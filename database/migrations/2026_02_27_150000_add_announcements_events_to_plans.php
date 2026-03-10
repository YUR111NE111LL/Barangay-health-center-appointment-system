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
            $table->boolean('has_announcements_events')->default(false)->after('has_web_customization');
        });

        // Basic = simple (text only). Standard & Premium = pretty (with images, follows admin customization).
        DB::table('plans')->whereIn('slug', ['standard', 'premium'])->update(['has_announcements_events' => true]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('has_announcements_events');
        });
    }
};
