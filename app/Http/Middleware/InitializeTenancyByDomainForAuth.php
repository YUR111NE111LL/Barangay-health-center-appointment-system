<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByDomainForAuth
{
    private function ensureTenantAuthTables(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function ($table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('role')->default(User::ROLE_RESIDENT);
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
            Schema::create('password_reset_tokens', function ($table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function ($table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * When on a tenant domain, initialize tenancy so login knows the current tenant.
     * When on central (localhost etc.), skip so central login is shown.
     * When domain is not registered, redirect to central with a friendly message instead of 500.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', ['127.0.0.1', 'localhost']);

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        try {
            return app(InitializeTenancyByDomain::class)->handle($request, function (Request $request) use ($next): Response {
                $this->ensureTenantAuthTables();

                return $next($request);
            });
        } catch (TenantCouldNotBeIdentifiedException $e) {
            $centralHost = $centralDomains[0] ?? 'localhost';
            $centralUrl = $request->getScheme().'://'.$centralHost;
            if (! in_array($request->getPort(), [80, 443], true) && $request->getPort()) {
                $centralUrl .= ':'.$request->getPort();
            }

            return redirect()->away($centralUrl.'/login')
                ->withErrors(['email' => 'This address ('.$host.') is not registered for any barangay. Use your barangay\'s correct URL or log in from the central site.']);
        } catch (TenantDatabaseDoesNotExistException $e) {
            // Tenant domain exists but tenant DB was not provisioned yet (or failed to migrate).
            // Redirecting avoids a raw 500 and gives the admin something actionable to check.
            $centralHost = $centralDomains[0] ?? 'localhost';
            $centralUrl = $request->getScheme().'://'.$centralHost;
            if (! in_array($request->getPort(), [80, 443], true) && $request->getPort()) {
                $centralUrl .= ':'.$request->getPort();
            }

            return redirect()->away($centralUrl.'/login')
                ->withErrors(['email' => 'This barangay is not ready yet (tenant database not found). Please ask the Super Admin to retry database provisioning.']);
        }
    }
}
