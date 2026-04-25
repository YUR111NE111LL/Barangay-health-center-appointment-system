<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')
            ->where('slug', 'standard')
            ->update([
                'has_inventory_tracking' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')
            ->where('slug', 'standard')
            ->update([
                'has_inventory_tracking' => false,
                'updated_at' => now(),
            ]);
    }
};
