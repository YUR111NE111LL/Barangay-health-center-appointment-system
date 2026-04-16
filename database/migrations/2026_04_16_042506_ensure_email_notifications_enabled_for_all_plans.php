<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ensures Basic, Standard, and Premium tenants all send appointment status emails regardless of
     * any historical false value on has_email_notifications.
     */
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')->update([
            'has_email_notifications' => true,
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible: previous per-plan values are not stored.
    }
};
