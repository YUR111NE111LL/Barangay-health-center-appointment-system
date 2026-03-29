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
        Schema::table('tenant_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenant_applications', 'tenant_id')) {
                $table->string('tenant_id', 255)->nullable()->index();
            }
            if (! Schema::hasColumn('tenant_applications', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('tenant_applications', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
            if (! Schema::hasColumn('tenant_applications', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('tenant_applications', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
            }
        });

        Schema::table('tenant_applications', function (Blueprint $table): void {
            $columns = ['tenant_id', 'reviewed_by', 'reviewed_at', 'rejection_reason'];
            $toDrop = array_filter($columns, fn (string $c): bool => Schema::hasColumn('tenant_applications', $c));
            if ($toDrop !== []) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
