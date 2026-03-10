<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        $tenants = Tenant::where('is_active', true)->orderBy('name')->get();
        return view('auth.register', compact('tenants'));
    }

    public function register(Request $request): RedirectResponse
    {
        $allowedRoles = [
            User::ROLE_RESIDENT,
            User::ROLE_STAFF,
            User::ROLE_NURSE,
            User::ROLE_HEALTH_CENTER_ADMIN,
        ];
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($allowedRoles)],
        ];
        $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        $rules['email'][] = Rule::unique('users')->where('tenant_id', $request->input('tenant_id'));
        $siteKey = config('services.recaptcha.v3.site_key');
        $secretKey = config('services.recaptcha.v3.secret_key');
        if ($siteKey && $secretKey && ! config('app.debug')) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }
        $validated = $request->validate($rules);

        if ($siteKey && $secretKey && ! config('app.debug')) {
            $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $validated['recaptcha_token'],
                'remoteip' => $request->ip(),
            ]);
            $body = $verify->json();
            $threshold = (float) config('services.recaptcha.v3.score_threshold', 0.5);
            if (! ($body['success'] ?? false) || (float) ($body['score'] ?? 0) < $threshold) {
                return back()
                    ->withInput($request->only('name', 'email', 'tenant_id', 'role'))
                    ->withErrors(['email' => 'reCAPTCHA verification failed. Please try again.']);
            }
        }

        $validated['password'] = Hash::make($validated['password']);
        $requiresApproval = in_array($validated['role'], User::rolesRequiringApproval(), true);
        if ($requiresApproval) {
            $validated['is_approved'] = false;
        }
        unset($validated['recaptcha_token']);

        $user = User::create($validated);
        $user->syncRoles([$validated['role']]);

        event(new Registered($user));

        if ($requiresApproval) {
            return redirect()->route('pending-approval')
                ->with('status', 'Your account has been created. An admin must approve it before you can log in.');
        }

        Auth::login($user);
        if ($user->role === User::ROLE_RESIDENT) {
            return redirect()->route('resident.dashboard');
        }
        return redirect()->route('backend.dashboard');
    }
}
