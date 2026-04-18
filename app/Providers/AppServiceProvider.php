<?php

namespace App\Providers;

use App\Events\AppointmentSaved;
use App\Listeners\RecordTenantAuthAudit;
use App\Listeners\SendAppointmentNotification;
use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\Event as EventModel;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Observers\TenantAuditObserver;
use App\Support\GlobalUpdateReadState;
use App\Support\SessionPortal;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        View::composer([
            'tenant.layouts.app',
            'tenant-user.layouts.app',
            'superadmin.layouts.app',
        ], function (\Illuminate\View\View $view): void {
            if (! app()->runningInConsole()) {
                $view->with('sessionPortalKey', SessionPortal::portalKey(request()));
            }
        });

        // Per-tenant RBAC: tenant users use tenant_role_permissions only (via User::hasTenantPermission). Never Spatie.
        // Super Admin (central, no tenant): not governed by Spatie RBAC — full access without permission matrix checks.
        Gate::before(function ($user, string $ability): ?bool {
            if (! $user instanceof User) {
                return null;
            }
            if ($user->isSuperAdmin()) {
                return true;
            }
            if ($user->tenant_id === null) {
                return null;
            }

            // Add/manage barangay user accounts: Barangay (Health Center) Admin only; not driven by tenant_role_permissions rows.
            if ($ability === 'manage users' && $user->role === User::ROLE_HEALTH_CENTER_ADMIN) {
                return true;
            }

            return $user->hasTenantPermission($ability);
        });

        Event::listen(AppointmentSaved::class, SendAppointmentNotification::class);
        Event::listen(Login::class, [RecordTenantAuthAudit::class, 'handleLogin']);
        Event::listen(Logout::class, [RecordTenantAuthAudit::class, 'handleLogout']);

        foreach ([
            User::class,
            Appointment::class,
            Service::class,
            Announcement::class,
            EventModel::class,
        ] as $auditableModel) {
            $auditableModel::observe(TenantAuditObserver::class);
        }

        // View composers: inject data into layouts so views don't run queries (MVC: data from controller/composer, not view)
        View::composer('tenant.layouts.app', function (\Illuminate\View\View $view): void {
            $user = Auth::user();
            $tenant = $user?->tenant;
            if ($tenant) {
                $tenant->loadMissing('domains');
            }
            $brandColor = $tenant?->getPrimaryColor() ?? '#0d9488';
            $brandName = $tenant ? $tenant->barangayDisplayName() : config('bhcas.acronym');
            $brandLogo = $tenant?->logoUrl();
            $brandLogoClass = $tenant ? $tenant->brandLogoImgClass() : 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25';
            $tenantMainMaxWidthClass = $tenant?->appearanceMainMaxWidthClass('staff') ?? 'max-w-7xl';
            $theme = $tenant?->theme ?? 'default';
            $themeClass = 'theme-'.(in_array($theme, ['default', 'modern', 'minimal'], true) ? $theme : 'default');
            $navLayout = in_array($tenant?->nav_layout ?? 'navbar', ['navbar', 'sidebar', 'dropdown'], true) ? ($tenant?->nav_layout ?? 'navbar') : 'navbar';
            $hasFeatureWebCustomization = $tenant?->hasFeature('web_customization') ?? false;
            $fontUrl = $tenant ? Tenant::fontFamilyGoogleUrl($tenant->font_family) : null;
            $backendPendingCount = 0;
            if ($user && $user->role === User::ROLE_HEALTH_CENTER_ADMIN && $user->tenant_id) {
                $backendPendingCount = User::withoutGlobalScopes()
                    ->where('tenant_id', $user->tenant_id)
                    ->whereIn('role', User::rolesApprovedByBarangayAdmin())
                    ->where('is_approved', false)
                    ->count();
            }
            $backendPendingAppointmentsCount = 0;
            if ($user && $user->tenant_id && $user instanceof User && $user->hasTenantPermission('view appointments')) {
                $backendPendingAppointmentsCount = Appointment::query()
                    ->where('status', Appointment::STATUS_PENDING)
                    ->count();
            }
            $supportUpdatesNotificationCount = 0;
            if ($user instanceof User && $user->tenant_id) {
                $supportUpdatesNotificationCount = GlobalUpdateReadState::unreadGlobalCount($user);
            }
            $view->with(compact('tenant', 'brandColor', 'brandName', 'brandLogo', 'brandLogoClass', 'tenantMainMaxWidthClass', 'themeClass', 'navLayout', 'hasFeatureWebCustomization', 'fontUrl', 'backendPendingCount', 'backendPendingAppointmentsCount', 'supportUpdatesNotificationCount'));
        });
        View::composer('tenant-user.layouts.app', function (\Illuminate\View\View $view): void {
            $user = Auth::user();
            $tenant = $user?->tenant;
            if ($tenant) {
                $tenant->loadMissing('domains');
            }
            $brandColor = $tenant?->getPrimaryColor() ?? '#0d9488';
            $brandName = $tenant ? $tenant->barangayDisplayName() : config('bhcas.acronym');
            $brandLogo = $tenant?->logoUrl();
            $brandLogoClass = $tenant ? $tenant->brandLogoImgClass() : 'h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/25';
            $tenantMainMaxWidthClass = $tenant?->appearanceMainMaxWidthClass('resident') ?? 'max-w-4xl';
            $theme = $tenant?->theme ?? 'default';
            $themeClass = 'theme-'.(in_array($theme, ['default', 'modern', 'minimal'], true) ? $theme : 'default');
            $navLayout = in_array($tenant?->nav_layout ?? 'navbar', ['navbar', 'sidebar', 'dropdown'], true) ? ($tenant?->nav_layout ?? 'navbar') : 'navbar';
            $navOrder = $tenant?->nav_order ?? Tenant::residentNavItemKeys();
            $residentNavConfig = [
                'dashboard' => ['route' => 'resident.dashboard', 'label' => 'My Appointments', 'icon' => 'clipboard'],
                'book' => ['route' => 'resident.book', 'label' => 'Book', 'icon' => 'book'],
                'announcements' => ['route' => 'resident.announcements.index', 'label' => 'Announcements', 'icon' => 'announcements'],
                'events' => ['route' => 'resident.events.index', 'label' => 'Events', 'icon' => 'events'],
                'support' => ['route' => 'resident.support.help', 'label' => 'Support', 'icon' => 'support'],
                'profile' => ['route' => 'resident.profile.show', 'label' => 'Profile', 'icon' => 'profile'],
            ];
            $residentSupportStatusUpdateCount = 0;
            if ($user instanceof User && $user->tenant_id) {
                $residentSupportStatusUpdateCount = SupportTicket::query()
                    ->where('tenant_id', $user->tenant_id)
                    ->where('user_id', $user->id)
                    ->whereIn('status', [SupportTicket::STATUS_IN_PROGRESS, SupportTicket::STATUS_RESOLVED])
                    ->count();
            }
            $residentGlobalUpdateNotificationCount = 0;
            if ($user instanceof User && $user->tenant_id) {
                $residentGlobalUpdateNotificationCount = GlobalUpdateReadState::unreadGlobalCount($user);
            }
            $residentSupportNotificationCount = $residentSupportStatusUpdateCount + $residentGlobalUpdateNotificationCount;
            if (isset($residentNavConfig['support']) && $residentSupportNotificationCount > 0) {
                $residentNavConfig['support']['badge'] = $residentSupportNotificationCount;
            }
            if ($user instanceof User && ! $user->hasTenantPermission('book appointments')) {
                unset($residentNavConfig['book']);
            }
            $residentNavItems = [];
            foreach ($navOrder as $key) {
                if (isset($residentNavConfig[$key])) {
                    $residentNavItems[] = $residentNavConfig[$key];
                }
            }
            if (count($residentNavItems) < count($residentNavConfig)) {
                foreach (array_keys($residentNavConfig) as $key) {
                    if (! in_array($key, $navOrder, true)) {
                        $residentNavItems[] = $residentNavConfig[$key];
                    }
                }
            }
            $fontUrl = $tenant ? Tenant::fontFamilyGoogleUrl($tenant->font_family) : null;
            $view->with(compact('tenant', 'brandColor', 'brandName', 'brandLogo', 'brandLogoClass', 'tenantMainMaxWidthClass', 'themeClass', 'navLayout', 'navOrder', 'residentNavConfig', 'residentNavItems', 'fontUrl', 'residentSupportStatusUpdateCount', 'residentGlobalUpdateNotificationCount'));
        });
        View::composer('superadmin.layouts.app', function (\Illuminate\View\View $view): void {
            $count = 0;
            if (Auth::check()) {
                $count = User::withoutGlobalScopes()
                    ->whereIn('role', User::rolesApprovedBySuperAdmin())
                    ->where('is_approved', false)
                    ->count();
            }
            $tenantApplicationPendingCount = 0;
            if (Auth::check()) {
                $tenantApplicationPendingCount = TenantApplication::query()
                    ->where('status', TenantApplication::STATUS_PENDING)
                    ->count();
            }
            $supportReportPendingCount = 0;
            if (Auth::check()) {
                $supportReportPendingCount = SupportTicket::query()
                    ->whereIn('status', ['open', 'in_progress'])
                    ->count();
            }
            $view->with([
                'pendingCount' => $count,
                'tenantApplicationPendingCount' => $tenantApplicationPendingCount,
                'supportReportPendingCount' => $supportReportPendingCount,
            ]);
        });

        // @planFeature('inventory') ... @endplanFeature — show content only if tenant's plan has the feature
        Blade::directive('planFeature', function (string $feature): string {
            return "<?php if(auth()->check() && auth()->user()->tenant?->hasFeature({$feature})): ?>";
        });
        Blade::directive('endplanFeature', function (): string {
            return '<?php endif; ?>';
        });

        // Password reset email: include tenant_id in URL for multi-tenant (so reset form finds the right user)
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $params = [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ];
            $barangayLabel = '';
            if (property_exists($notifiable, 'tenant_id') && $notifiable->tenant_id !== null) {
                $params['tenant_id'] = $notifiable->tenant_id;
                $tenant = Tenant::query()->with('domains')->find($notifiable->tenant_id);
                if ($tenant) {
                    $barangayLabel = $tenant->barangayDisplayName();
                }
            }
            $url = url(route('password.reset', $params, false));

            $mail = (new MailMessage)
                ->subject(__('Reset Password Notification'))
                ->line(__('You are receiving this email because we received a password reset request for your account.'));
            if ($barangayLabel !== '') {
                $mail->line(__('Barangay: :barangay', ['barangay' => $barangayLabel]));
                $mail->line(__('If you use Gmail, check Spam and Promotions for this message.'));
            }

            return $mail
                ->action(__('Reset Password'), $url)
                ->line(__('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.users.expire')]))
                ->line(__('If you did not request a password reset, no further action is required.'));
        });
    }
}
