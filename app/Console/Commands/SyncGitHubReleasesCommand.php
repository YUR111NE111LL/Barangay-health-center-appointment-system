<?php

namespace App\Console\Commands;

use App\Support\GitHubReleaseSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncGitHubReleasesCommand extends Command
{
    protected $signature = 'github:sync-releases';

    protected $description = 'Import GitHub releases/tags into global Release Notes (central DB)';

    public function handle(GitHubReleaseSyncService $sync): int
    {
        try {
            $result = $sync->sync(null);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $this->info($result['message']);

        return self::SUCCESS;
    }
}
