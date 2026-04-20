<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('release_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('release_notes', 'external_ref')) {
                $table->string('external_ref', 191)->nullable()->unique()->after('tenant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('release_notes', function (Blueprint $table) {
            if (Schema::hasColumn('release_notes', 'external_ref')) {
                $table->dropUnique(['external_ref']);
                $table->dropColumn('external_ref');
            }
        });
    }
};
