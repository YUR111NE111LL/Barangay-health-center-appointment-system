<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if ($request->filled('oauth_flash')) {
            $payload = Cache::get('oauth_login_flash:'.$request->query('oauth_flash'));
            if (is_array($payload) && isset($payload['email'])) {
                $redirect = redirect()->route('login', $request->except('oauth_flash'))
                    ->withErrors(['email' => $payload['email']]);

                if (isset($payload['auth_scope_alert']) && is_string($payload['auth_scope_alert'])) {
                    $redirect->with('auth_scope_alert', $payload['auth_scope_alert']);
                }

                return $redirect;
            }
        }

        $tenant = tenant();

        if ($tenant) {
            $for = $request->query('for', 'resident');
            if (! in_array($for, ['tenant', 'resident'], true)) {
                $for = 'resident';
            }

            return view('auth.login-tenant', [
                'tenant' => $tenant,
                'for' => $for,
            ]);
        }

        return view('auth.login-central');
    }

    public function login(Request $request): RedirectResponse
    {
        $currentTenant = tenant();
        $for = $request->input('for', $currentTenant ? 'auto' : 'super-admin');

        $rules = [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
        if (! $currentTenant) {
            $rules['for'] = ['required', 'in:super-admin'];
        } elseif (in_array($for, ['tenant', 'resident'], true)) {
            $rules['for'] = ['required', 'in:tenant,resident'];
        }
        // Central = super-admin only (no tenant_id). Tenant domain = no tenant_id (current tenant).
        if (Recaptcha::shouldProcess()) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }
        $validated = $request->validate($rules);

        if (Recaptcha::shouldProcess()) {
            $result = Recaptcha::verifyV3($request, $validated['recaptcha_token'], 'login');
            if (! $result['ok']) {
                $message = ($result['reason'] ?? '') === 'network'
                    ? __('Unable to verify reCAPTCHA right now. Please try again in a moment.')
                    : __('reCAPTCHA verification failed. Please try again.');

                return back()
                    ->withInput($request->only('email', 'for', 'tenant_id'))
                    ->withErrors(['email' => $message]);
            }
        }

        $email = $validated['email'];
        $password = $validated['password'];
        if ($currentTenant) {
            // Strict tenant isolation: tenant-domain login only authenticates against tenant DB.
            if (! Schema::hasTable('users')) {
                return back()
                    ->withInput($request->only('email', 'for'))
                    ->withErrors(['email' => 'This barangay is not ready yet. Please contact the Super Admin.']);
            }

            $user = User::where('tenant_id', $currentTenant->id)
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first();

            if (! $user) {
                $message = 'This email is not registered for this barangay.';

                return back()
                    ->withInput($request->only('email', 'for'))
                    ->with('auth_scope_alert', $message)
                    ->withErrors(['email' => $message]);
            }
        } else {
            // Super Admin login: only allow if email is not already registered under a tenant
            $alreadyUnderTenant = User::whereNotNull('tenant_id')
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->exists();
            if ($alreadyUnderTenant) {
                $message = 'This email is registered under a barangay account and cannot log in as Super Admin.';

                return back()
                    ->withInput($request->only('email', 'for'))
                    ->with('auth_scope_alert', $message)
                    ->withErrors(['email' => $message.' Please use Resident or Staff login and select your barangay.']);
            }
            $user = User::whereNull('tenant_id')
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first();
            if (! $user) {
                return back()
                    ->withInput($request->only('email', 'for'))
                    ->withErrors(['email' => 'The provided credentials do not match our records.']);
            }
            if (! $user->isSuperAdmin()) {
                $message = 'This account is not a Super Admin account.';

                return back()
                    ->withInput($request->only('email', 'for'))
                    ->with('auth_scope_alert', $message)
                    ->withErrors(['email' => $message.' Use Staff or Resident login and select your barangay.']);
            }
        }

        if (! Hash::check($password, $user->getAuthPassword())) {
            return back()
                ->withInput($request->only('email', 'for', 'tenant_id'))
                ->withErrors(['email' => 'The provided credentials do not match our records.']);
        }

        // Staff/nurse/admin on a tenant domain arriving via the resident-mode form: never call
        // Auth::login() in the current _resident session — doing so would overwrite any resident
        // already logged in on this browser. Check approval directly on the model and redirect
        // straight to the staff session handoff without touching the _resident session at all.
        if ($currentTenant && ! $user->canAccessResidentPortal() && $for !== 'tenant') {
            if ($user->isPendingApproval()) {
                return back()
                    ->withInput($request->only('email', 'for', 'tenant_id'))
                    ->withErrors(['email' => 'Your account is pending approval. A Super Admin must approve it before you can log in.']);
            }

            $token = bin2hex(random_bytes(32));
            Cache::put('email_sso:'.$token, [
                'tenant_id' => (int) $currentTenant->id,
                'user_id' => (int) $user->id,
            ], now()->addMinutes(5));

            return redirect()->route('auth.email.tenant-session', ['token' => $token, 'for' => 'tenant']);
        }

        // Normal login path: resident, super-admin, or staff arriving via ?for=tenant.
        Auth::login($user, $request->boolean('remember'));

        if ($user->isPendingApproval()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email', 'for', 'tenant_id'))
                ->withErrors(['email' => 'Your account is pending approval. A Super Admin must approve it before you can log in.']);
        }

        // Super Admin: redirect to super-admin dashboard.
        if ($user->isSuperAdmin()) {
            $request->session()->regenerate();

            return redirect()->intended(route('super-admin.dashboard'))
                ->with('success', __('You have logged in successfully.'));
        }

        // Tenant or Resident: already verified they belong to selected barangay.
        // Don't use intended() here; stale central URLs in the session (e.g. /super-admin)
        // can wrongly pull tenant users back to the central app.
        $request->session()->regenerate();
        if ($user->canAccessResidentPortal()) {
            return redirect()->to('/resident')
                ->with('success', __('You have logged in successfully.'));
        }

        return redirect()->to('/backend')
            ->with('success', __('You have logged in successfully.'));
    }
}
