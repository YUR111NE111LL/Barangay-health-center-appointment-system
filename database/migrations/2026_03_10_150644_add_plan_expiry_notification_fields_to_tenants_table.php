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
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('expiry_notification_sent_at')->nullable()->after('subscription_ends_at');
            $table->timestamp('grace_period_ends_at')->nullable()->after('expiry_notification_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['expiry_notification_sent_at', 'grace_period_ends_at']);
        });
    }
};
