<?php

use App\Support\GitHubReleaseSyncService;

it('throws when github token is not configured', function (): void {
    config([
        'github.token' => '',
        'github.owner' => 'o',
        'github.repo' => 'r',
    ]);

    $service = app(GitHubReleaseSyncService::class);

    $service->sync(null);
})->throws(\RuntimeException::class);
