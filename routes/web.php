<?php

use App\Http\Controllers\Backend\AppointmentController as BackendAppointmentController;
use App\Http\Controllers\Backend\BackendDashboardController;
use App\Http\Controllers\Backend\ReportController;
use App\Http\Controllers\RequirementsController;
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

Route::middleware(['tenancy.by_domain_for_auth'])->group(function (): void {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleLoginController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleLoginController::class, 'callback'])->name('auth.google.callback');
    Route::get('/auth/google/tenant-session', [\App\Http\Controllers\Auth\GoogleLoginController::class, 'completeTenantSession'])->name('auth.google.tenant-session');
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
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    $message = __('You have logged out successfully.');

    if ($user && $user->tenant_id) {
        $tenant = \App\Models\Tenant::find($user->tenant_id);
        $domain = $tenant?->domains()->first()?->domain;
        if ($domain) {
            $scheme = request()->getScheme();
            $port = request()->getPort();
            $portSuffix = ($port && ! in_array((int) $port, [80, 443], true)) ? ':'.$port : '';

            return redirect()->away($scheme.'://'.$domain.$portSuffix.'/login')->with('status', $message);
        }
    }

    return redirect()->route('login')->with('status', $message);
})->name('logout')->middleware(['tenancy.by_domain_for_auth', 'auth']);

Route::get('/tenant-custom.css', \App\Http\Controllers\TenantCustomCssController::class)->name('tenant.custom-css')->middleware('auth');

Route::get('/dashboard', function () {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    if ($user->isSuperAdmin()) {
        return redirect()->route('super-admin.dashboard');
    }
    if ($user->role === 'Resident') {
        return redirect()->route('resident.dashboard');
    }

    return redirect()->route('backend.dashboard');
})->name('dashboard')->middleware(['tenancy.by_domain_for_auth', 'auth']);

