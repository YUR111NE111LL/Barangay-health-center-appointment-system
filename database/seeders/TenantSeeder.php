<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'Basic',
                'max_appointments_per_month' => 50,
                'max_users' => 250,
                'has_automated_approval' => false,
                'has_appointment_history' => true,
                'has_monthly_reports' => false,
                'has_inventory_tracking' => false,
                'has_advanced_analytics' => false,
                'has_priority_support' => false,
                'has_data_export' => false,
                'has_email_notifications' => true,
                'has_web_customization' => false,
                'price' => 250,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'standard'],
            [
                'name' => 'Standard',
                'max_appointments_per_month' => 300,
                'max_users' => 1500,
                'has_automated_approval' => true,
                'has_appointment_history' => true,
                'has_monthly_reports' => true,
                'has_inventory_tracking' => false,
                'has_advanced_analytics' => false,
                'has_priority_support' => false,
                'has_data_export' => false,
                'has_email_notifications' => true,
                'has_web_customization' => true,
                'price' => 650,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'premium'],
            [
                'name' => 'Premium',
                'max_appointments_per_month' => 0,
                'max_users' => 0,
                'has_automated_approval' => true,
                'has_appointment_history' => true,
                'has_monthly_reports' => true,
                'has_inventory_tracking' => true,
                'has_advanced_analytics' => true,
                'has_priority_support' => true,
                'has_data_export' => true,
                'has_email_notifications' => true,
                'has_web_customization' => true,
                'price' => 1000,
            ]
        );

        // Keep seeding focused on plan data only.
        // Tenants should be created manually from the Super Admin UI to avoid accidental demo tenants.
    }
}
