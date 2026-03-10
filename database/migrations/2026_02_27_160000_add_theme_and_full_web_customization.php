<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('has_full_web_customization')->default(false)->after('has_web_customization');
        });
        DB::table('plans')->where('slug', 'premium')->update(['has_full_web_customization' => true]);

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('theme')->default('default')->after('footer_text');
            $table->text('custom_css')->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('has_full_web_customization');
        });
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['theme', 'custom_css']);
        });
    }
};
