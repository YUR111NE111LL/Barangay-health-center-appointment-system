<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(Request $request): View
    {
        $for = $request->query('for', 'resident'); // super-admin | tenant | resident
        $tenants = in_array($for, ['tenant', 'resident'], true)
            ? Tenant::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('auth.login', [
            'for' => $for,
            'tenants' => $tenants,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $for = $request->input('for', 'resident');
        $rules = [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
        if (in_array($for, ['tenant', 'resident'], true)) {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }
        $siteKey = config('services.recaptcha.v3.site_key');
        $secretKey = config('services.recaptcha.v3.secret_key');
        $skipRecaptcha = config('app.debug'); // allow login without reCAPTCHA in local/dev
        if ($siteKey && $secretKey && ! $skipRecaptcha) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }
        $validated = $request->validate($rules);

        if ($siteKey && $secretKey && ! $skipRecaptcha) {
            $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $validated['recaptcha_token'],
                'remoteip' => $request->ip(),
            ]);
            $body = $verify->json();
            $threshold = (float) config('services.recaptcha.v3.score_threshold', 0.5);
            if (! ($body['success'] ?? false) || (float) ($body['score'] ?? 0) < $threshold) {
                return back()
                    ->withInput($request->only('email', 'for', 'tenant_id'))
                    ->withErrors(['email' => 'reCAPTCHA verification failed. Please try again.']);
            }
        }

        $email = $validated['email'];
        $password = $validated['password'];
        $selectedTenantId = in_array($for, ['tenant', 'resident'], true) ? (int) ($validated['tenant_id'] ?? 0) : null;

        // Resolve user by login type: only the user registered under the selected barangay (or Super Admin with no tenant)
        if (in_array($for, ['tenant', 'resident'], true)) {
            $user = User::where('tenant_id', $selectedTenantId)
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first();
            if (! $user) {
                return back()
                    ->withInput($request->only('email', 'for', 'tenant_id'))
                    ->withErrors(['email' => 'This email is not registered under the selected barangay. Please select the barangay where you signed up, or sign up first.']);
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

        // Tenant or Resident: already verified they belong to selected barangay
        $request->session()->regenerate();
        if ($user->role === 'Resident') {
            return redirect()->intended(route('resident.dashboard'))
                ->with('success', __('You have logged in successfully.'));
        }
        return redirect()->intended(route('backend.dashboard'))
            ->with('success', __('You have logged in successfully.'));
    }
}
