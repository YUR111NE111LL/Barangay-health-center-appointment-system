<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')
            ->where('slug', 'premium')
            ->update([
                'has_web_customization' => true,
                'has_full_web_customization' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Keep premium capabilities enabled; no destructive rollback.
    }
};
