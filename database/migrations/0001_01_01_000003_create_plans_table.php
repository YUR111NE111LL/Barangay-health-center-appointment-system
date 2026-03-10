<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Basic, Standard, Premium
            $table->string('slug')->unique();
            $table->unsignedInteger('max_appointments_per_month')->default(0); // 0 = unlimited
            $table->boolean('has_automated_approval')->default(false);
            $table->boolean('has_appointment_history')->default(true);
            $table->boolean('has_monthly_reports')->default(false);
            $table->boolean('has_inventory_tracking')->default(false);
            $table->boolean('has_advanced_analytics')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('has_data_export')->default(false);
            $table->boolean('has_email_notifications')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
