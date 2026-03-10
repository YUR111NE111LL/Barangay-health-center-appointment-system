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
            $table->boolean('has_web_customization')->default(false)->after('has_data_export');
        });

        DB::table('plans')->whereIn('slug', ['standard', 'premium'])->update(['has_web_customization' => true]);

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('site_name')->nullable()->after('name');
            $table->string('primary_color')->nullable()->after('site_name');
            $table->string('logo_path')->nullable()->after('primary_color');
            $table->string('tagline')->nullable()->after('logo_path');
            $table->text('footer_text')->nullable()->after('tagline');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('has_web_customization');
        });
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['site_name', 'primary_color', 'logo_path', 'tagline', 'footer_text']);
        });
    }
};
