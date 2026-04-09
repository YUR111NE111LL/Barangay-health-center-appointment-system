<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantCreationService;
use App\Support\TenantDomainInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class GoogleLoginController extends Controller
{
    /**
     * Single OAuth redirect URI (GOOGLE_REDIRECT_URI or APP_URL + /auth/google/callback) so Google Cloud
     * Console only needs one “Authorized redirect URI”. Tenant users are sent back to their barangay host
     * via {@see completeTenantSession()} after the central callback.
     */
    private function googleOAuthRedirectUri(): string
    {
        $uri = config('services.google.redirect');
        if (is_string($uri) && $uri !== '') {
            return rtrim($uri, '/');
        }

        return rtrim((string) config('app.url'), '/').'/auth/google/callback';
    }

    /**
     * Public URL for a tenant (scheme + host + port) to redirect the browser after central OAuth.
     */
    private function tenantBaseUrl(Tenant $tenant, Request $request): ?string
    {
        $domain = $tenant->domains()->first()?->domain;
        if (! is_string($domain) || $domain === '') {
            return null;
        }
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $portSuffix = ($port && ! in_array((int) $port, [80, 443], true)) ? ':'.$port : '';

        return $scheme.'://'.$domain.$portSuffix;
    }

    /**
     * After Google returns to the central callback, send the user to their tenant login with a flash error.
     */
    private function redirectTenantLoginOAuthError(Request $request, ?Tenant $tenant, ?int $tenantId, string $for, string $message): RedirectResponse
    {
        if ($tenantId && in_array($for, ['tenant', 'resident'], true)) {
            $tenant ??= Tenant::query()->find($tenantId);
            if ($tenant) {
                $base = $this->tenantBaseUrl($tenant, $request);
                if ($base) {
                    $key = (string) Str::uuid();
                    Cache::put('oauth_login_flash:'.$key, ['email' => $message], now()->addMinutes(10));

                    return redirect()->away($base.'/login?'.http_build_query([
                        'for' => $for,
                        'tenant_id' => $tenantId,
                        'oauth_flash' => $key,
                    ]));
                }
            }
        }

        return redirect()->route('login', ['for' => $for])
            ->withErrors(['email' => $message])
            ->withInput($tenantId ? ['tenant_id' => $tenantId] : []);
    }

    /**
     * Central OAuth cannot set a session cookie for the tenant domain; use a one-time token instead.
     * User + central mirror are already persisted in {@see handleGoogleTenantUserCallback}.
     */
    private function redirectToTenantSessionAfterGoogle(Request $request, Tenant $tenant, User $tenantUser): RedirectResponse
    {
        $token = bin2hex(random_bytes(32));
        Cache::put('google_sso:'.$token, [
            'tenant_id' => (int) $tenant->id,
            'user_id' => (int) $tenantUser->id,
        ], now()->addMinutes(5));

        $base = $this->tenantBaseUrl($tenant, $request);
        if (! $base) {
            return redirect()->route('login', ['for' => 'resident'])
                ->withErrors(['email' => __('Could not resolve your barangay web address. Contact support.')]);
        }

        $portal = $tenantUser->canAccessResidentPortal() ? 'resident' : 'staff';

        return redirect()->away($base.'/auth/google/tenant-session?token='.urlencode($token).'&portal='.$portal);
    }

    /**
     * Redirect to tenant sign-up with a status message (central OAuth callback cannot flash session to tenant host).
     */
    private function redirectTenantSignUpOAuthStatus(Request $request, Tenant $tenant, array $query, string $status): RedirectResponse
    {
        $base = $this->tenantBaseUrl($tenant, $request);
        if (! $base) {
            return redirect()->route('sign-up', $query)->with('status', $status);
        }
        $key = (string) Str::uuid();
        Cache::put('oauth_register_flash:'.$key, ['status' => $status], now()->addMinutes(10));

        return redirect()->away($base.'/sign-up?'.http_build_query(array_merge($query, ['oauth_flash' => $key])));
    }

    /**
     * Finish Google sign-in on the tenant host (session is created here).
     */
    public function completeTenantSession(Request $request): RedirectResponse
    {
        $token = $request->query('token');
        if (! is_string($token) || strlen($token) < 32) {
            return redirect()->route('login', ['for' => 'resident'])
                ->withErrors(['email' => __('Invalid or expired sign-in link. Please use “Login with Google” again.')]);
        }

        $payload = Cache::pull('google_sso:'.$token);
        if (! is_array($payload) || ! isset($payload['tenant_id'], $payload['user_id'])) {
            return redirect()->route('login', ['for' => 'resident'])
                ->withErrors(['email' => __('Invalid or expired sign-in link. Please use “Login with Google” again.')]);
        }

        $currentTenant = tenant();
        if (! $currentTenant || (int) $payload['tenant_id'] !== (int) $currentTenant->id) {
            return redirect()->route('login', ['for' => 'resident'])
                ->withErrors(['email' => __('This sign-in link is for a different barangay. Open your barangay URL and try again.')]);
        }

        $user = User::withoutGlobalScopes()->find($payload['user_id']);
        if (! $user) {
            return redirect()->route('login', ['for' => 'resident'])
                ->withErrors(['email' => __('Your account could not be found. Please contact support.')]);
        }
        if ($user->isPendingApproval()) {
            return redirect()->route('login', ['for' => 'resident'])
                ->withErrors(['email' => __('Your account is pending approval. An admin must approve it before you can log in.')]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectTenantUserAfterGoogle($user);
    }

    /**
     * Finish email magic-link sign-in on the tenant host (session is created here).
     * Used for auto-provisioned tenants where the first admin user is created from the application email.
     */
    public function completeEmailTenantSession(Request $request): RedirectResponse
    {
        $token = $request->query('token');
        if (! is_string($token) || strlen($token) < 32) {
            return redirect()->route('login', ['for' => 'tenant'])
                ->withErrors(['email' => __('Invalid or expired sign-in link. Please request a new link.')]);
        }

        $payload = Cache::pull('email_sso:'.$token);
        if (! is_array($payload) || ! isset($payload['tenant_id'], $payload['user_id'])) {
            return redirect()->route('login', ['for' => 'tenant'])
                ->withErrors(['email' => __('Invalid or expired sign-in link. Please request a new link.')]);
        }

        $currentTenant = tenant();
        if (! $currentTenant || (int) $payload['tenant_id'] !== (int) $currentTenant->id) {
            return redirect()->route('login', ['for' => 'tenant'])
                ->withErrors(['email' => __('This sign-in link is for a different barangay. Open your barangay URL and try again.')]);
        }

        $user = User::withoutGlobalScopes()->find($payload['user_id']);
        if (! $user) {
            return redirect()->route('login', ['for' => 'tenant'])
                ->withErrors(['email' => __('Your account could not be found. Please contact support.')]);
        }
        if ($user->isPendingApproval()) {
            return redirect()->route('login', ['for' => 'tenant'])
                ->withErrors(['email' => __('Your account is pending approval. An admin must approve it before you can log in.')]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->to('/backend');
    }

    /**
     * Build URL-safe base64 state payload. Payload is IN the state so it survives the round-trip (no cache dependency).
     */
    private static function encodeState(string $for, ?int $tenantId, string $intent, ?string $key = null): string
    {
        $payload = ['f' => $for, 't' => $tenantId, 'i' => $intent];
        if (is_string($key) && $key !== '') {
            $payload['k'] = $key;
        }

        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    }

    /**
     * Decode state payload. Returns [for, tenant_id, intent] or null.
     */
    private static function decodeState(string $state): ?array
    {
        $state = str_replace(' ', '+', trim($state));
        $state = str_replace(['-', '_'], ['+', '/'], $state);
        $state .= str_repeat('=', (4 - strlen($state) % 4) % 4);
        $decoded = @json_decode(base64_decode($state), true);

        if (! is_array($decoded)) {
            return null;
        }

        return [
            'for' => $decoded['f'] ?? 'resident',
            'tenant_id' => isset($decoded['t']) ? (int) $decoded['t'] : null,
            'intent' => ($decoded['i'] ?? 'login') === 'signup' ? 'signup' : 'login',
            'key' => isset($decoded['k']) && is_string($decoded['k']) ? $decoded['k'] : null,
        ];
    }

    /**
     * Redirect to Google. Call this with for=resident|tenant|super-admin and optionally tenant_id (required for resident/tenant).
     * Puts payload directly in state so chosen barangay is never lost.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $for = $request->query('for', 'resident');
        $tenantId = $request->query('tenant_id');
        $tenantId = $tenantId !== null && $tenantId !== '' ? (int) $tenantId : null;
        if (in_array($for, ['tenant', 'resident'], true) && ! $tenantId && tenant()) {
            $tenantId = (int) tenant()->id;
        }
        $intent = $request->query('intent', 'login');
        if (! in_array($intent, ['login', 'signup'], true)) {
            $intent = 'login';
        }
        $key = $request->query('app_key');
        $key = is_string($key) && $key !== '' ? $key : null;

        $state = self::encodeState($for, $tenantId, $intent, $key);

        $callbackUrl = $this->googleOAuthRedirectUri();
        $clientId = config('services.google.client_id');
        if (! $clientId) {
            return redirect()->route('login', ['for' => $for])
                ->withErrors(['email' => 'Google sign-in is not configured.']);
        }

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
        ];

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params));
    }

    /**
     * Handle Google callback: find or create user, then login with tenant check.
     * State contains encoded payload (f,t,i).
     *
     * Tenant/resident flows run inside $tenant->run() so users are stored in the tenant DB and mirrored to central;
     * the browser is then redirected to the tenant host via {@see completeTenantSession()} (OAuth uses one central redirect URI).
     */
    public function callback(Request $request): RedirectResponse
    {
        $for = 'resident';
        $tenantId = null;
        $intent = 'login';
        $key = null;
        $stateParam = $request->query('state');
        if ($stateParam !== null && $stateParam !== '') {
            $stateParam = (string) $stateParam;
            $decoded = self::decodeState($stateParam);
            if ($decoded !== null) {
                $for = $decoded['for'];
                $tenantId = $decoded['tenant_id'];
                $intent = $decoded['intent'];
                $key = $decoded['key'] ?? null;
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

            return $this->redirectTenantLoginOAuthError($request, null, $tenantId, $for, $message);
        }

        if (! $request->filled('code')) {
            return $this->redirectTenantLoginOAuthError($request, null, $tenantId, $for, 'Google did not return an authorization code. Please try "Login with Google" again (select barangay first).');
        }

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $googleDriver */
            $googleDriver = Socialite::driver('google');

            $callbackUrl = $this->googleOAuthRedirectUri();

            $googleUser = $googleDriver
                ->stateless()
                ->redirectUrl($callbackUrl)
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
                'callback_url_used' => $this->googleOAuthRedirectUri(),
                'for' => $for,
            ]);

            $message = 'Google login was cancelled or failed. Please try again or use your email and password.';
            if (str_contains(get_class($e), 'InvalidStateException') || str_contains($e->getMessage(), 'state')) {
                $message = 'Session expired or mismatch. Please select your barangay again and click "Login with Google" in one go (same browser, no new tab).';
            } elseif (str_contains($e->getMessage(), 'redirect_uri_mismatch') || str_contains($responseBody, 'redirect_uri_mismatch')) {
                $message = 'Google redirect URI mismatch. In Google Cloud Console → Credentials → your OAuth client → Authorized redirect URIs, add this exact URL: '.$this->googleOAuthRedirectUri();
            } elseif (str_contains($responseBody, 'invalid_client') || str_contains($e->getMessage(), '401')) {
                $message = 'Google sign-in is misconfigured. Please contact support or try again later.';
            }

            return $this->redirectTenantLoginOAuthError($request, null, $tenantId, $for, $message);
        }

        $email = $googleUser->getEmail();
        $name = $googleUser->getName() ?: $email;
        $googleId = $googleUser->getId();
        $emailNormalized = $email !== null ? strtolower($email) : '';

        if ($for === 'super-admin') {
            return $this->handleGoogleSuperAdminCallback($request, $intent, $email, $emailNormalized, $name, $googleId);
        }

        if ($for === 'tenant-application') {
            return $this->handleGoogleTenantApplicationCallback($request, $intent, $email, $emailNormalized, $name, $googleId, $key);
        }

        if (in_array($for, ['tenant', 'resident'], true) && $tenantId) {
            $tenant = Tenant::query()->find($tenantId);
            if (! $tenant) {
                return $this->redirectTenantLoginOAuthError($request, null, $tenantId, $for, __('That barangay could not be found. Please try again.'));
            }

            return $tenant->run(function () use ($request, $for, $tenant, $tenantId, $intent, $email, $emailNormalized, $name, $googleId) {
                return $this->handleGoogleTenantUserCallback($request, $for, $tenant, $tenantId, $intent, $email, $emailNormalized, $name, $googleId);
            });
        }

        return redirect()->route('login', ['for' => $for])
            ->withErrors(['email' => __('Could not complete Google sign-in. Please try again.')]);
    }

    /**
     * Super Admin accounts live in the central database only.
     */
    private function handleGoogleSuperAdminCallback(Request $request, string $intent, ?string $email, string $emailNormalized, string $name, string $googleId): RedirectResponse
    {
        $user = User::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where(function ($q) use ($googleId, $emailNormalized) {
                $q->where('google_id', $googleId)
                    ->orWhereRaw('LOWER(email) = ?', [$emailNormalized]);
            })
            ->first();

        if ($user) {
            if ($user->isPendingApproval()) {
                return redirect()->route('login', ['for' => 'super-admin'])
                    ->withErrors(['email' => 'Your account is pending approval. An admin must approve it before you can log in.']);
            }
            if (! $user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
            if (! $user->isSuperAdmin()) {
                return redirect()->route('login', ['for' => 'super-admin'])
                    ->withErrors(['email' => 'This account is not a Super Admin. Use Staff/Resident login with the correct barangay.']);
            }
            Auth::login($user, true);
            $request->session()->regenerate();

            return $this->redirectIntended($user);
        }

        if (empty($emailNormalized) || empty($email)) {
            return redirect()->route('login', ['for' => 'super-admin'])
                ->withErrors(['email' => 'Google did not provide an email. Please use email and password to sign up.']);
        }

        $alreadyUnderTenant = User::withoutGlobalScopes()
            ->whereNotNull('tenant_id')
            ->whereRaw('LOWER(email) = ?', [$emailNormalized])
            ->exists();
        if ($alreadyUnderTenant) {
            return redirect()->route('login', ['for' => 'super-admin'])
                ->withErrors(['email' => 'This Google account is already registered under a barangay. Please use Resident or Staff login and select your barangay.']);
        }
        if ($intent !== 'signup') {
            return redirect()->route('sign-up', ['for' => 'super-admin'])
                ->with('status', __('This Google account is not registered yet. Create your Super Admin account below, or use “Sign up with Google” on this page.'));
        }

        $newUser = User::create([
            'tenant_id' => null,
            'role' => User::ROLE_SUPER_ADMIN,
            'name' => $name,
            'email' => $emailNormalized ?: $email,
            'password' => null,
            'google_id' => $googleId,
        ]);
        $newUser->syncRoles([User::ROLE_SUPER_ADMIN]);
        Auth::login($newUser, true);
        $request->session()->regenerate();

        return redirect()->intended(route('super-admin.dashboard'));
    }

    /**
     * Mirror tenant DB user to central `users` (same idea as RegisterController for barangay-admin approvals).
     * Keeps central lists consistent and avoids “missing user on central” when using Google on a tenant.
     */
    private function syncTenantGoogleUserMirrorToCentral(Tenant $tenant, User $tenantUser): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');

        tenancy()->end();
        try {
            $tid = (int) $tenant->id;
            $emailLower = strtolower((string) $tenantUser->email);

            $centralUser = User::withoutGlobalScopes()
                ->where('tenant_id', $tid)
                ->whereRaw('LOWER(email) = ?', [$emailLower])
                ->first();

            $payload = [
                'tenant_id' => $tid,
                'role' => $tenantUser->role,
                'name' => $tenantUser->name,
                'purok_address' => $tenantUser->purok_address,
                'profile_picture' => $tenantUser->profile_picture,
                'email' => $tenantUser->email,
                'password' => $tenantUser->getRawOriginal('password') ?? $tenantUser->password,
                'google_id' => $tenantUser->google_id,
                'is_approved' => $tenantUser->is_approved,
            ];

            if (! $centralUser) {
                $centralUser = User::withoutGlobalScopes()->create($payload);
            } else {
                $centralUser->update($payload);
            }

            $roleExists = Schema::hasTable($rolesTable)
                && Role::query()
                    ->where('name', $tenantUser->role)
                    ->where('guard_name', config('auth.defaults.guard', 'web'))
                    ->exists();
            if ($roleExists && Schema::hasTable($modelHasRolesTable)) {
                $centralUser->syncRoles([$tenantUser->role]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to mirror tenant Google user to central database.', [
                'tenant_id' => $tenant->id,
                'user_id' => $tenantUser->id,
                'exception' => $e->getMessage(),
            ]);
        } finally {
            tenancy()->initialize($tenant);
        }
    }

    /**
     * Staff / resident accounts for a barangay live in that tenant’s database (same as email/password registration).
     */
    private function handleGoogleTenantUserCallback(Request $request, string $for, Tenant $tenant, int $tenantId, string $intent, ?string $email, string $emailNormalized, string $name, string $googleId): RedirectResponse
    {
        $user = User::withoutGlobalScopes()
            ->where(function ($q) use ($googleId, $tenantId, $emailNormalized) {
                $q->where('google_id', $googleId)
                    ->orWhere(function ($q2) use ($tenantId, $emailNormalized) {
                        $q2->where('tenant_id', (int) $tenantId)
                            ->whereRaw('LOWER(email) = ?', [$emailNormalized]);
                    });
            })
            ->first();

        if ($user) {
            if ($user->isPendingApproval()) {
                $this->syncTenantGoogleUserMirrorToCentral($tenant, $user);

                return $this->redirectTenantLoginOAuthError($request, $tenant, $tenantId, $for, 'Your account is pending approval. An admin must approve it before you can log in.');
            }
            if (! $user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
            if (in_array($for, ['tenant', 'resident'], true) && (int) $user->tenant_id !== (int) $tenantId) {
                $this->syncTenantGoogleUserMirrorToCentral($tenant, $user);
                $correct = $user->tenant?->name ?? 'your barangay';

                return $this->redirectTenantLoginOAuthError($request, $tenant, $tenantId, $for, "This Google account is registered under \"{$correct}\". Please select that barangay to log in.");
            }
            $this->syncTenantGoogleUserMirrorToCentral($tenant, $user);

            return $this->redirectToTenantSessionAfterGoogle($request, $tenant, $user);
        }

        if (empty($emailNormalized) || empty($email)) {
            return $this->redirectTenantLoginOAuthError($request, $tenant, $tenantId, $for, 'Google did not provide an email. Please use email and password to sign up.');
        }

        if ($for !== 'resident' || ! $tenantId) {
            if ($for === 'tenant' && $tenantId) {
                return $this->redirectTenantSignUpOAuthStatus($request, $tenant, [
                    'for' => 'tenant',
                    'tenant_id' => $tenantId,
                ], __('This Google account is not registered for this barangay yet. Sign up below (your admin may need to approve your account).'));
            }

            $message = 'No account found for this Google account. Staff: sign up with email first, then you can link Google. Residents: sign up first (Sign up page → choose barangay → Sign up with Google).';

            return $this->redirectTenantLoginOAuthError($request, $tenant, $tenantId, $for, $message);
        }

        $existing = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(email) = ?', [$emailNormalized])
            ->first();
        if ($existing) {
            $existing->update(['google_id' => $googleId]);
            $this->syncTenantGoogleUserMirrorToCentral($tenant, $existing);

            return $this->redirectToTenantSessionAfterGoogle($request, $tenant, $existing);
        }

        if ($intent !== 'signup') {
            return $this->redirectTenantSignUpOAuthStatus($request, $tenant, array_filter([
                'for' => 'resident',
                'tenant_id' => $tenantId,
            ]), __('This Google account is not registered at this barangay yet. Complete sign up below, or use “Sign up with Google” on the sign-up page.'));
        }

        $newUser = User::create([
            'tenant_id' => $tenantId,
            'role' => User::ROLE_RESIDENT,
            'name' => $name,
            'email' => $emailNormalized ?: $email,
            'password' => null,
            'google_id' => $googleId,
        ]);
        $newUser->syncRoles([User::ROLE_RESIDENT]);
        $this->syncTenantGoogleUserMirrorToCentral($tenant, $newUser);

        return $this->redirectToTenantSessionAfterGoogle($request, $tenant, $newUser);
    }

    /**
     * Stay on the current host with relative paths (same as LoginController for tenant users).
     * Avoids intended() and named routes that can point at the central APP_URL, and avoids
     * stale url.intended values from the central app.
     */
    private function redirectTenantUserAfterGoogle(User $user): RedirectResponse
    {
        if ($user->canAccessResidentPortal()) {
            return redirect()->to('/resident');
        }

        return redirect()->to('/backend');
    }

    private function redirectIntended(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()->intended(route('super-admin.dashboard'));
        }
        if ($user->canAccessResidentPortal()) {
            return redirect()->to('/resident');
        }

        return redirect()->to('/backend');
    }

    private function isAllowedAutoProvisionGoogleAdmin(string $emailNormalized): bool
    {
        if ($emailNormalized === '') {
            return false;
        }
        $allowed = config('bhcas.barangay_admin_google_emails', []);
        if (! is_array($allowed)) {
            return false;
        }

        return in_array($emailNormalized, $allowed, true);
    }

    private function ensureTenantAuthTablesExistForGoogleProvisioning(Tenant $tenant): void
    {
        $tenant->run(function (): void {
            $schema = Schema::connection('tenant');

            if (! $schema->hasTable('users')) {
                $schema->create('users', function ($table): void {
                    $table->id();
                    $table->unsignedBigInteger('tenant_id')->nullable()->index();
                    $table->string('role')->default(User::ROLE_RESIDENT);
                    $table->string('name');
                    $table->string('purok_address')->nullable();
                    $table->string('profile_picture')->nullable();
                    $table->string('email');
                    $table->timestamp('email_verified_at')->nullable();
                    $table->string('password')->nullable();
                    $table->rememberToken();
                    $table->string('google_id')->nullable();
                    $table->boolean('is_approved')->default(false);
                    $table->timestamps();
                    $table->unique(['tenant_id', 'email']);
                });
            }

            if (! $schema->hasTable('password_reset_tokens')) {
                $schema->create('password_reset_tokens', function ($table): void {
                    $table->string('email')->primary();
                    $table->string('token');
                    $table->timestamp('created_at')->nullable();
                });
            }

            if (! $schema->hasTable('sessions')) {
                $schema->create('sessions', function ($table): void {
                    $table->string('id')->primary();
                    $table->foreignId('user_id')->nullable()->index();
                    $table->string('ip_address', 45)->nullable();
                    $table->text('user_agent')->nullable();
                    $table->longText('payload');
                    $table->integer('last_activity')->index();
                });
            }
        });
    }

    private function handleGoogleTenantApplicationCallback(
        Request $request,
        string $intent,
        ?string $email,
        string $emailNormalized,
        string $name,
        string $googleId,
        ?string $key,
    ): RedirectResponse {
        if (empty($emailNormalized) || empty($email)) {
            return redirect()
                ->route('tenant-applications.create')
                ->withErrors(['email' => __('Google did not provide an email. Please use email and password to apply.')]);
        }

        if (! is_string($key) || $key === '') {
            return redirect()
                ->route('tenant-applications.create')
                ->withErrors(['email' => __('Invalid or expired application session. Please try “Apply with Google” again.')]);
        }

        $payload = Cache::pull('tenant_application_google:'.$key);
        if (! is_array($payload)) {
            return redirect()
                ->route('tenant-applications.create')
                ->withErrors(['email' => __('Invalid or expired application session. Please try “Apply with Google” again.')]);
        }

        $planId = isset($payload['plan_id']) ? (int) $payload['plan_id'] : null;
        $tenantName = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $barangay = isset($payload['barangay']) ? trim((string) $payload['barangay']) : '';
        $address = isset($payload['address']) ? trim((string) $payload['address']) : null;
        $contact = isset($payload['contact_number']) ? trim((string) $payload['contact_number']) : null;

        if (! $planId || $tenantName === '' || $barangay === '') {
            return redirect()
                ->route('tenant-applications.create')
                ->withErrors(['email' => __('Invalid application details. Please submit again.')]);
        }

        $domain = TenantDomainInput::deriveDomainFromBarangay($barangay);
        if ($domain === '') {
            return redirect()
                ->route('tenant-applications.create')
                ->withErrors(['barangay' => __('Could not derive a website address from the barangay. Please contact support.')]);
        }

        $autoAllowed = $this->isAllowedAutoProvisionGoogleAdmin($emailNormalized);

        $tenantApplication = TenantApplication::query()->create([
            'plan_id' => $planId,
            'name' => $tenantName,
            'barangay' => $barangay,
            'domain' => $domain,
            'address' => $address !== '' ? $address : null,
            'contact_number' => $contact !== '' ? $contact : null,
            'email' => $emailNormalized,
            'status' => $autoAllowed ? TenantApplication::STATUS_APPROVED : TenantApplication::STATUS_PENDING,
            'reviewed_by' => null,
            'reviewed_at' => $autoAllowed ? now() : null,
            'rejection_reason' => null,
        ]);

        if (! $autoAllowed) {
            return redirect()
                ->route('tenant-applications.create')
                ->with('status', __('Thank you. Your barangay application has been submitted. A Super Admin must approve it before your site is created.'));
        }

        try {
            $tenant = app(TenantCreationService::class)->createFromValidatedData([
                'plan_id' => $planId,
                'name' => $tenantName,
                'domain' => $domain,
                'address' => $address !== '' ? $address : null,
                'contact_number' => $contact !== '' ? $contact : null,
                'email' => $emailNormalized,
                'is_active' => true,
                'subscription_ends_at' => null,
            ], $barangay);

            $tenantApplication->update([
                'tenant_id' => $tenant->getTenantKey(),
            ]);

            $this->ensureTenantAuthTablesExistForGoogleProvisioning($tenant);

            $tenantAdminUser = $tenant->run(function () use ($tenant, $emailNormalized, $email, $name, $googleId) {
                $user = User::withoutGlobalScopes()
                    ->where('tenant_id', (int) $tenant->id)
                    ->whereRaw('LOWER(email) = ?', [$emailNormalized])
                    ->first();

                if (! $user) {
                    $user = User::create([
                        'tenant_id' => (int) $tenant->id,
                        'role' => User::ROLE_HEALTH_CENTER_ADMIN,
                        'name' => $name,
                        'email' => $emailNormalized ?: $email,
                        'password' => null,
                        'google_id' => $googleId,
                        'is_approved' => true,
                    ]);
                } else {
                    $user->update([
                        'role' => User::ROLE_HEALTH_CENTER_ADMIN,
                        'name' => $name,
                        'google_id' => $user->google_id ?: $googleId,
                        'is_approved' => true,
                    ]);
                }

                $rolesTable = config('permission.table_names.roles', 'roles');
                $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
                $roleExists = Schema::hasTable($rolesTable)
                    && Role::query()
                        ->where('name', User::ROLE_HEALTH_CENTER_ADMIN)
                        ->where('guard_name', config('auth.defaults.guard', 'web'))
                        ->exists();
                if ($roleExists && Schema::hasTable($modelHasRolesTable)) {
                    $user->syncRoles([User::ROLE_HEALTH_CENTER_ADMIN]);
                }

                return $user;
            });

            $this->syncTenantGoogleUserMirrorToCentral($tenant, $tenantAdminUser);

            return $this->redirectToTenantSessionAfterGoogle($request, $tenant, $tenantAdminUser);
        } catch (\Throwable $e) {
            Log::warning('Auto-provision tenant from Google application failed.', [
                'email' => $emailNormalized,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            $tenantApplication->update([
                'status' => TenantApplication::STATUS_PENDING,
                'reviewed_at' => null,
            ]);

            return redirect()
                ->route('tenant-applications.create')
                ->withErrors(['email' => __('We could not auto-create your barangay site. Your application was saved for Super Admin review.')]);
        }
    }
}
