<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant-only table: register this migration path in config/tenancy.php (tenants:migrate).
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            // No FK to users: tenant DB migration order does not guarantee users exists before
            // this runs; matches appointments (unsignedBigInteger user_id, app-level integrity).
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_role', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('event', 32);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
