<?php

use App\Http\Controllers\RequirementsController;
use App\Http\Controllers\Tenant\AppointmentController as BackendAppointmentController;
use App\Http\Controllers\Tenant\BackendDashboardController;
use App\Http\Controllers\Tenant\DashboardLiveUpdateController;
use App\Http\Controllers\Tenant\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

// Keep the old generated landing page available (in case you still need it).
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome.legacy');

Route::get('/requirements', RequirementsController::class)->name('requirements');

Route::get('/apply-for-tenant', [\App\Http\Controllers\TenantApplicationController::class, 'create'])->name('tenant-applications.create');
Route::post('/apply-for-tenant', [\App\Http\Controllers\TenantApplicationController::class, 'store'])
    ->middleware('throttle:12,1')
    ->name('tenant-applications.store');
Route::post('/apply-for-tenant/google', [\App\Http\Controllers\TenantApplicationController::class, 'startGoogle'])
    ->middleware('throttle:12,1')
    ->name('tenant-applications.google.start');

Route::middleware(['tenancy.by_domain_for_auth'])->group(function (): void {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleLoginController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleLoginController::class, 'callback'])->name('auth.google.callback');
    Route::get('/auth/google/tenant-session', [\App\Http\Controllers\Auth\GoogleLoginController::class, 'completeTenantSession'])->name('auth.google.tenant-session');
    Route::get('/auth/email/tenant-session', [\App\Http\Controllers\Auth\GoogleLoginController::class, 'completeEmailTenantSession'])->name('auth.email.tenant-session');
    Route::get('/super-admin/login', fn () => redirect()->route('login', ['for' => 'super-admin']))->name('login.super-admin');
    Route::get('/backend/login', fn () => redirect()->route('login', ['for' => 'tenant']))->name('login.tenant');
    Route::get('/resident/login', fn () => redirect()->route('login', ['for' => 'resident']))->name('login.resident');
    Route::get('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);
    Route::get('/sign-up', [\App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('sign-up');

    Route::get('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
    Route::get('/pending-approval', function () {
        return view('auth.pending-approval');
    })->name('pending-approval');
});

Route::post('/logout', function () {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    // Same host as the current request (do not redirect()->away to domains()->first()): browsing via
    // 127.0.0.1 vs localhost vs a DB domain alias breaks fetch() logout navigation (cross-origin / opaque response).
    return redirect()->to('/login')->with('status', __('You have logged out successfully.'));
})->name('logout')->middleware(['tenancy.by_domain_for_auth', 'auth']);

Route::get('/tenant-custom.css', \App\Http\Controllers\TenantCustomCssController::class)->name('tenant.custom-css')->middleware('auth');

Route::get('/dashboard', function () {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    if ($user->isSuperAdmin()) {
        return redirect()->route('super-admin.dashboard');
    }
    if ($user->canAccessResidentPortal()) {
        return redirect()->route('resident.dashboard');
    }

    return redirect()->route('backend.dashboard');
})->name('dashboard')->middleware(['tenancy.by_domain_for_auth', 'auth']);

Route::middleware(['tenancy.by_domain_for_auth', 'auth', 'tenant'])->prefix('backend')->name('backend.')->group(function (): void {
    Route::get('/', [BackendDashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin', [BackendDashboardController::class, 'admin'])->name('admin.dashboard')->middleware('tenant.barangay_admin');
    Route::get('/nurse', [BackendDashboardController::class, 'nurse'])->name('nurse.dashboard')->middleware('role:Nurse');
    Route::get('/staff', [BackendDashboardController::class, 'staff'])->name('staff.dashboard')->middleware('role:Staff');

    Route::middleware('throttle:90,1')->group(function (): void {
        Route::get('/dashboard/live/summary', [DashboardLiveUpdateController::class, 'summary'])->name('dashboard.live.summary');
        Route::get('/dashboard/live/admin', [DashboardLiveUpdateController::class, 'admin'])->name('dashboard.live.admin')->middleware('tenant.barangay_admin');
        Route::get('/dashboard/live/nurse', [DashboardLiveUpdateController::class, 'nurse'])->name('dashboard.live.nurse')->middleware('role:Nurse');
        Route::get('/dashboard/live/staff', [DashboardLiveUpdateController::class, 'staff'])->name('dashboard.live.staff')->middleware('role:Staff');
    });

    Route::get('profile', [\App\Http\Controllers\Tenant\ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [\App\Http\Controllers\Tenant\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [\App\Http\Controllers\Tenant\ProfileController::class, 'update'])->name('profile.update');

    Route::resource('appointments', BackendAppointmentController::class);
    Route::post('appointments/{appointment}/approve', [BackendAppointmentController::class, 'approve'])->name('appointments.approve');
    Route::post('appointments/{appointment}/reject', [BackendAppointmentController::class, 'reject'])->name('appointments.reject');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('inventory', [\App\Http\Controllers\Tenant\InventoryController::class, 'index'])->name('inventory.index');
    Route::resource('medicines', \App\Http\Controllers\Tenant\MedicineController::class)->except(['show']);

    Route::get('users', [\App\Http\Controllers\Tenant\UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [\App\Http\Controllers\Tenant\UserController::class, 'create'])->name('users.create');
    Route::post('users', [\App\Http\Controllers\Tenant\UserController::class, 'store'])->name('users.store');
    Route::delete('users/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('users/google', [\App\Http\Controllers\Tenant\UserController::class, 'createWithGoogle'])->name('users.google');
    Route::get('users/google/callback', [\App\Http\Controllers\Tenant\UserController::class, 'googleCallback'])->name('users.google.callback');
    Route::post('users/google', [\App\Http\Controllers\Tenant\UserController::class, 'storeWithGoogle'])->name('users.store.google');

    Route::prefix('support')->name('support.')->group(function (): void {
        Route::get('help', [\App\Http\Controllers\Tenant\SupportHelpController::class, 'index'])->name('help');
        Route::get('tickets', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/create', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'reply'])->name('tickets.reply');
        Route::patch('tickets/{ticket}/status', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'updateStatus'])->name('tickets.status');
        Route::delete('tickets/{ticket}', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'destroy'])->name('tickets.destroy');

        Route::get('updates', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'index'])->name('updates.index');
        Route::get('updates/create', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'create'])->name('updates.create');
        Route::post('updates', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'store'])->name('updates.store');
        Route::get('updates/{update}/edit', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'edit'])->name('updates.edit');
        Route::put('updates/{update}', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'update'])->name('updates.update');
        Route::delete('updates/{update}', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'destroy'])->name('updates.destroy');
        Route::post('updates/clear-notification', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'clearNotifications'])->name('updates.clear-notification');
    });

    // RBAC: Barangay (Health Center) Admin only. Nurses and Residents cannot view or access these routes; tenant permissions are plan-based and per-tenant (no overlap with other tenants).
    Route::middleware('tenant.barangay_admin')->group(function (): void {
        Route::get('pending-approvals', [\App\Http\Controllers\Tenant\PendingApprovalsController::class, 'index'])->name('pending-approvals.index');
        Route::put('pending-approvals/{user}', [\App\Http\Controllers\Tenant\PendingApprovalsController::class, 'approve'])->name('pending-approvals.approve');
        Route::match(['delete'], 'pending-approvals/{user}', [\App\Http\Controllers\Tenant\PendingApprovalsController::class, 'deny'])->name('pending-approvals.deny');
        Route::get('rbac', [\App\Http\Controllers\Tenant\RbacController::class, 'index'])->name('rbac.index');
        Route::get('rbac/users/{user}/edit', [\App\Http\Controllers\Tenant\RbacController::class, 'edit'])->name('rbac.edit');
        Route::put('rbac/users/{user}', [\App\Http\Controllers\Tenant\RbacController::class, 'update'])->name('rbac.update');
        Route::get('rbac/permissions', [\App\Http\Controllers\Tenant\RolePermissionsController::class, 'index'])->name('rbac.permissions.index');
        Route::get('rbac/permissions/roles/create', [\App\Http\Controllers\Tenant\RolePermissionsController::class, 'create'])->name('rbac.permissions.create');
        Route::post('rbac/permissions/roles', [\App\Http\Controllers\Tenant\RolePermissionsController::class, 'store'])->name('rbac.permissions.store');
        Route::get('rbac/permissions/roles/{role}', [\App\Http\Controllers\Tenant\RolePermissionsController::class, 'edit'])->name('rbac.permissions.edit');
        Route::put('rbac/permissions/roles/{role}', [\App\Http\Controllers\Tenant\RolePermissionsController::class, 'update'])->name('rbac.permissions.update');
        Route::delete('rbac/permissions/roles/{role}', [\App\Http\Controllers\Tenant\RolePermissionsController::class, 'destroy'])->name('rbac.permissions.destroy');

        Route::get('audit-log', [\App\Http\Controllers\Tenant\AuditLogController::class, 'index'])->name('audit-log.index');

        Route::resource('announcements', \App\Http\Controllers\Tenant\AnnouncementController::class);
        Route::resource('events', \App\Http\Controllers\Tenant\EventController::class);
        Route::resource('services', \App\Http\Controllers\Tenant\ServiceController::class)->except(['show']);

        // Plan-based web customization (only when tenant's plan has web_customization)
        Route::get('customize-web', [\App\Http\Controllers\Tenant\CustomizeWebController::class, 'edit'])->name('customize-web.edit');
        Route::put('customize-web', [\App\Http\Controllers\Tenant\CustomizeWebController::class, 'update'])->name('customize-web.update');
    });
});

