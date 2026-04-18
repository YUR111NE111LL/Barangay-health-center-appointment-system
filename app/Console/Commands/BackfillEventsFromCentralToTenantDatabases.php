<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optional one-off: before tenant-aware routing, events may have been written only to the
 * central database. This copies them into each tenant database by natural key (tenant + title + date).
 */
class BackfillEventsFromCentralToTenantDatabases extends Command
{
    protected $signature = 'bhcas:backfill-events-from-central {--dry-run : Show what would be copied without writing}';

    protected $description = 'Copy events from the central database into each tenant database (safe to run multiple times; skips duplicates).';

    public function handle(): int
    {
        $central = config('tenancy.database.central_connection', 'central');

        if (! Schema::connection($central)->hasTable('events')) {
            $this->warn("No `events` table on connection [{$central}]. Nothing to do.");

            return self::SUCCESS;
        }

        $rows = DB::connection($central)->table('events')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $this->info('No event rows on central database.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $copied = 0;
        $skipped = 0;

        foreach (Tenant::query()->orderBy('id')->get() as $tenant) {
            $forTenant = $rows->where('tenant_id', (int) $tenant->getTenantKey());
            if ($forTenant->isEmpty()) {
                continue;
            }

            try {
                tenancy()->initialize($tenant);
            } catch (\Throwable $e) {
                $this->warn("Skipping tenant {$tenant->getTenantKey()}: {$e->getMessage()}");

                continue;
            }

            try {
                foreach ($forTenant as $row) {
                    $attrs = [
                        'tenant_id' => (int) $row->tenant_id,
                        'title' => $row->title,
                        'event_date' => $row->event_date,
                    ];

                    $values = [
                        'description' => $row->description,
                        'image_path' => $row->image_path,
                        'event_time' => $row->event_time,
                        'location' => $row->location,
                        'is_published' => (bool) $row->is_published,
                    ];

                    $existing = Event::query()->where($attrs)->exists();
                    if ($existing) {
                        $skipped++;

                        continue;
                    }

                    if ($dryRun) {
                        $this->line("[dry-run] Would copy event \"{$row->title}\" for tenant {$tenant->getTenantKey()}");
                        $copied++;

                        continue;
                    }

                    Event::query()->create(array_merge($attrs, $values, [
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]));
                    $copied++;
                }
            } finally {
                tenancy()->end();
            }
        }

        if ($dryRun) {
            $this->info("Dry run: would create {$copied} row(s); {$skipped} already existed.");
        } else {
            $this->info("Copied {$copied} event row(s); skipped {$skipped} duplicate(s).");
        }

        return self::SUCCESS;
    }
}
