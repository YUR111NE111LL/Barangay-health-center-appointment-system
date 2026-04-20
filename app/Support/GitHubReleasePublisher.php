<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Creates a GitHub Release (and tag if missing) via REST API.
 * Requires a token with write access (classic: `repo`; fine-grained: Contents: Read and write).
 */
final class GitHubReleasePublisher
{
    /**
     * @param  string  $tagName  Git tag (e.g. v1.0.3) — must not already exist unless you intend to attach a release to an existing tag
     * @param  string|null  $targetBranch  Branch name or commit SHA; null uses repository default branch from the API
     *
     * @throws RuntimeException
     */
    public function publishRelease(
        string $tagName,
        string $releaseName,
        ?string $body,
        ?string $targetBranch = null,
    ): void {
        $token = config('github.token');
        $owner = config('github.owner');
        $repo = config('github.repo');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('GITHUB_TOKEN is not set.');
        }

        if (! is_string($owner) || $owner === '' || ! is_string($repo) || $repo === '') {
            throw new RuntimeException('GITHUB_REPO_OWNER and GITHUB_REPO_NAME must be set.');
        }

        $tagName = trim($tagName);
        if ($tagName === '') {
            throw new RuntimeException('Version/tag is empty.');
        }

        $target = $targetBranch !== null && trim($targetBranch) !== ''
            ? trim($targetBranch)
            : (config('github.default_branch') ?: $this->fetchDefaultBranchRef($token, $owner, $repo));

        $path = '/repos/'.$this->encodePathSegment($owner).'/'.$this->encodePathSegment($repo).'/releases';

        $payload = [
            'tag_name' => $tagName,
            'name' => $releaseName,
            'body' => $body ?? '',
            'draft' => false,
            'prerelease' => false,
            'target_commitish' => $target,
        ];

        $response = Http::timeout(120)
            ->withToken($token)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->post('https://api.github.com'.$path, $payload);

        if ($response->successful()) {
            return;
        }

        $message = $response->json('message') ?? $response->body();

        throw new RuntimeException(
            'GitHub create release failed ('.$response->status().'): '.(is_string($message) ? $message : $response->body())
        );
    }

    private function fetchDefaultBranchRef(string $token, string $owner, string $repo): string
    {
        $path = '/repos/'.$this->encodePathSegment($owner).'/'.$this->encodePathSegment($repo);
        $response = Http::timeout(30)
            ->withToken($token)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->get('https://api.github.com'.$path);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Could not read repository default branch ('.$response->status().'). Set GITHUB_DEFAULT_BRANCH in .env or fix token access.'
            );
        }

        $branch = $response->json('default_branch');

        return is_string($branch) && $branch !== '' ? $branch : 'main';
    }

    private function encodePathSegment(string $segment): string
    {
        return rawurlencode($segment);
    }
}
