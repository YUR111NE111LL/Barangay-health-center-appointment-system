<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align Basic / Standard / Premium with default user caps and monthly prices.
     */
    public function up(): void
    {
        DB::table('plans')->where('slug', 'basic')->update([
            'max_users' => 250,
            'price' => 250,
        ]);
        DB::table('plans')->where('slug', 'standard')->update([
            'max_users' => 1500,
            'price' => 650,
        ]);
        DB::table('plans')->where('slug', 'premium')->update([
            'max_users' => 0,
            'price' => 1000,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')->where('slug', 'basic')->update([
            'max_users' => 5,
            'price' => 0,
        ]);
        DB::table('plans')->where('slug', 'standard')->update([
            'max_users' => 15,
            'price' => 0,
        ]);
        DB::table('plans')->where('slug', 'premium')->update([
            'max_users' => 0,
            'price' => 0,
        ]);
    }
};
