<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveTenantApplicationRequest;
use App\Http\Requests\RejectTenantApplicationRequest;
use App\Mail\TenantApplicationRejected;
use App\Mail\TenantSiteReady;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantCreationService;
use App\Support\TenantDomainInput;
use App\Support\TenantPortalLoginUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class TenantApplicationReviewController extends Controller
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

        return $base.'/auth/email/tenant-session?token='.urlencode($token);
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $query = TenantApplication::query()->with(['plan', 'reviewer']);

        if (is_string($status) && in_array($status, [
            TenantApplication::STATUS_PENDING,
            TenantApplication::STATUS_APPROVED,
            TenantApplication::STATUS_REJECTED,
        ], true)) {
            $query->where('status', $status);
        }

        $applications = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.tenant-applications.index', compact('applications', 'status'));
    }

    public function show(TenantApplication $tenantApplication): View
    {
        $tenantApplication->load(['plan', 'reviewer', 'tenant']);
        $suggestedDomain = TenantDomainInput::deriveDomainFromBarangay($tenantApplication->barangay);

        return view('superadmin.tenant-applications.show', compact('tenantApplication', 'suggestedDomain'));
    }

    public function approve(ApproveTenantApplicationRequest $request, TenantApplication $tenantApplication): RedirectResponse
    {
        if ($tenantApplication->status !== TenantApplication::STATUS_PENDING) {
            return redirect()
                ->route('super-admin.tenant-applications.show', $tenantApplication)
                ->with('error', __('This application has already been reviewed.'));
        }

        $domain = $request->validated()['domain'];

        $createdTenant = null;

        try {
            $validatedPayload = [
                'plan_id' => $tenantApplication->plan_id,
                'name' => $tenantApplication->name,
                'domain' => $domain,
                'address' => $tenantApplication->address,
                'contact_number' => $tenantApplication->contact_number,
                'email' => $tenantApplication->email,
                'is_active' => true,
                'subscription_ends_at' => null,
            ];

            // Do not wrap Tenant::create in DB::transaction(): Stancl fires TenantCreated synchronously,
            // which runs CreateDatabase + MigrateDatabase (DDL). MySQL implicitly commits on DDL and breaks
            // Laravel's transaction with "There is no active transaction". Provisioning is handled by Stancl.
            $createdTenant = app(TenantCreationService::class)->createTenantRecordAndDomain(
                $validatedPayload,
                $tenantApplication->barangay,
            );

            $tenantApplication->update([
                'domain' => $domain,
                'status' => TenantApplication::STATUS_APPROVED,
                'tenant_id' => $createdTenant->getTenantKey(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        } catch (\Throwable $e) {
            return redirect()
                ->route('super-admin.tenant-applications.show', $tenantApplication)
                ->withInput()
                ->with('error', __('Could not create tenant: :message', ['message' => $e->getMessage()]));
        }

        $freshApplication = $tenantApplication->fresh(['plan']);

        $approvalMailSent = false;
        if ($createdTenant !== null && $freshApplication && filled($freshApplication->email)) {
            $this->ensureTenantAuthTablesExist($createdTenant);
            $tenantAdminUserId = $this->createOrUpdateFirstTenantAdminUser($createdTenant, (string) $freshApplication->email, (string) $freshApplication->name);

            $urls = TenantPortalLoginUrls::forDomain($domain);
            $dashboardUrl = $this->dashboardMagicLinkForTenant($createdTenant, $tenantAdminUserId);
            $staffUrl = $dashboardUrl ?: $urls['staff'];

            $centralBaseUrl = rtrim((string) config('bhcas.central_app_url', config('app.url')), '/');
            $centralLoginUrl = $centralBaseUrl.'/login?for=tenant';
            try {
                Mail::mailer(config('mail.default'))->to($freshApplication->email)->send(new TenantSiteReady(
                    $freshApplication->name,
                    $freshApplication->barangay,
                    $domain,
                    $freshApplication->plan,
                    $staffUrl,
                    $dashboardUrl ? $centralLoginUrl : $urls['resident'],
                    __('Your barangay application was approved – :app', ['app' => config('bhcas.name')]),
                ));
                $approvalMailSent = true;
            } catch (\Throwable $mailException) {
                report($mailException);
            }
        }

        $success = __('Tenant approved and created. You can provision the database from the tenant page if needed.');
        if ($freshApplication && filled($freshApplication->email)) {
            $success .= $approvalMailSent
                ? ' '.__('A notification was sent to :email.', ['email' => $freshApplication->email])
                : ' '.__('We could not send email to :email. Check logs and mail configuration.', ['email' => $freshApplication->email]);
        } elseif ($freshApplication && ! filled($freshApplication->email)) {
            $success .= ' '.__('No email was on file for this application; the applicant was not notified by email.');
        }
        if (config('mail.default') === 'log' && config('app.debug')) {
            $success .= ' '.__('Note: MAIL_MAILER is log — emails go to the log file, not an inbox. Use MAIL_MAILER=smtp to send real mail.');
        }

        return redirect()
            ->route('super-admin.tenant-applications.show', $freshApplication ?? $tenantApplication->fresh())
            ->with('success', $success);
    }

    public function reject(RejectTenantApplicationRequest $request, TenantApplication $tenantApplication): RedirectResponse
    {
        if ($tenantApplication->status !== TenantApplication::STATUS_PENDING) {
            return redirect()
                ->route('super-admin.tenant-applications.show', $tenantApplication)
                ->with('error', __('This application has already been reviewed.'));
        }

        $validated = $request->validated();

        $tenantApplication->update([
            'status' => TenantApplication::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        $freshApplication = $tenantApplication->fresh();

        $rejectionMailSent = false;
        if ($freshApplication && filled($freshApplication->email)) {
            try {
                Mail::mailer(config('mail.default'))->to($freshApplication->email)->send(new TenantApplicationRejected(
                    $freshApplication->name,
                    $freshApplication->barangay,
                    $freshApplication->rejection_reason,
                ));
                $rejectionMailSent = true;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $success = __('Application rejected.');
        if ($freshApplication && filled($freshApplication->email)) {
            $success .= $rejectionMailSent
                ? ' '.__('A notification was sent to :email.', ['email' => $freshApplication->email])
                : ' '.__('We could not send email to :email. Check logs and mail configuration.', ['email' => $freshApplication->email]);
        } elseif ($freshApplication && ! filled($freshApplication->email)) {
            $success .= ' '.__('No email was on file for this application; the applicant was not notified by email.');
        }
        if (config('mail.default') === 'log' && config('app.debug')) {
            $success .= ' '.__('Note: MAIL_MAILER is log — emails go to the log file, not an inbox. Use MAIL_MAILER=smtp to send real mail.');
        }

        return redirect()
            ->route('super-admin.tenant-applications.show', $freshApplication)
            ->with('success', $success);
    }

    /**
     * Remove the application record from the list only. Does not delete an existing tenant site.
     */
    public function destroy(Request $request, TenantApplication $tenantApplication): RedirectResponse
    {
        $tenantApplication->delete();

        $query = [];
        $status = $request->input('redirect_status');
        if (is_string($status) && in_array($status, [
            TenantApplication::STATUS_PENDING,
            TenantApplication::STATUS_APPROVED,
            TenantApplication::STATUS_REJECTED,
        ], true)) {
            $query['status'] = $status;
        }

        return redirect()
            ->route('super-admin.tenant-applications.index', $query)
            ->with('success', __('Application record removed. Existing barangay tenants are not affected.'));
    }
}
