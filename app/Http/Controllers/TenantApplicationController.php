<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantApplicationRequest;
use App\Mail\TenantSiteReady;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantCreationService;
use App\Support\Recaptcha;
use App\Support\TenantDomainInput;
use App\Support\TenantPortalLoginUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class TenantApplicationController extends Controller
{
    public function create(): View
    {
        $plans = Plan::query()->orderBy('name')->get();

        return view('tenant-applications.create', compact('plans'));
    }

    public function store(StoreTenantApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (Recaptcha::shouldProcess()) {
            $result = Recaptcha::verifyV3($request, $validated['recaptcha_token'], 'tenant_application');
            if (! $result['ok']) {
                return back()
                    ->withInput($request->except('recaptcha_token'))
                    ->withErrors(['recaptcha_token' => __('reCAPTCHA verification failed. Please try again.')]);
            }
        }

        unset($validated['recaptcha_token']);

        $email = strtolower(trim((string) ($validated['email'] ?? '')));
        $isGmail = $email !== '' && str_ends_with($email, '@gmail.com');
        $autoProvisionAnyEmail = (bool) config('bhcas.auto_provision_tenant_for_any_email_applications', false);
        $autoProvisionGmailOnly = (bool) config('bhcas.auto_provision_tenant_for_gmail_applications', false);

        if ($autoProvisionAnyEmail || ($isGmail && $autoProvisionGmailOnly)) {
            return $this->autoProvisionTenantFromApplication($validated);
        }

        $validated['status'] = TenantApplication::STATUS_PENDING;

        TenantApplication::query()->create($validated);

        return redirect()
            ->route('tenant-applications.create')
            ->with('status', __('Thank you. Your barangay application has been submitted. A Super Admin must approve it before your site is created.'));
    }

    public function startGoogle(StoreTenantApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (Recaptcha::shouldProcess()) {
            $result = Recaptcha::verifyV3($request, $validated['recaptcha_token'], 'tenant_application');
            if (! $result['ok']) {
                return back()
                    ->withInput($request->except('recaptcha_token'))
                    ->withErrors(['recaptcha_token' => __('reCAPTCHA verification failed. Please try again.')]);
            }
        }

        unset($validated['recaptcha_token']);

        $key = (string) Str::uuid();
        Cache::put('tenant_application_google:'.$key, $validated, now()->addMinutes(10));

        return redirect()->route('auth.google.redirect', [
            'for' => 'tenant-application',
            'intent' => 'signup',
            'app_key' => $key,
        ]);
    }

    private function autoProvisionTenantFromApplication(array $validated): RedirectResponse
    {
        $planId = (int) $validated['plan_id'];
        $tenantName = trim((string) $validated['name']);
        $barangay = trim((string) $validated['barangay']);
        $email = strtolower(trim((string) $validated['email']));
        $address = isset($validated['address']) ? trim((string) $validated['address']) : null;
        $contact = isset($validated['contact_number']) ? trim((string) $validated['contact_number']) : null;

        $domain = TenantDomainInput::deriveDomainFromBarangay($barangay);
        if ($domain === '') {
            return back()
                ->withInput()
                ->withErrors(['barangay' => __('Could not derive a website address from the barangay. Please contact support.')]);
        }

        $tenantApplication = TenantApplication::query()->create([
            'plan_id' => $planId,
            'name' => $tenantName,
            'barangay' => $barangay,
            'domain' => $domain,
            'address' => $address !== '' ? $address : null,
            'contact_number' => $contact !== '' ? $contact : null,
            'email' => $email,
            'status' => TenantApplication::STATUS_APPROVED,
            'reviewed_by' => null,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        try {
            $tenant = app(TenantCreationService::class)->createFromValidatedData([
                'plan_id' => $planId,
                'name' => $tenantName,
                'domain' => $domain,
                'address' => $address !== '' ? $address : null,
                'contact_number' => $contact !== '' ? $contact : null,
                'email' => $email,
                'is_active' => true,
                'subscription_ends_at' => null,
            ], $barangay);

            $tenantApplication->update([
                'tenant_id' => $tenant->getTenantKey(),
            ]);

            $this->ensureTenantAuthTablesExist($tenant);

            $tenantAdminUserId = $tenant->run(function () use ($tenant, $email, $tenantName): int {
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

            $this->sendTenantReadyEmail($tenantApplication, $tenant, $tenantAdminUserId, $domain);

            return redirect()
                ->route('tenant-applications.create')
                ->with('status', __('Your barangay site is ready. We created your Barangay Admin account automatically using the email you entered. Please check your email for the dashboard link.'));
        } catch (\Throwable $e) {
            Log::warning('Auto-provision tenant from application failed.', [
                'email' => $email,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            $tenantApplication->update([
                'status' => TenantApplication::STATUS_PENDING,
                'reviewed_at' => null,
            ]);

            return redirect()
                ->route('tenant-applications.create')
                ->with('status', __('Thank you. Your barangay application has been submitted. A Super Admin must approve it before your site is created.'));
        }
    }

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

    private function sendTenantReadyEmail(TenantApplication $application, Tenant $tenant, int $tenantAdminUserId, string $domain): void
    {
        if (! filled($application->email)) {
            return;
        }

        $plan = Plan::query()->find($application->plan_id);
        $urls = TenantPortalLoginUrls::forDomain($domain);

        $token = bin2hex(random_bytes(32));
        Cache::put('email_sso:'.$token, [
            'tenant_id' => (int) $tenant->id,
            'user_id' => $tenantAdminUserId,
        ], now()->addMinutes(30));

        $scheme = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = ($port && ! in_array((int) $port, [80, 443], true)) ? ':'.$port : '';
        $base = $scheme.'://'.$domain.$portSuffix;
        $dashboardUrl = $base.'/auth/email/tenant-session?token='.urlencode($token).'&portal=staff';
        $centralBaseUrl = rtrim((string) config('bhcas.central_app_url', config('app.url')), '/');
        $centralLoginUrl = $centralBaseUrl.'/login?for=tenant';

        try {
            Mail::mailer(config('mail.default'))->to($application->email)->send(new TenantSiteReady(
                $application->name,
                $application->barangay,
                $domain,
                $plan,
                $dashboardUrl,
                $centralLoginUrl,
                __('Your barangay site is ready – :app', ['app' => config('bhcas.name')]),
            ));
        } catch (\Throwable $mailException) {
            report($mailException);
        }
    }
}
