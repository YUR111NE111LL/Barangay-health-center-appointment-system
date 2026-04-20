<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GitHub API (private repo: use a fine-grained PAT with Contents: Read-only)
    |--------------------------------------------------------------------------
    */

    'token' => env('GITHUB_TOKEN') !== null ? trim((string) env('GITHUB_TOKEN')) : null,

    'owner' => env('GITHUB_REPO_OWNER') !== null ? trim((string) env('GITHUB_REPO_OWNER')) : null,

    'repo' => env('GITHUB_REPO_NAME') !== null ? trim((string) env('GITHUB_REPO_NAME')) : null,

    /*
    |--------------------------------------------------------------------------
    | Default branch when publishing a release from the Super Admin form (optional).
    | If empty, the API default_branch for the repo is used.
    |--------------------------------------------------------------------------
    */

    'default_branch' => env('GITHUB_DEFAULT_BRANCH') !== null ? trim((string) env('GITHUB_DEFAULT_BRANCH')) : null,

    /*
    |--------------------------------------------------------------------------
    | When true, lightweight tags without a GitHub Release are imported as notes.
    |--------------------------------------------------------------------------
    */

    'sync_tags' => filter_var(env('GITHUB_SYNC_TAGS', true), FILTER_VALIDATE_BOOLEAN),

];
