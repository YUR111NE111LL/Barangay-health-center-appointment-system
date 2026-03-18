<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add .localhost domain for each tenant that has a .test domain (for local dev).
     */
    public function up(): void
    {
        $domains = DB::table('domains')->where('domain', 'like', '%.test')->get();

        foreach ($domains as $row) {
            $localhostDomain = str_replace('.test', '.localhost', $row->domain);
            $exists = DB::table('domains')->where('domain', $localhostDomain)->exists();
            if (! $exists) {
                DB::table('domains')->insert([
                    'domain' => $localhostDomain,
                    'tenant_id' => $row->tenant_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('domains')->where('domain', 'like', '%.localhost')->delete();
    }
};