Route::middleware(['tenancy.by_domain_for_auth', 'auth', 'tenant'])->prefix('backend')->name('backend.')->group(function (): void {
    Route::get('/', [BackendDashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin', [BackendDashboardController::class, 'admin'])->name('admin.dashboard')->middleware('role:Health Center Admin');
    Route::get('/nurse', [BackendDashboardController::class, 'nurse'])->name('nurse.dashboard')->middleware('role:Nurse');
    Route::get('/staff', [BackendDashboardController::class, 'staff'])->name('staff.dashboard')->middleware('role:Staff');

    Route::get('profile', [\App\Http\Controllers\Backend\ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [\App\Http\Controllers\Backend\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [\App\Http\Controllers\Backend\ProfileController::class, 'update'])->name('profile.update');

    Route::resource('appointments', BackendAppointmentController::class);
    Route::post('appointments/{appointment}/approve', [BackendAppointmentController::class, 'approve'])->name('appointments.approve');
    Route::post('appointments/{appointment}/reject', [BackendAppointmentController::class, 'reject'])->name('appointments.reject');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('inventory', [\App\Http\Controllers\Backend\InventoryController::class, 'index'])->name('inventory.index');

    Route::get('users', [\App\Http\Controllers\Backend\UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [\App\Http\Controllers\Backend\UserController::class, 'create'])->name('users.create');
    Route::post('users', [\App\Http\Controllers\Backend\UserController::class, 'store'])->name('users.store');
    Route::get('users/google', [\App\Http\Controllers\Backend\UserController::class, 'createWithGoogle'])->name('users.google');
    Route::get('users/google/callback', [\App\Http\Controllers\Backend\UserController::class, 'googleCallback'])->name('users.google.callback');
    Route::post('users/google', [\App\Http\Controllers\Backend\UserController::class, 'storeWithGoogle'])->name('users.store.google');

    // RBAC: Barangay (Health Center) Admin only. Nurses and Residents cannot view or access these routes; tenant permissions are plan-based and per-tenant (no overlap with other tenants).
    Route::middleware('role:Health Center Admin')->group(function (): void {
        Route::get('pending-approvals', [\App\Http\Controllers\Backend\PendingApprovalsController::class, 'index'])->name('pending-approvals.index');
        Route::put('pending-approvals/{user}', [\App\Http\Controllers\Backend\PendingApprovalsController::class, 'approve'])->name('pending-approvals.approve');
        Route::match(['delete'], 'pending-approvals/{user}', [\App\Http\Controllers\Backend\PendingApprovalsController::class, 'deny'])->name('pending-approvals.deny');
        Route::get('rbac', [\App\Http\Controllers\Backend\RbacController::class, 'index'])->name('rbac.index');
        Route::get('rbac/users/{user}/edit', [\App\Http\Controllers\Backend\RbacController::class, 'edit'])->name('rbac.edit');
        Route::put('rbac/users/{user}', [\App\Http\Controllers\Backend\RbacController::class, 'update'])->name('rbac.update');
        Route::get('rbac/permissions', [\App\Http\Controllers\Backend\RolePermissionsController::class, 'index'])->name('rbac.permissions.index');
        Route::get('rbac/permissions/roles/{role}', [\App\Http\Controllers\Backend\RolePermissionsController::class, 'edit'])->name('rbac.permissions.edit');
        Route::put('rbac/permissions/roles/{role}', [\App\Http\Controllers\Backend\RolePermissionsController::class, 'update'])->name('rbac.permissions.update');

        Route::resource('announcements', \App\Http\Controllers\Backend\AnnouncementController::class);
        Route::resource('events', \App\Http\Controllers\Backend\EventController::class);
        Route::resource('services', \App\Http\Controllers\Backend\ServiceController::class)->except(['show']);

        // Plan-based web customization (only when tenant's plan has web_customization)
        Route::get('customize-web', [\App\Http\Controllers\Backend\CustomizeWebController::class, 'edit'])->name('customize-web.edit');
        Route::put('customize-web', [\App\Http\Controllers\Backend\CustomizeWebController::class, 'update'])->name('customize-web.update');
    });
});

Route::middleware(['tenancy.by_domain_for_auth', 'auth', 'tenant', 'role:Resident'])->prefix('resident')->name('resident.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\Frontend\ResidentController::class, 'dashboard'])->name('dashboard');
    Route::get('/book', [\App\Http\Controllers\Frontend\BookingController::class, 'create'])->name('book')->middleware('permission:book appointments');
    Route::post('/book', [\App\Http\Controllers\Frontend\BookingController::class, 'store'])->name('book.store')->middleware('permission:book appointments');
    Route::get('/announcements', [\App\Http\Controllers\Frontend\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{announcement}', [\App\Http\Controllers\Frontend\AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/events', [\App\Http\Controllers\Frontend\EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [\App\Http\Controllers\Frontend\EventController::class, 'show'])->name('events.show');
    Route::get('/profile', [\App\Http\Controllers\Frontend\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [\App\Http\Controllers\Frontend\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\Frontend\ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:Super Admin'])->prefix('super-admin')->name('super-admin.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('accounts', [\App\Http\Controllers\SuperAdmin\SuperAdminController::class, 'accounts'])->name('accounts.index');
    Route::get('pending-approvals', [\App\Http\Controllers\SuperAdmin\PendingApprovalsController::class, 'index'])->name('pending-approvals.index');
    Route::put('pending-approvals/{user}', [\App\Http\Controllers\SuperAdmin\PendingApprovalsController::class, 'approve'])->name('pending-approvals.approve');
    Route::match(['delete'], 'pending-approvals/{user}', [\App\Http\Controllers\SuperAdmin\PendingApprovalsController::class, 'deny'])->name('pending-approvals.deny');
    Route::get('tenant-applications', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'index'])->name('tenant-applications.index');
    Route::get('tenant-applications/{tenant_application}', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'show'])->name('tenant-applications.show');
    Route::post('tenant-applications/{tenant_application}/approve', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'approve'])->name('tenant-applications.approve');
    Route::post('tenant-applications/{tenant_application}/reject', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'reject'])->name('tenant-applications.reject');
    Route::delete('tenant-applications/{tenant_application}', [\App\Http\Controllers\SuperAdmin\TenantApplicationReviewController::class, 'destroy'])->name('tenant-applications.destroy');

    Route::resource('tenants', \App\Http\Controllers\SuperAdmin\TenantManagementController::class);
    Route::post('tenants/{tenant}/provision-database', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'provisionDatabase'])->name('tenants.provision-database');
    Route::patch('tenants/{tenant}/toggle-status', [\App\Http\Controllers\SuperAdmin\TenantManagementController::class, 'toggleStatus'])->name('tenants.toggle-status');
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
