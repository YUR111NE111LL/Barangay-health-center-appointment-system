<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveTenantApplicationRequest;
use App\Http\Requests\RejectTenantApplicationRequest;
use App\Mail\TenantApplicationRejected;
use App\Mail\TenantSiteReady;
use App\Models\TenantApplication;
use App\Services\TenantCreationService;
use App\Support\TenantDomainInput;
use App\Support\TenantPortalLoginUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TenantApplicationReviewController extends Controller
{
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
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [TenantApplication::STATUS_PENDING])
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
                'reviewed_by' => auth()->id(),
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
            $urls = TenantPortalLoginUrls::forDomain($domain);
            try {
                Mail::mailer(config('mail.default'))->to($freshApplication->email)->send(new TenantSiteReady(
                    $freshApplication->name,
                    $domain,
                    $freshApplication->plan,
                    $urls['staff'],
                    $urls['resident'],
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
            'reviewed_by' => auth()->id(),
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
}
