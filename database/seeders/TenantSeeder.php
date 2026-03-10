<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $basic = Plan::firstOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'Basic',
                'max_appointments_per_month' => 50,
                'max_users' => 5,
                'has_automated_approval' => false,
                'has_appointment_history' => true,
                'has_monthly_reports' => false,
                'has_inventory_tracking' => false,
                'has_advanced_analytics' => false,
                'has_priority_support' => false,
                'has_data_export' => false,
                'has_email_notifications' => true,
                'has_web_customization' => false,
                'price' => 0,
            ]
        );

        $standard = Plan::firstOrCreate(
            ['slug' => 'standard'],
            [
                'name' => 'Standard',
                'max_appointments_per_month' => 300,
                'max_users' => 15,
                'has_automated_approval' => true,
                'has_appointment_history' => true,
                'has_monthly_reports' => true,
                'has_inventory_tracking' => false,
                'has_advanced_analytics' => false,
                'has_priority_support' => false,
                'has_data_export' => false,
                'has_email_notifications' => true,
                'has_web_customization' => true,
                'price' => 0,
            ]
        );

        $premium = Plan::firstOrCreate(
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
                'price' => 0,
            ]
        );

        Tenant::firstOrCreate(
            ['slug' => 'brgy-sumpong'],
            [
                'plan_id' => $standard->id,
                'name' => 'Brgy Sumpong',
                'address' => 'Sumpong, Malaybalay City',
                'contact_number' => '088-123-4567',
                'email' => 'sumpong@bhc.example.com',
                'is_active' => true,
            ]
        );

        Tenant::firstOrCreate(
            ['slug' => 'brgy-casisang'],
            [
                'plan_id' => $basic->id,
                'name' => 'Brgy Casisang',
                'address' => 'Casisang, Malaybalay City',
                'contact_number' => '088-234-5678',
                'email' => 'casisang@bhc.example.com',
                'is_active' => true,
            ]
        );

        // Premium plan tenant (full web customization, etc.)
        Tenant::firstOrCreate(
            ['slug' => 'brgy-kalasungay'],
            [
                'plan_id' => $premium->id,
                'name' => 'Brgy Kalasungay',
                'address' => 'Kalasungay, Malaybalay City',
                'contact_number' => '088-345-6789',
                'email' => 'kalasungay@bhc.example.com',
                'is_active' => true,
            ]
        );
    }
}
