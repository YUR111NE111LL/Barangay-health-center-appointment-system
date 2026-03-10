<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    /**
     * Redirect to Google. Call this with for=resident|tenant|super-admin and optionally tenant_id (required for resident/tenant).
     * Uses current request host for callback URL so both localhost and 127.0.0.1 work. State carries for/tenant_id.
     */
    public function redirect(\Illuminate\Http\Request $request): RedirectResponse
    {
        $for = $request->query('for', 'resident');
        $tenantId = $request->query('tenant_id');
        $tenantId = $tenantId !== null && $tenantId !== '' ? (int) $tenantId : null;
        $intent = $request->query('intent', 'login'); // 'login' = only existing accounts; 'signup' = may create new
        if (! in_array($intent, ['login', 'signup'], true)) {
            $intent = 'login';
        }

        $state = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode([
            'for' => $for,
            'tenant_id' => $tenantId,
            'intent' => $intent,
        ])));

        $callbackUrl = $request->getSchemeAndHttpHost() . '/auth/google/callback';

        return Socialite::driver('google')
            ->redirectUrl($callbackUrl)
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Handle Google callback: find or create user, then login with tenant check.
     * Reads for and tenant_id from state parameter (so we don't rely on session).
     */
    public function callback(\Illuminate\Http\Request $request): RedirectResponse
    {
        $for = 'resident';
        $tenantId = null;
        $intent = 'login';
        $stateParam = $request->query('state');
        if ($stateParam) {
            $stateParam = str_replace(['-', '_'], ['+', '/'], $stateParam);
            $stateParam .= str_repeat('=', (4 - strlen($stateParam) % 4) % 4);
            $decoded = @json_decode(base64_decode($stateParam), true);
            if (is_array($decoded)) {
                $for = $decoded['for'] ?? 'resident';
                $tenantId = isset($decoded['tenant_id']) ? (int) $decoded['tenant_id'] : null;
                $intent = isset($decoded['intent']) && $decoded['intent'] === 'signup' ? 'signup' : 'login';
            }
        }
        if ($tenantId === 0) {
            $tenantId = null;
        }

        if (in_array($for, ['tenant', 'resident'], true) && ! $tenantId) {
            return redirect()->route('login', ['for' => $for])
                ->withErrors(['email' => 'Please select your barangay first, then try "Login with Google" or "Sign up with Google" again.']);
        }

        if ($request->has('error')) {
            $message = $request->get('error') === 'access_denied'
                ? 'Google sign-in was cancelled. Please try again or use your email and password.'
                : 'Google login failed. Please try again or use your email and password.';
            return redirect()->route('login', ['for' => $for])
                ->withErrors(['email' => $message])
                ->withInput($tenantId ? ['tenant_id' => $tenantId] : []);
        }

        if (! $request->filled('code')) {
            return redirect()->route('login', ['for' => $for])
                ->withErrors(['email' => 'Google did not return an authorization code. Please try "Login with Google" again (select barangay first).'])
                ->withInput($tenantId ? ['tenant_id' => $tenantId] : []);
        }

        $callbackUrl = $request->getSchemeAndHttpHost() . '/auth/google/callback';

        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl($callbackUrl)
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            $responseBody = '';
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $responseBody = (string) $e->getResponse()->getBody();
            }
            Log::warning('Google OAuth callback failed.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'response_body' => $responseBody ?: null,
                'callback_url_used' => $callbackUrl,
                'for' => $for,
            ]);

            $message = 'Google login was cancelled or failed. Please try again or use your email and password.';
            if (str_contains(get_class($e), 'InvalidStateException') || str_contains($e->getMessage(), 'state')) {
                $message = 'Session expired or mismatch. Please select your barangay again and click "Login with Google" in one go (same browser, no new tab).';
            } elseif (str_contains($e->getMessage(), 'redirect_uri_mismatch') || str_contains($responseBody, 'redirect_uri_mismatch')) {
                $message = 'Google redirect URI mismatch. In Google Cloud Console → Credentials → your OAuth client → Authorized redirect URIs, add this site’s callback URL (contact support if unsure).';
            } elseif (str_contains($responseBody, 'invalid_client') || str_contains($e->getMessage(), '401')) {
                $message = 'Google sign-in is misconfigured. Please contact support or try again later.';
            }

            return redirect()->route('login', ['for' => $for])
                ->withErrors(['email' => $message])
                ->withInput($tenantId ? ['tenant_id' => $tenantId] : []);
        }

        $email = $googleUser->getEmail();
        $name = $googleUser->getName() ?: $email;
        $googleId = $googleUser->getId();
        $emailNormalized = $email !== null ? strtolower($email) : '';

        // Find existing user: for super-admin only consider Super Admin users (tenant_id null); for tenant/resident consider by tenant
        $user = User::withoutGlobalScopes()
            ->when($for === 'super-admin', function ($q) {
                $q->whereNull('tenant_id');
            })
            ->where(function ($q) use ($googleId, $tenantId, $emailNormalized) {
                $q->where('google_id', $googleId);
                if ($tenantId !== null && $tenantId !== '') {
                    $q->orWhere(function ($q2) use ($tenantId, $emailNormalized) {
                        $q2->where('tenant_id', (int) $tenantId)
                            ->whereRaw('LOWER(email) = ?', [$emailNormalized]);
                    });
                } else {
                    $q->orWhere(function ($q2) use ($emailNormalized) {
                        $q2->whereNull('tenant_id')
                            ->whereRaw('LOWER(email) = ?', [$emailNormalized]);
                    });
                }
            })
            ->first();

        if ($user) {
            if ($user->isPendingApproval()) {
                return redirect()->route('login', ['for' => $for])
                    ->withErrors(['email' => 'Your account is pending approval. An admin must approve it before you can log in.'])
                    ->withInput($tenantId ? ['tenant_id' => $tenantId] : []);
            }
            // Link Google to account (e.g. added via Backend "Add user" or previously signed up with email)
            if (! $user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
            // Tenant must match for tenant/resident
            if (in_array($for, ['tenant', 'resident'], true) && (int) $user->tenant_id !== (int) $tenantId) {
                $correct = $user->tenant?->name ?? 'your barangay';
                return redirect()->route('login', ['for' => $for])
                    ->withErrors(['email' => "This Google account is registered under \"{$correct}\". Please select that barangay to log in."])
                    ->withInput($tenantId ? ['tenant_id' => $tenantId] : []);
            }
            if ($for === 'super-admin' && ! $user->isSuperAdmin()) {
                return redirect()->route('login', ['for' => 'super-admin'])
                    ->withErrors(['email' => 'This account is not a Super Admin. Use Staff/Resident login with the correct barangay.']);
            }
            Auth::login($user, true);
            $request->session()->regenerate();
            return $this->redirectIntended($user);
        }

        // New user: allow Super Admin sign-up via Google, or Resident with tenant selected
        if (empty($emailNormalized) || empty($email)) {
            return redirect()->route('login', ['for' => $for])
                ->withErrors(['email' => 'Google did not provide an email. Please use email and password to sign up.'])
                ->withInput($tenantId ? ['tenant_id' => $tenantId] : []);
        }

        if ($for === 'super-admin') {
            // Do not create Super Admin if this email is already registered under a tenant
            $alreadyUnderTenant = User::withoutGlobalScopes()
                ->whereNotNull('tenant_id')
                ->whereRaw('LOWER(email) = ?', [$emailNormalized])
                ->exists();
            if ($alreadyUnderTenant) {
                return redirect()->route('login', ['for' => 'super-admin'])
                    ->withErrors(['email' => 'This Google account is already registered under a barangay. Please use Resident or Staff login and select your barangay.']);
            }
            // Sign up new Super Admin via Google (only way to get Super Admin)
            $user = User::create([
                'tenant_id' => null,
                'role' => User::ROLE_SUPER_ADMIN,
                'name' => $name,
                'email' => $emailNormalized ?: $email,
                'password' => null,
                'google_id' => $googleId,
            ]);
            $user->syncRoles([User::ROLE_SUPER_ADMIN]);
            Auth::login($user, true);
            $request->session()->regenerate();
            return redirect()->intended(route('super-admin.dashboard'));
        }

        if ($for !== 'resident' || ! $tenantId) {
            $message = 'No account found for this Google account. Staff: sign up with email first, then you can link Google. Residents: sign up first (Sign up page → choose barangay → Sign up with Google).';
            return redirect()->route('login', ['for' => $for])
                ->withErrors(['email' => $message])
                ->withInput($tenantId ? ['tenant_id' => $tenantId] : []);
        }

        $existing = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(email) = ?', [$emailNormalized])
            ->first();
        if ($existing) {
            $existing->update(['google_id' => $googleId]);
            Auth::login($existing, true);
            $request->session()->regenerate();
            return redirect()->intended(route('resident.dashboard'));
        }

        // No existing account: only create if they used "Sign up with Google" (intent=signup)
        if ($intent !== 'signup') {
            return redirect()->route('login', ['for' => $for])
                ->withErrors(['email' => 'No account found for this Google account. Please sign up first: go to Sign up, select your barangay, then use "Sign up with Google".'])
                ->withInput(['tenant_id' => $tenantId]);
        }

        $user = User::create([
            'tenant_id' => $tenantId,
            'role' => User::ROLE_RESIDENT,
            'name' => $name,
            'email' => $emailNormalized ?: $email,
            'password' => null,
            'google_id' => $googleId,
        ]);
        $user->syncRoles([User::ROLE_RESIDENT]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('resident.dashboard'));
    }

    private function redirectIntended(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()->intended(route('super-admin.dashboard'));
        }
        if ($user->role === 'Resident') {
            return redirect()->intended(route('resident.dashboard'));
        }
        return redirect()->intended(route('backend.dashboard'));
    }
}
