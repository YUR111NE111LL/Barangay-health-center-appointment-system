<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Standard ₱650/mo, Premium ₱1000/mo (replaces prior Standard ₱1500 and Premium “Contact us”).
     */
    public function up(): void
    {
        DB::table('plans')->where('slug', 'standard')->update([
            'price' => 650,
        ]);
        DB::table('plans')->where('slug', 'premium')->update([
            'price' => 1000,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')->where('slug', 'standard')->update([
            'price' => 1500,
        ]);
        DB::table('plans')->where('slug', 'premium')->update([
            'price' => null,
        ]);
    }
};
