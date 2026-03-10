<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token): View
    {
        $email = $request->query('email');
        $tenantId = $request->query('tenant_id');

        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $email,
            'tenant_id' => $tenantId,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $emailNormalized = strtolower($request->email);
        $user = User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$emailNormalized]);
        if ($request->filled('tenant_id')) {
            $user->where('tenant_id', $request->tenant_id);
        } else {
            $user->whereNull('tenant_id');
        }
        $user = $user->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('We could not find an account with that email.')]);
        }

        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker();
        if (! $broker->tokenExists($user, $request->token)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('This password reset link is invalid or has expired.')]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        $broker->getRepository()->delete($user);

        event(new PasswordReset($user));

        $for = $user->tenant_id
            ? ($user->role === 'Resident' ? 'resident' : 'tenant')
            : 'super-admin';

        return redirect()->route('login', ['for' => $for])
            ->with('status', __('Your password has been reset. You can now log in.'));
    }
}
