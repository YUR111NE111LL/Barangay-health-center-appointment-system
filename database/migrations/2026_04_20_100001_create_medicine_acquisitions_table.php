<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medicine_acquisitions')) {
            Schema::create('medicine_acquisitions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                // Tenant DBs can be provisioned with slightly different user schemas; avoid hard FK failures.
                $table->unsignedBigInteger('user_id')->index();
                $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price_snapshot', 12, 2)->nullable();
                $table->decimal('line_total', 12, 2)->default(0);
                $table->boolean('is_free')->default(true);
                $table->timestamps();
                $table->index(['tenant_id', 'created_at']);
            });

            return;
        }

        Schema::table('medicine_acquisitions', function (Blueprint $table): void {
            if (! Schema::hasColumn('medicine_acquisitions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('medicine_acquisitions', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('tenant_id');
            }
            if (! Schema::hasColumn('medicine_acquisitions', 'medicine_id')) {
                $table->unsignedBigInteger('medicine_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('medicine_acquisitions', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('medicine_id');
            }
            if (! Schema::hasColumn('medicine_acquisitions', 'unit_price_snapshot')) {
                $table->decimal('unit_price_snapshot', 12, 2)->nullable()->after('quantity');
            }
            if (! Schema::hasColumn('medicine_acquisitions', 'line_total')) {
                $table->decimal('line_total', 12, 2)->default(0)->after('unit_price_snapshot');
            }
            if (! Schema::hasColumn('medicine_acquisitions', 'is_free')) {
                $table->boolean('is_free')->default(true)->after('line_total');
            }
            if (! Schema::hasColumn('medicine_acquisitions', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (! Schema::hasColumn('medicine_acquisitions', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_acquisitions');
    }
};
