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
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table): void {
                if (! Schema::hasColumn('support_tickets', 'reporter_name')) {
                    $table->string('reporter_name')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('support_tickets', 'reporter_email')) {
                    $table->string('reporter_email')->nullable()->after('reporter_name');
                }
            });
        }

        if (Schema::hasTable('support_ticket_messages')) {
            Schema::table('support_ticket_messages', function (Blueprint $table): void {
                if (! Schema::hasColumn('support_ticket_messages', 'author_name')) {
                    $table->string('author_name')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('support_ticket_messages', 'author_email')) {
                    $table->string('author_email')->nullable()->after('author_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table): void {
                if (Schema::hasColumn('support_tickets', 'reporter_email')) {
                    $table->dropColumn('reporter_email');
                }
                if (Schema::hasColumn('support_tickets', 'reporter_name')) {
                    $table->dropColumn('reporter_name');
                }
            });
        }

        if (Schema::hasTable('support_ticket_messages')) {
            Schema::table('support_ticket_messages', function (Blueprint $table): void {
                if (Schema::hasColumn('support_ticket_messages', 'author_email')) {
                    $table->dropColumn('author_email');
                }
                if (Schema::hasColumn('support_ticket_messages', 'author_name')) {
                    $table->dropColumn('author_name');
                }
            });
        }
    }
};
