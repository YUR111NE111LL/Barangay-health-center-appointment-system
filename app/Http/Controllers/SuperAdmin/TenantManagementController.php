<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stancl\Tenancy\Database\Models\Domain;

class TenantManagementController extends Controller
{
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

    /**
     * Ensure the tenant database name is a valid/simple identifier for DB creation.
     * Stancl tenancy uses this value directly for the tenant database manager.
     */
    private function sanitizeTenantDatabaseName(string $dbName): string
    {
        $dbName = trim($dbName);
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $dbName) ?: '';

        // MySQL database names should be <= 64 chars.
        if (strlen($dbName) > 64) {
            $dbName = substr($dbName, 0, 64);
        }

        // Ensure it starts with a letter to avoid edge-case SQL dialect rules.
        if ($dbName !== '' && ! preg_match('/^[a-zA-Z]/', $dbName)) {
            $dbName = 'tenant_'.$dbName;
            if (strlen($dbName) > 64) {
                $dbName = substr($dbName, 0, 64);
            }
        }

        return $dbName;
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
                'domain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
                'address' => ['nullable', 'string'],
                'contact_number' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email'],
                'is_active' => ['boolean'],
                'subscription_ends_at' => ['nullable', 'date'],
            ]);
            $validated['is_active'] = $request->boolean('is_active');

            $plan = Plan::findOrFail($validated['plan_id']);
            $planSlug = $plan->slug ? Str::slug((string) $plan->slug) : Str::slug((string) $plan->name);
            // Tenant DB name should be based on the barangay input (fallback to "name" for backward-compat).
            $barangaySlugSource = $request->input('barangay') ?: $validated['name'];
            $barangaySlug = Str::slug((string) $barangaySlugSource);
            $domainSlug = Str::slug($validated['domain']);

            // Tenant DB name must be safe and must avoid collisions if a tenant is deleted
            // but the previous tenant database remains (or a previous migration partially ran).
            // We intentionally add a random segment so new tenant creations get a fresh DB.
            $randomDbSegment = Str::lower(Str::random(6));
            $dbName = str_replace('-', '_', 'tenant_'.$randomDbSegment.'_'.$planSlug.'_'.$barangaySlug.'_'.$domainSlug);
            $dbName = $this->sanitizeTenantDatabaseName($dbName);

            $tenant = Tenant::create([
                'plan_id' => $validated['plan_id'],
                'name' => $validated['name'],
                'address' => $validated['address'] ?? null,
                'contact_number' => $validated['contact_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'is_active' => $validated['is_active'],
                'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
                'data' => [
                    // Stancl tenancy reads `data.tenancy_db_name` when creating/migrating tenant DB.
                    'tenancy_db_name' => $dbName,
                ],
            ]);

            $tenant->domains()->create([
                'domain' => Str::lower($validated['domain']),
            ]);

            // Safety net: ensure tenant DB exists even if event listeners were skipped.
            $tenantDatabaseName = $tenant->database()->getName();
            $tenantDatabaseManager = $tenant->database()->manager();
            if (! $tenantDatabaseManager->databaseExists($tenantDatabaseName)) {
                $tenant->database()->makeCredentials();
                $tenantDatabaseManager->createDatabase($tenant);
            }

            return redirect()->route('super-admin.tenants.index')->with('success', 'Tenant created.');
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
            'email' => ['nullable', 'email'],
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
