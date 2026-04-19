<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\TenantSiteReady;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Rules\TenantContactEmailUniqueInCentral;
use App\Services\SeedTenantInitialData;
use App\Services\TenantCreationService;
use App\Support\TenantPortalLoginUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Database\Models\Domain;

class TenantManagementController extends Controller
{
    private function ensureTenantAuthTablesExist(Tenant $tenant): void
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

    private function createOrUpdateFirstTenantAdminUser(Tenant $tenant, string $email, string $tenantName): int
    {
        $email = strtolower(trim($email));

        return $tenant->run(function () use ($tenant, $email, $tenantName): int {
            $user = User::withoutGlobalScopes()
                ->where('tenant_id', (int) $tenant->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if (! $user) {
                $user = User::create([
                    'tenant_id' => (int) $tenant->id,
                    'role' => User::ROLE_HEALTH_CENTER_ADMIN,
                    'name' => $tenantName,
                    'email' => $email,
                    'password' => null,
                    'google_id' => null,
                    'is_approved' => true,
                ]);
            } else {
                $user->update([
                    'role' => User::ROLE_HEALTH_CENTER_ADMIN,
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

            return (int) $user->id;
        });
    }

    private function dashboardMagicLinkForTenant(Tenant $tenant, int $tenantAdminUserId): ?string
    {
        $domain = $tenant->domains()->first()?->domain;
        if (! is_string($domain) || $domain === '') {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        Cache::put('email_sso:'.$token, [
            'tenant_id' => (int) $tenant->id,
            'user_id' => $tenantAdminUserId,
        ], now()->addMinutes(30));

        $scheme = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = ($port && ! in_array((int) $port, [80, 443], true)) ? ':'.$port : '';
        $base = $scheme.'://'.$domain.$portSuffix;

        return $base.'/auth/email/tenant-session?token='.urlencode($token).'&portal=staff';
    }

    /**
     * Normalize a user-entered domain so the stored value is uniform.
     * Examples:
     * - https://brgy-sumpong.test/ -> brgy-sumpong.test
     * - brgy-sumpong.test:8000 -> brgy-sumpong.test
     */
    private function normalizeDomain(string $input): string
    {
        $domain = trim($input);

        if ($domain === '') {
            return '';
        }

        // Remove scheme if the user included it.
        $domain = preg_replace('#^https?://#i', '', $domain) ?: $domain;

        // Remove path/query/hash if the user pasted a full URL.
        // Use a safe regex: strip from the first occurrence of `/`, `?`, or `#`.
        // (Previously this used `#...#` delimiters with a `#` inside a character class,
        // which can trigger "Unknown modifier" errors.)
        $domain = preg_replace('/[\/\?#].*$/', '', $domain) ?: $domain;
        $domain = rtrim($domain, '.');

        // Remove port (host:port) for typical IPv4/domain inputs.
        // Note: For bracketed IPv6 like [::1]:8000 we keep only the host.
        if (str_starts_with($domain, '[') && str_contains($domain, ']')) {
            $endBracketPos = strpos($domain, ']');
            $host = substr($domain, 1, $endBracketPos - 1);

            return Str::lower(trim($host));
        }

        if (substr_count($domain, ':') === 1) {
            $colonPos = strrpos($domain, ':');
            $host = substr($domain, 0, $colonPos);
            $port = substr($domain, $colonPos + 1);

            if (ctype_digit($port)) {
                $domain = $host;
            }
        }

        return Str::lower(trim($domain));
    }

    /**
     * Derive a default tenant domain from a barangay input.
     * If the input already looks like a domain (contains "."), keep it.
     * Otherwise generate: "{slug}.test".
     */
    private function deriveDomainFromBarangay(string $barangay): string
    {
        $raw = trim($barangay);
        if ($raw === '') {
            return '';
        }

        // If they provided a full domain/url, keep it.
        if (str_contains($raw, '.') || str_contains($raw, '://')) {
            return $this->normalizeDomain($raw);
        }

        $slug = Str::slug($raw);
        if ($slug === '') {
            return '';
        }

        $root = (string) config('bhcas.tenant_domain_root', 'localhost');

        return $this->normalizeDomain($slug.'.'.$root);
    }

    public function index(): View
    {
        $tenants = Tenant::with('plan', 'domains')->orderBy('name')->paginate(15);

        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        $plans = Plan::orderBy('name')->get();

        return view('superadmin.tenants.create', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            // Backward-compatible: if `domain` is blank/empty, derive it from the new `barangay` field.
            $normalizedDomain = $this->normalizeDomain((string) $request->input('domain', ''));
            if ($normalizedDomain === '' && $request->filled('barangay')) {
                $normalizedDomain = $this->deriveDomainFromBarangay((string) $request->input('barangay'));
            }

            $request->merge([
                'domain' => $normalizedDomain,
            ]);

            $validated = $request->validate([
                'plan_id' => ['required', 'exists:plans,id'],
                'name' => ['required', 'string', 'max:255'],
                'barangay' => ['required', 'string', 'max:255'],
                'domain' => ['nullable', 'string', 'max:255', 'unique:domains,domain'],
                'address' => ['nullable', 'string'],
                'contact_number' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255', new TenantContactEmailUniqueInCentral],
                'is_active' => ['boolean'],
                'subscription_ends_at' => ['nullable', 'date'],
            ]);

            if (($validated['domain'] ?? '') === '') {
                return back()
                    ->withInput()
                    ->withErrors([
                        'barangay' => 'Could not derive a valid domain from the barangay name.',
                    ]);
            }
            $validated['is_active'] = $request->boolean('is_active');

            $barangaySlugSource = (string) ($request->input('barangay') ?: $validated['name']);

            $tenant = app(TenantCreationService::class)->createFromValidatedData($validated, $barangaySlugSource);
            $tenant->load('plan', 'domains');

            $domain = $tenant->domains->first()?->domain;
            if ($domain !== null && filled($validated['email'] ?? null)) {
                $urls = TenantPortalLoginUrls::forDomain($domain);
                $this->ensureTenantAuthTablesExist($tenant);
                $tenantAdminUserId = $this->createOrUpdateFirstTenantAdminUser($tenant, (string) $validated['email'], (string) $tenant->name);
                $dashboardUrl = $this->dashboardMagicLinkForTenant($tenant, $tenantAdminUserId);
                $staffUrl = $dashboardUrl ?: $urls['staff'];
                $centralBaseUrl = rtrim((string) config('bhcas.central_app_url', config('app.url')), '/');
                $centralLoginUrl = $centralBaseUrl.'/login?for=tenant';
                try {
                    Mail::mailer(config('mail.default'))->to((string) $validated['email'])->send(new TenantSiteReady(
                        $tenant->name,
                        $request->filled('barangay') ? (string) $request->input('barangay') : $tenant->name,
                        $domain,
                        $tenant->plan,
                        $staffUrl,
                        $dashboardUrl ? $centralLoginUrl : $urls['resident'],
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $message = __('Tenant created.');
            if (filled($validated['email'] ?? null)) {
                $message .= ' '.__('A welcome email was sent to :email. We also created the first Barangay Admin account using this email.', ['email' => $validated['email']]);
            }
            if (config('mail.default') === 'log' && config('app.debug')) {
                $message .= ' '.__('Note: MAIL_MAILER is log — emails go to the log file, not an inbox. Use MAIL_MAILER=smtp to send real mail.');
            }

            return redirect()->route('super-admin.tenants.index')->with('success', $message);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'tenant' => 'Tenant creation failed: '.$e->getMessage(),
                ]);
        }
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load('plan', 'domains');
        $tenant->loadCount(['users', 'appointments']);
        $tenant->load(['users' => fn ($q) => $q->orderBy('role')->orderBy('name')]);

        return view('superadmin.tenants.show', compact('tenant'));
    }

    /**
     * Provision the tenant database if it doesn't exist yet.
     *
     * This is safe/idempotent (only creates DB if missing, then runs tenant migrations).
     */
    public function provisionDatabase(Tenant $tenant): RedirectResponse
    {
        try {
            $tenantDatabaseName = $tenant->database()->getName();
            $tenantDatabaseManager = $tenant->database()->manager();

            if (! $tenantDatabaseManager->databaseExists($tenantDatabaseName)) {
                $tenant->database()->makeCredentials();
                $tenantDatabaseManager->createDatabase($tenant);
            }

            // Try tenant migrations, but keep DB provisioning successful even if migration set is incompatible.
            try {
                Artisan::call('tenants:migrate', [
                    '--tenants' => [$tenant->getTenantKey()],
                ]);
            } catch (\Throwable $e) {
                return back()->with('success', 'Tenant database was created. Tenant migrations failed, so this tenant may need migration cleanup before full feature use.');
            }

            try {
                (new SeedTenantInitialData($tenant))->handle();
            } catch (\Throwable $e) {
                report($e);

                return back()->with('success', 'Tenant database provisioned and migrated. Initial roles and services could not be seeded automatically; open Role permissions or contact support if features are missing.');
            }

            return back()->with('success', 'Tenant database provisioned successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'tenant' => 'Tenant database provisioning failed: '.$e->getMessage(),
            ]);
        }
    }

    public function edit(Tenant $tenant): View
    {
        $tenant->load('plan', 'domains');
        $plans = Plan::orderBy('name')->get();

        return view('superadmin.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->merge([
            'domain' => $this->normalizeDomain((string) $request->input('domain', '')),
        ]);

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', Rule::unique('domains', 'domain')->ignore($tenant->domains()->first())],
            'address' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', new TenantContactEmailUniqueInCentral($tenant->id)],
            'is_active' => ['boolean'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $tenant->update([
            'plan_id' => $validated['plan_id'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $validated['is_active'],
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
        ]);

        $primary = $tenant->domains()->first();
        if ($primary instanceof Domain) {
            $primary->update(['domain' => Str::lower($validated['domain'])]);
        } else {
            $tenant->domains()->create(['domain' => Str::lower($validated['domain'])]);
        }

        $message = $validated['is_active']
            ? 'Tenant activated successfully. All users can now access the system.'
            : 'Tenant deactivated successfully. All users have been blocked from accessing the system.';

        return redirect()->route('super-admin.tenants.show', $tenant)->with('success', $message);
    }

    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        $message = $tenant->is_active
            ? 'Tenant activated successfully. All users can now access the system.'
            : 'Tenant deactivated successfully. All users have been blocked from accessing the system.';

        return redirect()->route('super-admin.tenants.show', $tenant)->with('success', $message);
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        try {
            // Remove central-domain records first (data still gets deleted by the Stancl tenancy
            // `TenantDeleted` job, which provisions the tenant DB deletion).
            $tenant->domains()->delete();
            $tenant->delete();

            // Also remove any tenant application rows pointing at this tenant (central DB).
            TenantApplication::query()
                ->where('tenant_id', $tenant->getTenantKey())
                ->delete();

            // Remove central mirrored users for this tenant only (tenant DB users remain inside tenant DB).
            User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->getTenantKey())
                ->delete();
        } catch (\Throwable $e) {
            return redirect()
                ->route('super-admin.tenants.show', $tenant)
                ->with('error', 'Failed to delete tenant: '.$e->getMessage());
        }

        return redirect()
            ->route('super-admin.tenants.index')
            ->with('success', 'Tenant deleted successfully (tenant database removed).');
    }
}
