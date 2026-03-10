<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'trp_tenant_role_perm_unique';

    public function up(): void
    {
        if (! Schema::hasTable('tenant_role_permissions')) {
            Schema::create('tenant_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('role_name', 64);
                $table->string('permission_name', 128);
                $table->unique(['tenant_id', 'role_name', 'permission_name'], self::UNIQUE_INDEX);
            });
            return;
        }

        // Table already exists (e.g. from a failed run); add unique index if missing
        $indexExists = collect(DB::select("SHOW INDEX FROM tenant_role_permissions WHERE Key_name = ?", [self::UNIQUE_INDEX]))->isNotEmpty();
        if (! $indexExists) {
            Schema::table('tenant_role_permissions', function (Blueprint $table) {
                $table->unique(['tenant_id', 'role_name', 'permission_name'], self::UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_role_permissions');
    }
};
