<?php

namespace App\Console\Commands;

use App\Mail\PlanExpiryNotification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckPlanExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-plan-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check tenant plan expirations, send notifications, and deactivate expired tenants after grace period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $gracePeriodDays = 3;

        // Find tenants with subscriptions expiring today or tomorrow (notify before expiry)
        $expiringSoon = Tenant::whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '>=', $now->copy()->startOfDay())
            ->where('subscription_ends_at', '<=', $now->copy()->addDays(2)->endOfDay())
            ->whereNull('expiry_notification_sent_at')
            ->where('is_active', true)
            ->with('plan')
            ->get();

        $notifiedCount = 0;
        foreach ($expiringSoon as $tenant) {
            $admin = $tenant->users()
                ->where('role', User::ROLE_HEALTH_CENTER_ADMIN)
                ->first();

            if ($admin && $admin->email) {
                Mail::to($admin->email)->send(new PlanExpiryNotification($tenant, 'expiring_soon'));
                $tenant->update(['expiry_notification_sent_at' => $now]);
                $notifiedCount++;
                $this->info("Sent expiry notification to {$tenant->name} (Admin: {$admin->email})");
            }
        }

        // Find tenants that expired and need grace period set
        $expiredTenants = Tenant::whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', $now->copy()->startOfDay())
            ->whereNull('grace_period_ends_at')
            ->where('is_active', true)
            ->with('plan')
            ->get();

        $gracePeriodSetCount = 0;
        foreach ($expiredTenants as $tenant) {
            $gracePeriodEnds = $tenant->subscription_ends_at->copy()->addDays($gracePeriodDays);
            $tenant->update(['grace_period_ends_at' => $gracePeriodEnds]);

            // Send notification about expiry and grace period
            $admin = $tenant->users()
                ->where('role', User::ROLE_HEALTH_CENTER_ADMIN)
                ->first();

            if ($admin && $admin->email) {
                Mail::to($admin->email)->send(new PlanExpiryNotification($tenant, 'expired_grace_period'));
                $tenant->update(['expiry_notification_sent_at' => $now]);
                $gracePeriodSetCount++;
                $this->info("Set grace period for {$tenant->name} until {$gracePeriodEnds->format('Y-m-d')}");
            }
        }

        // Find tenants past grace period - deactivate them
        $pastGracePeriod = Tenant::whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', $now->copy()->startOfDay())
            ->where('is_active', true)
            ->with('plan')
            ->get();

        $deactivatedCount = 0;
        foreach ($pastGracePeriod as $tenant) {
            $tenant->update(['is_active' => false]);

            // Send final notification
            $admin = $tenant->users()
                ->where('role', User::ROLE_HEALTH_CENTER_ADMIN)
                ->first();

            if ($admin && $admin->email) {
                Mail::to($admin->email)->send(new PlanExpiryNotification($tenant, 'deactivated'));
                $deactivatedCount++;
                $this->warn("Deactivated tenant: {$tenant->name}");
            }
        }

        $this->info('Plan expiration check completed:');
        $this->info("  - Notified {$notifiedCount} tenant(s) about upcoming expiry");
        $this->info("  - Set grace period for {$gracePeriodSetCount} expired tenant(s)");
        $this->info("  - Deactivated {$deactivatedCount} tenant(s) past grace period");

        return self::SUCCESS;
    }
}
