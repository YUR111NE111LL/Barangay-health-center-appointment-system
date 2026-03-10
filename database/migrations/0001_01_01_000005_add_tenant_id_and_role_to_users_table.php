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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default('Resident')->after('tenant_id'); // Super Admin, Health Center Admin, Nurse, Staff, Resident
        });

        // Super Admin has no tenant; email unique per tenant or globally
        // For single-database: we keep email unique globally or (tenant_id, email) unique
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['tenant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'role']);
        });
    }
};
