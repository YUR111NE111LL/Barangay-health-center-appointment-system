<?php

namespace App\Providers;

use App\Events\AppointmentSaved;
use App\Listeners\SendAppointmentNotification;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
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
        if (! app()->runningInConsole()) {
            $host = request()->getHost();
            $baseCookieName = (string) config('session.cookie', 'laravel-session');
            $hostCookieSuffix = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($host)) ?? 'app';
            $path = '/'.ltrim((string) request()->path(), '/');
            $for = (string) request()->query('for', request()->input('for', ''));
            $portal = 'public';
            if (str_starts_with($path, '/super-admin') || $for === 'super-admin') {
                $portal = 'superadmin';
            } elseif (str_starts_with($path, '/backend') || $for === 'tenant') {
                $portal = 'staff';
            } elseif (str_starts_with($path, '/resident') || $for === 'resident') {
                $portal = 'resident';
            }

            config(['session.cookie' => $baseCookieName.'_'.$hostCookieSuffix.'_'.$portal]);
        }

        Paginator::useTailwind();

        // Per-tenant RBAC: for tenant users, ONLY tenant_role_permissions (via User::hasTenantPermission). Never Spatie.
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->tenant_id === null) {
                return null; // Super Admin: use default (Spatie) checks
            }

            return $user->hasTenantPermission($ability);
        });

        Event::listen(AppointmentSaved::class, SendAppointmentNotification::class);

        // View composers: inject data into layouts so views don't run queries (MVC: data from controller/composer, not view)
        View::composer('backend.layouts.app', function (\Illuminate\View\View $view): void {
            $user = Auth::user();
            $tenant = $user?->tenant;
            if ($tenant) {
                $tenant->loadMissing('domains');
            }
            $brandColor = $tenant?->getPrimaryColor() ?? '#0d9488';
            $brandName = $tenant ? $tenant->barangayDisplayName() : config('bhcas.acronym');
            $brandLogo = $tenant && $tenant->logo_path
                ? (str_contains($tenant->logo_path, 'cloudinary.com') ? $tenant->logo_path : asset('storage/'.$tenant->logo_path))
                : null;
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
            $view->with(compact('tenant', 'brandColor', 'brandName', 'brandLogo', 'themeClass', 'navLayout', 'hasFeatureWebCustomization', 'fontUrl', 'backendPendingCount', 'backendPendingAppointmentsCount'));
        });
        View::composer('frontend.layouts.app', function (\Illuminate\View\View $view): void {
            $user = Auth::user();
            $tenant = $user?->tenant;
            if ($tenant) {
                $tenant->loadMissing('domains');
            }
            $brandColor = $tenant?->getPrimaryColor() ?? '#0d9488';
            $brandName = $tenant ? $tenant->barangayDisplayName() : config('bhcas.acronym');
            $brandLogo = $tenant && $tenant->logo_path
                ? (str_contains($tenant->logo_path, 'cloudinary.com') ? $tenant->logo_path : asset('storage/'.$tenant->logo_path))
                : null;
            $theme = $tenant?->theme ?? 'default';
            $themeClass = 'theme-'.(in_array($theme, ['default', 'modern', 'minimal'], true) ? $theme : 'default');
            $navLayout = in_array($tenant?->nav_layout ?? 'navbar', ['navbar', 'sidebar', 'dropdown'], true) ? ($tenant?->nav_layout ?? 'navbar') : 'navbar';
            $navOrder = $tenant?->nav_order ?? Tenant::residentNavItemKeys();
            $residentNavConfig = [
                'dashboard' => ['route' => 'resident.dashboard', 'label' => 'My Appointments'],
                'book' => ['route' => 'resident.book', 'label' => 'Book'],
                'announcements' => ['route' => 'resident.announcements.index', 'label' => 'Announcements'],
                'events' => ['route' => 'resident.events.index', 'label' => 'Events'],
                'profile' => ['route' => 'resident.profile.show', 'label' => 'Profile'],
            ];
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
            $view->with(compact('tenant', 'brandColor', 'brandName', 'brandLogo', 'themeClass', 'navLayout', 'navOrder', 'residentNavConfig', 'residentNavItems', 'fontUrl'));
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
            $view->with([
                'pendingCount' => $count,
                'tenantApplicationPendingCount' => $tenantApplicationPendingCount,
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
