<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(Request $request): View
    {
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
        $for = $request->input('for', $currentTenant ? 'resident' : 'super-admin');

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
        $siteKey = config('services.recaptcha.v3.site_key');
        $secretKey = config('services.recaptcha.v3.secret_key');
        $skipRecaptcha = config('app.debug') || app()->environment('local');
        if ($siteKey && $secretKey && ! $skipRecaptcha) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }
        $validated = $request->validate($rules);

        if ($siteKey && $secretKey && ! $skipRecaptcha) {
            // Fail fast if Google is slow/unreachable (prevents login page from "hanging").
            try {
                $verify = Http::asForm()->timeout(5)->connectTimeout(2)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secretKey,
                    'response' => $validated['recaptcha_token'],
                    'remoteip' => $request->ip(),
                ]);
            } catch (ConnectionException $e) {
                return back()
                    ->withInput($request->only('email', 'for', 'tenant_id'))
                    ->withErrors(['email' => 'Unable to verify reCAPTCHA right now. Please try again in a moment.']);
            }

            $body = $verify->json() ?? [];
            $threshold = (float) config('services.recaptcha.v3.score_threshold', 0.5);
            if (! ($body['success'] ?? false) || (float) ($body['score'] ?? 0) < $threshold) {
                return back()
                    ->withInput($request->only('email', 'for', 'tenant_id'))
                    ->withErrors(['email' => 'reCAPTCHA verification failed. Please try again.']);
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
                return back()
                    ->withInput($request->only('email', 'for'))
                    ->withErrors(['email' => 'This email is not registered for this barangay.']);
            }
            $allowedRoles = $for === 'resident' ? ['Resident'] : ['Health Center Admin', 'Nurse', 'Staff'];
            if (! in_array($user->role, $allowedRoles, true)) {
                return back()
                    ->withInput($request->only('email', 'for'))
                    ->withErrors(['email' => $for === 'resident' ? 'Use Staff / Nurse login for this account.' : 'Use Resident login for this account.']);
            }
        } else {
            // Super Admin login: only allow if email is not already registered under a tenant
            $alreadyUnderTenant = User::whereNotNull('tenant_id')
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->exists();
            if ($alreadyUnderTenant) {
                return back()
                    ->withInput($request->only('email', 'for'))
                    ->withErrors(['email' => 'This email is registered under a barangay. Please use Resident or Staff login and select your barangay.']);
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
                return back()
                    ->withInput($request->only('email', 'for'))
                    ->withErrors(['email' => 'This account is not a Super Admin. Use Staff or Resident login and select your barangay.']);
            }
        }

        if (! Hash::check($password, $user->getAuthPassword())) {
            return back()
                ->withInput($request->only('email', 'for', 'tenant_id'))
                ->withErrors(['email' => 'The provided credentials do not match our records.']);
        }

        Auth::login($user, $request->boolean('remember'));

        if ($user->isPendingApproval()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email', 'for', 'tenant_id'))
                ->withErrors(['email' => 'Your account is pending approval. A Super Admin must approve it before you can log in.']);
        }

        // Super Admin: redirect to super-admin dashboard
        if ($user->isSuperAdmin()) {
            $request->session()->regenerate();

            return redirect()->intended(route('super-admin.dashboard'))
                ->with('success', __('You have logged in successfully.'));
        }

        // Tenant or Resident: already verified they belong to selected barangay.
        // Don't use intended() here, because stale central URLs in session (e.g. /super-admin)
        // can wrongly pull tenant users back to the central app.
        $request->session()->regenerate();
        if ($user->role === 'Resident') {
            // Use relative path to stay on the current tenant host.
            return redirect()->to('/resident')
                ->with('success', __('You have logged in successfully.'));
        }

        // Use relative path to stay on the current tenant host.
        return redirect()->to('/backend')
            ->with('success', __('You have logged in successfully.'));
    }
}
