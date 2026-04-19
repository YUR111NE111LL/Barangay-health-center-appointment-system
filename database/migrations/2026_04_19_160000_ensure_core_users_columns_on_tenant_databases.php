<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant DBs use a curated migration list in config/tenancy.php. Older tenant databases
 * may have been created from create_users without role / google / is_approved; heal in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('role')->default('Resident');
                $table->string('name');
                $table->string('purok_address')->nullable();
                $table->string('profile_picture')->nullable();
                $table->string('email');
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->rememberToken();
                $table->string('google_id')->nullable();
                $table->boolean('is_approved')->default(false);
                $table->timestamps();
                $table->unique(['tenant_id', 'email']);
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                if (Schema::hasColumn('users', 'tenant_id')) {
                    $table->string('role')->default('Resident')->after('tenant_id');
                } else {
                    $table->string('role')->default('Resident');
                }
            });
        }

        if (! Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('google_id')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'is_approved')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_approved')->default(true);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
