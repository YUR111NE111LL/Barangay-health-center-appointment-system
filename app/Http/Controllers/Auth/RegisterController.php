<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Recaptcha;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /**
     * Roles available to tenant sign-up.
     *
     * @return array<int, string>
     */
    private function allowedTenantRolesForSignup(?int $tenantId): array
    {
        $baseRoles = [
            User::ROLE_RESIDENT,
            User::ROLE_STAFF,
            User::ROLE_NURSE,
            User::ROLE_HEALTH_CENTER_ADMIN,
        ];

        if (! $tenantId || ! Schema::hasTable('tenant_role_permissions')) {
            return $baseRoles;
        }

        $customRoles = DB::table('tenant_role_permissions')
            ->where('tenant_id', $tenantId)
            ->whereNotIn('role_name', array_merge($baseRoles, [User::ROLE_SUPER_ADMIN]))
            ->distinct()
            ->orderBy('role_name')
            ->pluck('role_name')
            ->filter(fn ($role) => is_string($role) && trim($role) !== '')
            ->map(fn ($role) => trim((string) $role))
            ->values()
            ->toArray();

        return array_values(array_unique(array_merge($baseRoles, $customRoles)));
    }

    private function ensureTenantAuthTables(Tenant $tenant): void
    {
        $tenant->run(function (): void {
            if (! Schema::hasTable('users')) {
                Schema::create('users', function ($table): void {
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

            if (! Schema::hasTable('password_reset_tokens')) {
                Schema::create('password_reset_tokens', function ($table): void {
                    $table->string('email')->primary();
                    $table->string('token');
                    $table->timestamp('created_at')->nullable();
                });
            }

            if (! Schema::hasTable('sessions')) {
                Schema::create('sessions', function ($table): void {
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

    public function showRegistrationForm(): View|RedirectResponse
    {
        $request = request();

        if ($request->filled('oauth_flash')) {
            $payload = Cache::pull('oauth_register_flash:'.$request->query('oauth_flash'));
            if (is_array($payload) && isset($payload['status'])) {
                return redirect()->route('sign-up', $request->except('oauth_flash'))
                    ->with('status', $payload['status']);
            }
        }

        $tenants = Tenant::with('domains')->where('is_active', true)->orderBy('name')->get();
        $currentTenant = tenant();
        if (! $currentTenant && ! $request->filled('for')) {
            $request->query->set('for', 'super-admin');
        }
        $tenantRoleOptions = [];
        foreach ($tenants as $tenant) {
            $tenantRoleOptions[(int) $tenant->id] = $this->allowedTenantRolesForSignup((int) $tenant->id);
        }

        return view('auth.register', compact('tenants', 'currentTenant', 'tenantRoleOptions'));
    }

    public function register(Request $request): RedirectResponse
    {
        $currentTenant = tenant();
        if (! $currentTenant) {
            // Central sign-up is strictly for Super Admin accounts.
            $request->merge([
                'for' => 'super-admin',
                'role' => User::ROLE_SUPER_ADMIN,
                'tenant_id' => null,
            ]);
        }
        $tenantId = $request->input('tenant_id');
        $tenant = $currentTenant ?: ($tenantId ? Tenant::query()->find($tenantId) : null);

        $requestedRole = (string) $request->input('role', '');
        $isSuperAdminSignup = $requestedRole === User::ROLE_SUPER_ADMIN;
        $tenantIdForUnique = $tenant ? (int) $tenant->id : null;

        $doRegister = function () use ($request, $isSuperAdminSignup, $tenantIdForUnique, $currentTenant): RedirectResponse {
            if ($isSuperAdminSignup) {
                $emailNormalized = strtolower((string) $request->input('email', ''));
                $alreadyUnderTenant = User::withoutGlobalScopes()
                    ->whereNotNull('tenant_id')
                    ->whereRaw('LOWER(email) = ?', [$emailNormalized])
                    ->exists();
                if ($alreadyUnderTenant) {
                    $message = 'This email is already used by a barangay tenant account. Please use your barangay sign in/sign up page. If you need your own barangay portal, submit Apply for tenant.';

                    return back()
                        ->withInput($request->only('name', 'email'))
                        ->with('auth_scope_alert', $message)
                        ->withErrors(['email' => $message]);
                }
            }

            $allowedRoles = $isSuperAdminSignup
                ? [User::ROLE_SUPER_ADMIN]
                : $this->allowedTenantRolesForSignup($tenantIdForUnique);

            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'confirmed', Password::defaults()],
                'role' => ['required', Rule::in($allowedRoles)],
            ];

            // Validate tenant_id against central DB (tenancy might be initialized to tenant DB when this runs).
            $rules['tenant_id'] = [
                $isSuperAdminSignup ? 'nullable' : 'required',
                function (string $attribute, mixed $value, \Closure $fail) use ($isSuperAdminSignup, $currentTenant): void {
                    if ($isSuperAdminSignup) {
                        return;
                    }

                    if ($value === null || $value === '') {
                        $fail('The selected '.$attribute.' is invalid.');

                        return;
                    }

                    if ($currentTenant && (int) $value !== (int) $currentTenant->id) {
                        $fail('The selected '.$attribute.' is invalid.');

                        return;
                    }

                    $exists = Tenant::query()->whereKey((int) $value)->exists();
                    if (! $exists) {
                        $fail('The selected '.$attribute.' is invalid.');
                    }
                },
            ];

            $rules['email'][] = Rule::unique('users')->where('tenant_id', $tenantIdForUnique);

            if (Recaptcha::shouldProcess()) {
                $rules['recaptcha_token'] = ['required', 'string'];
            }

            $validated = $request->validate($rules);

            if ($currentTenant && ! $isSuperAdminSignup) {
                // Never allow a tenant-domain signup to drift to another tenant ID.
                $validated['tenant_id'] = (int) $currentTenant->id;
            }

            if (Recaptcha::shouldProcess()) {
                $result = Recaptcha::verifyV3($request, $validated['recaptcha_token'], 'register');
                if (! $result['ok']) {
                    return back()
                        ->withInput($request->only('name', 'email', 'tenant_id', 'role'))
                        ->withErrors(['email' => __('reCAPTCHA verification failed. Please try again.')]);
                }
            }

            $validated['password'] = Hash::make($validated['password']);
            if ($isSuperAdminSignup) {
                // Keep Super Admin accounts tenant-less, so `User::isSuperAdmin()` works reliably.
                $validated['tenant_id'] = null;
            }
            $requiresApproval = in_array($validated['role'], User::rolesRequiringApproval(), true);
            if ($requiresApproval) {
                $validated['is_approved'] = false;
            }
            unset($validated['recaptcha_token']);

            $user = User::create($validated);
            $rolesTable = config('permission.table_names.roles', 'roles');
            $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
            $roleExists = Schema::hasTable($rolesTable)
                && Role::query()
                    ->where('name', $validated['role'])
                    ->where('guard_name', config('auth.defaults.guard', 'web'))
                    ->exists();
            if ($roleExists && Schema::hasTable($modelHasRolesTable)) {
                $user->syncRoles([$validated['role']]);
            }

            event(new Registered($user));

            // Keep tenant data isolated: tenant accounts live in tenant DB.
            // For roles that require Super Admin approval, mirror to central so
            // Super Admin can still review pending approvals in one place.
            if (
                $currentTenant
                && in_array($validated['role'], User::rolesApprovedBySuperAdmin(), true)
            ) {
                tenancy()->end();
                $centralUser = User::withoutGlobalScopes()
                    ->where('tenant_id', $validated['tenant_id'])
                    ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
                    ->first();

                if (! $centralUser) {
                    User::withoutGlobalScopes()->create([
                        'tenant_id' => $validated['tenant_id'],
                        'role' => $validated['role'],
                        'name' => $validated['name'],
                        'purok_address' => $validated['purok_address'] ?? null,
                        'profile_picture' => $validated['profile_picture'] ?? null,
                        'email' => $validated['email'],
                        'password' => $validated['password'],
                        'google_id' => $validated['google_id'] ?? null,
                        'is_approved' => false,
                    ]);
                } else {
                    $centralUser->update([
                        'role' => $validated['role'],
                        'name' => $validated['name'],
                        'purok_address' => $validated['purok_address'] ?? null,
                        'profile_picture' => $validated['profile_picture'] ?? null,
                        'password' => $validated['password'],
                        'google_id' => $validated['google_id'] ?? null,
                        'is_approved' => false,
                    ]);
                }

                tenancy()->initialize($currentTenant);
            }

            if ($requiresApproval) {
                return redirect()->route('pending-approval')
                    ->with('status', 'Your account has been created. An admin must approve it before you can log in.');
            }

            Auth::login($user);
            if ($user->role === User::ROLE_RESIDENT) {
                return redirect()->route('resident.dashboard');
            }

            return redirect()->route('backend.dashboard');
        };

        // If sign-up is started from the central site, explicitly initialize tenancy
        // so the user is created inside the barangay's own tenant database.
        if ($tenant && ! $isSuperAdminSignup) {
            $this->ensureTenantAuthTables($tenant);

            return $tenant->run($doRegister);
        }

        return $doRegister();
    }
}
