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
        if (Schema::hasTable('release_notes')) {
            return;
        }

        Schema::create('release_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('version', 50)->nullable();
            $table->string('type', 30)->default('feature');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_notes');
    }
};
