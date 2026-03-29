<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(Request $request): View
    {
        $for = $request->query('for', 'resident');
        $tenants = in_array($for, ['tenant', 'resident'], true)
            ? Tenant::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('auth.passwords.email', [
            'for' => $for,
            'tenants' => $tenants,
        ]);
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $for = $request->input('for', 'resident');
        $rules = ['email' => ['required', 'email']];
        if (in_array($for, ['tenant', 'resident'], true)) {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }
        if (Recaptcha::shouldProcess()) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }
        $validated = $request->validate($rules);

        if (Recaptcha::shouldProcess()) {
            $result = Recaptcha::verifyV3($request, $validated['recaptcha_token'], 'forgot_password');
            if (! $result['ok']) {
                return back()
                    ->withInput($request->only('email', 'for', 'tenant_id'))
                    ->withErrors(['email' => __('reCAPTCHA verification failed. Please try again.')]);
            }
        }

        $emailNormalized = strtolower($validated['email']);
        $user = User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$emailNormalized]);
        if (in_array($for, ['tenant', 'resident'], true)) {
            $user->where('tenant_id', $validated['tenant_id']);
        } else {
            $user->whereNull('tenant_id');
        }
        $user = $user->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email', 'for', 'tenant_id'))
                ->withErrors(['email' => __('We could not find an account with that email for the selected barangay.')]);
        }

        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);
        $user->sendPasswordResetNotification($token);

        $mailer = Config::get('mail.default');
        $actuallySends = ! in_array($mailer, ['log', 'array'], true);
        $status = $actuallySends
            ? __('If that email is registered, we have sent you a password reset link.')
            : __('A reset link was generated. To receive it by email, set MAIL_MAILER=smtp and configure Gmail (or another SMTP) in your .env, then run: php artisan config:clear');

        return back()->with('status', $status);
    }
}
