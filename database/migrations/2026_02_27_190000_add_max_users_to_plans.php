<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_users')->default(5)->after('max_appointments_per_month');
        });
        DB::table('plans')->where('slug', 'basic')->update(['max_users' => 5]);
        DB::table('plans')->where('slug', 'standard')->update(['max_users' => 15]);
        DB::table('plans')->where('slug', 'premium')->update(['max_users' => 0]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_users');
        });
    }
};
