<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->boolean('is_free')->default(true)->after('quantity');
            $table->decimal('price_per_unit', 12, 2)->nullable()->after('is_free');
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn(['is_free', 'price_per_unit']);
        });
    }
};
