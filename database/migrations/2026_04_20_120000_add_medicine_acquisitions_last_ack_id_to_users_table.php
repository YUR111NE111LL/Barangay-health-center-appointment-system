<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'medicine_acquisitions_last_ack_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('medicine_acquisitions_last_ack_id')->default(0);
            });
        }

        if (Schema::hasTable('medicine_acquisitions')) {
            $max = (int) (DB::table('medicine_acquisitions')->max('id') ?? 0);
            DB::table('users')->update(['medicine_acquisitions_last_ack_id' => $max]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'medicine_acquisitions_last_ack_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('medicine_acquisitions_last_ack_id');
            });
        }
    }
};
