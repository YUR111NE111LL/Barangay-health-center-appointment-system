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
        if (! Schema::hasTable('support_tickets') || ! Schema::hasTable('support_ticket_messages')) {
            return;
        }

        Schema::table('support_ticket_messages', function (Blueprint $table): void {
            $table->foreign('ticket_id')
                ->references('id')
                ->on('support_tickets')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('support_ticket_messages')) {
            return;
        }

        Schema::table('support_ticket_messages', function (Blueprint $table): void {
            $table->dropForeign(['ticket_id']);
        });
    }
};
