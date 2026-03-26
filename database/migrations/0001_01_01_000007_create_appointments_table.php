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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            // Tenant DB has no `tenants` table; keep tenant_id as plain indexed column.
            $table->unsignedBigInteger('tenant_id')->index();
            // Tenant auth tables may be provisioned separately; avoid FK hard dependency here.
            $table->unsignedBigInteger('user_id')->index(); // Resident (patient)
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->string('status')->default('pending'); // pending, approved, completed, cancelled, no_show
            $table->text('complaint')->nullable();
            $table->text('notes')->nullable(); // Staff/Nurse notes
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('visited_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