// Resident portal: browsing announcements, events, profile, and support does not require "book appointments".
// Only booking actions are gated by that permission (see BookingController and route middleware below).
Route::middleware(['tenancy.by_domain_for_auth', 'auth', 'tenant'])->prefix('resident')->name('resident.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\TenantUser\ResidentController::class, 'dashboard'])->name('dashboard');
    Route::get('/book', [\App\Http\Controllers\TenantUser\BookingController::class, 'create'])->name('book')->middleware('permission:book appointments');
    Route::post('/book', [\App\Http\Controllers\TenantUser\BookingController::class, 'store'])->name('book.store')->middleware('permission:book appointments');
    Route::get('/announcements', [\App\Http\Controllers\TenantUser\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{announcement}', [\App\Http\Controllers\TenantUser\AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/events', [\App\Http\Controllers\TenantUser\EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [\App\Http\Controllers\TenantUser\EventController::class, 'show'])->name('events.show');
    Route::get('/profile', [\App\Http\Controllers\TenantUser\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [\App\Http\Controllers\TenantUser\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\TenantUser\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/medicine', [\App\Http\Controllers\TenantUser\MedicineController::class, 'index'])->name('medicine.index');
    Route::post('/medicine/{medicine}/acquire', [\App\Http\Controllers\TenantUser\MedicineController::class, 'acquire'])
        ->name('medicine.acquire')
        ->middleware(['permission:acquire medicine', 'throttle:30,1']);

    Route::prefix('support')->name('support.')->group(function (): void {
        Route::get('help', [\App\Http\Controllers\Tenant\SupportHelpController::class, 'index'])->name('help');
        Route::get('tickets', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/create', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'reply'])->name('tickets.reply');
        Route::patch('tickets/{ticket}/status', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'updateStatus'])->name('tickets.status');
        Route::delete('tickets/{ticket}', [\App\Http\Controllers\Tenant\SupportTicketController::class, 'destroy'])->name('tickets.destroy');

        Route::get('updates', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'index'])->name('updates.index');
        Route::post('updates/clear-notification', [\App\Http\Controllers\Tenant\ReleaseNoteController::class, 'clearNotifications'])->name('updates.clear-notification');
    });
});

Route::middleware(['auth', 'role:Super Admin'])->prefix('super-admin')->name('super-admin.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('pending-approvals', [\App\Http\Controllers\SuperAdmin\PendingApprovalsController::class, 'index'])->name('pending-approvals.index');
    Route::put('pending-approvals/{user}', [\App\Http\Controllers\SuperAdmin\PendingApprovalsController::class, 'approve'])->name('pending-approvals.approve');
    Route::match(['delete'], 'pending-approvals/{user}', [\App\Http\Controllers\SuperAdmin\PendingApprovalsController::class, 'deny'])->name('pending-approvals.deny');
    Route::get('tenant-applications', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'index'])->name('tenant-applications.index');
    Route::get('tenant-applications/{tenant_application}', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'show'])->name('tenant-applications.show');
    Route::post('tenant-applications/{tenant_application}/approve', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'approve'])->name('tenant-applications.approve');
    Route::post('tenant-applications/{tenant_application}/reject', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'reject'])->name('tenant-applications.reject');
    Route::post('tenant-applications/{tenant_application}/resend-rejection-email', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'resendRejectionEmail'])->name('tenant-applications.resend-rejection-email');
    Route::delete('tenant-applications/{tenant_application}', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'destroy'])->name('tenant-applications.destroy');
    Route::get('support-reports', [\App\Http\Controllers\SuperAdmin\SupportReportController::class, 'index'])->name('support-reports.index');
    Route::get('support-reports/{ticket}/attachment', [\App\Http\Controllers\SuperAdmin\SupportReportController::class, 'viewAttachment'])->name('support-reports.attachment');
    Route::get('support-reports/{ticket}', [\App\Http\Controllers\SuperAdmin\SupportReportController::class, 'show'])->name('support-reports.show');
    Route::patch('support-reports/{ticket}/status', [\App\Http\Controllers\SuperAdmin\SupportReportController::class, 'updateStatus'])->name('support-reports.status');
    Route::get('updates', [\App\Http\Controllers\SuperAdmin\ReleaseNoteController::class, 'index'])->name('updates.index');
    Route::get('updates/create', [\App\Http\Controllers\SuperAdmin\ReleaseNoteController::class, 'create'])->name('updates.create');
    Route::post('updates', [\App\Http\Controllers\SuperAdmin\ReleaseNoteController::class, 'store'])->name('updates.store');
    Route::get('updates/{update}/edit', [\App\Http\Controllers\SuperAdmin\ReleaseNoteController::class, 'edit'])->name('updates.edit');
    Route::put('updates/{update}', [\App\Http\Controllers\SuperAdmin\ReleaseNoteController::class, 'update'])->name('updates.update');
    Route::delete('updates/{update}', [\App\Http\Controllers\SuperAdmin\ReleaseNoteController::class, 'destroy'])->name('updates.destroy');
    Route::post('updates/sync-github', \App\Http\Controllers\SuperAdmin\GitHubReleaseSyncController::class)
        ->middleware('throttle:12,1')
        ->name('updates.sync-github');

    Route::resource('tenants', \App\Http\Controllers\SuperAdmin\TenantManagementController::class);
    Route::get('plans', [\App\Http\Controllers\SuperAdmin\PlanManagementController::class, 'index'])->name('plans.index');
    Route::put('plans/{plan}', [\App\Http\Controllers\SuperAdmin\PlanManagementController::class, 'update'])->name('plans.update');
    Route::post('tenants/{tenant}/provision-database', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'provisionDatabase'])->name('tenants.provision-database');
    Route::patch('tenants/{tenant}/toggle-status', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'toggleStatus'])->name('tenants.toggle-status');
    Route::get('tenant-audit-logs', [\App\Http\Controllers\SuperAdmin\TenantAuditLogController::class, 'directory'])->name('tenant-audit-logs.index');
    Route::get('tenants/{tenant}/audit-log', [\App\Http\Controllers\SuperAdmin\TenantAuditLogController::class, 'index'])->name('tenants.audit-log.index');
    Route::get('tenants/{tenant}/rbac', [\App\Http\Controllers\SuperAdmin\TenantRbacController::class, 'index'])->name('tenants.rbac.index');
    Route::get('tenants/{tenant}/rbac/roles/{role}', [\App\Http\Controllers\SuperAdmin\TenantRbacController::class, 'edit'])->name('tenants.rbac.edit');
    Route::put('tenants/{tenant}/rbac/roles/{role}', [\App\Http\Controllers\SuperAdmin\TenantRbacController::class, 'update'])->name('tenants.rbac.update');
    Route::get('roles', [\App\Http\Controllers\SuperAdmin\RbacController::class, 'index'])->name('rbac.index');
    Route::get('roles/{role}/edit', [\App\Http\Controllers\SuperAdmin\RbacController::class, 'edit'])->name('rbac.edit');
    Route::put('roles/{role}', [\App\Http\Controllers\SuperAdmin\RbacController::class, 'update'])->name('rbac.update');

    // Super Admin user management
    Route::get('users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [\App\Http\Controllers\SuperAdmin\UserController::class, 'create'])->name('users.create');
    Route::post('users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'store'])->name('users.store');
    Route::get('users/google', [\App\Http\Controllers\SuperAdmin\UserController::class, 'createWithGoogle'])->name('users.google');
    Route::get('users/google/callback', [\App\Http\Controllers\SuperAdmin\UserController::class, 'googleCallback'])->name('users.google.callback');
});
