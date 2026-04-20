<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant user IDs live in tenant databases; central support tables store those IDs for reference only.
 * Foreign keys to central `users` prevented inserts and blocked tickets from reaching Super Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = (string) config('tenancy.database.central_connection', 'central');

        $this->dropForeignSafely($connection, 'support_tickets', 'user_id');
        $this->dropForeignSafely($connection, 'support_tickets', 'assigned_to');
        $this->dropForeignSafely($connection, 'support_ticket_messages', 'user_id');
    }

    /**
     * Dropping user FKs cannot be reversed without failing once tenant-scoped user_ids exist.
     */
    public function down(): void
    {
        //
    }

    private function dropForeignSafely(string $connection, string $tableName, string $column): void
    {
        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        try {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($column): void {
                $table->dropForeign([$column]);
            });
        } catch (\Throwable) {
            // Constraint missing or non-MySQL driver naming; tickets still work without FK.
        }
    }
};
