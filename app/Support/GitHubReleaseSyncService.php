<?php

namespace App\Support;

use App\Models\ReleaseNote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class GitHubReleaseSyncService
{
    /**
     * Pull GitHub releases (and optionally tags) into global {@see ReleaseNote} rows.
     *
     * @return array{message: string, releases_created: int, releases_updated: int, tags_created: int, tags_skipped: int}
     */
    public function sync(?int $createdByUserId = null): array
    {
        $token = config('github.token');
        $owner = config('github.owner');
        $repo = config('github.repo');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('GitHub sync is not configured. Set GITHUB_TOKEN, GITHUB_REPO_OWNER, and GITHUB_REPO_NAME in your .env file.');
        }

        if (! is_string($owner) || $owner === '' || ! is_string($repo) || $repo === '') {
            throw new RuntimeException('Set GITHUB_REPO_OWNER and GITHUB_REPO_NAME in your .env file.');
        }

        $this->assertRepositoryAccessible($token, $owner, $repo);

        $releasesCreated = 0;
        $releasesUpdated = 0;
        $tagsCreated = 0;
        $tagsSkipped = 0;
        $releasesBlocked403 = false;

        $releaseTagNames = [];

        $repoPath = '/repos/'.$this->encodePathSegment($owner).'/'.$this->encodePathSegment($repo);

        $releaseRows = $this->fetchAllPages($repoPath.'/releases');
        if ($releaseRows['blocked403']) {
            $releasesBlocked403 = true;
        }

        foreach ($releaseRows['rows'] as $release) {
            if (! empty($release['draft'])) {
                continue;
            }

            $tagName = (string) ($release['tag_name'] ?? '');
            if ($tagName !== '') {
                $releaseTagNames[$tagName] = true;
            }

            $externalRef = 'github:release:'.(int) ($release['id'] ?? 0);
            if ($externalRef === 'github:release:0') {
                continue;
            }

            $body = (string) ($release['body'] ?? '');
            $title = (string) ($release['name'] ?? '');
            if ($title === '') {
                $title = $tagName !== '' ? $tagName : 'Release';
            }

            $publishedAt = $this->parseGithubDate($release['published_at'] ?? null) ?? now();

            $payload = [
                'tenant_id' => null,
                'title' => Str::limit($title, 255),
                'summary' => $this->makeSummary($body),
                'content' => $body !== '' ? $body : null,
                'version' => $this->normalizeVersion($tagName),
                'type' => $this->inferType($body, $title),
                'published_at' => $publishedAt,
                'is_pinned' => false,
            ];

            $note = ReleaseNote::query()->firstOrNew([
                'tenant_id' => null,
                'external_ref' => $externalRef,
            ]);

            $wasNew = ! $note->exists;
            $note->fill($payload);
            if ($wasNew && $createdByUserId !== null) {
                $note->created_by = $createdByUserId;
            }
            $note->save();

            if ($wasNew) {
                $releasesCreated++;
            } else {
                $releasesUpdated++;
            }
        }

        if (config('github.sync_tags')) {
            $tagRows = $this->fetchAllPages($repoPath.'/tags');
            if ($tagRows['blocked403']) {
                throw new RuntimeException(
                    'GitHub returned 403 when listing tags. This token cannot read repository data. '.
                    'For a private repo, use a classic Personal Access Token with the repo scope (Settings → Developer settings → Personal access tokens → Generate new token (classic)), '.
                    'or set your fine-grained token to Contents: Read for this repository and authorize SSO if the org requires it.'
                );
            }

            foreach ($tagRows['rows'] as $tag) {
                $name = (string) ($tag['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                if (isset($releaseTagNames[$name])) {
                    $tagsSkipped++;

                    continue;
                }

                if (ReleaseNote::query()
                    ->whereNull('tenant_id')
                    ->where('version', $this->normalizeVersion($name))
                    ->exists()) {
                    $tagsSkipped++;

                    continue;
                }

                $externalRef = 'github:tag:'.sha1($name);

                $note = ReleaseNote::query()->firstOrNew([
                    'tenant_id' => null,
                    'external_ref' => $externalRef,
                ]);

                $wasNew = ! $note->exists;
                $note->fill([
                    'title' => 'Tag '.$name,
                    'summary' => 'GitHub tag detected (no release notes published for this tag).',
                    'content' => 'This entry was created from a Git repository tag. Publish a GitHub Release to include full notes.',
                    'version' => $this->normalizeVersion($name),
                    'type' => 'maintenance',
                    'published_at' => now(),
                    'is_pinned' => false,
                ]);
                if ($wasNew && $createdByUserId !== null) {
                    $note->created_by = $createdByUserId;
                }
                $note->save();

                if ($wasNew) {
                    $tagsCreated++;
                }
            }
        }

        $this->repinLatestGithubNote();

        $message = sprintf(
            'GitHub sync finished: %d release(s) created, %d updated; %d tag note(s) created, %d tag(s) skipped.',
            $releasesCreated,
            $releasesUpdated,
            $tagsCreated,
            $tagsSkipped
        );

        if ($releasesBlocked403) {
            $message .= ' Note: GitHub blocked the Releases API (403) for this token—common with fine-grained PATs. '.
                'To import release notes from private repos, use a classic token with the repo scope, or confirm Contents: Read applies to Releases for your token. Tags were synced if permitted.';
        }

        return [
            'message' => $message,
            'releases_created' => $releasesCreated,
            'releases_updated' => $releasesUpdated,
            'tags_created' => $tagsCreated,
            'tags_skipped' => $tagsSkipped,
        ];
    }

    private function assertRepositoryAccessible(string $token, string $owner, string $repo): void
    {
        $path = '/repos/'.$this->encodePathSegment($owner).'/'.$this->encodePathSegment($repo);
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->get('https://api.github.com'.$path);

        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $hint = ' Repo: '.$owner.'/'.$repo.'. API: https://api.github.com'.$path;

        if ($status === 401) {
            throw new RuntimeException(
                'GitHub rejected the token (401). Create a new token, paste it into GITHUB_TOKEN, then run: php artisan config:clear'.$hint
            );
        }

        if ($status === 403) {
            throw new RuntimeException(
                'GitHub denied access to this repository (403). For a fine-grained token: add this exact repository, set Contents to Read, and if the repo is under an organization with SAML SSO, open the token on GitHub and click Authorize for that organization.'.$hint
            );
        }

        if ($status === 404) {
            throw new RuntimeException(
                'GitHub returned 404 for this repository. That usually means (1) OWNER or NAME does not match the URL github.com/OWNER/NAME, (2) the token cannot access this private repo (fine-grained token: select the repo + Contents: Read), or (3) org SSO is not authorized for this token. After fixing .env run: php artisan config:clear'.$hint
            );
        }

        throw new RuntimeException(
            'GitHub API request failed ('.$status.'): '.$response->body().$hint
        );
    }

    /**
     * Path segments for /repos/{owner}/{repo}/... must be encoded (spaces, unicode, etc.).
     */
    private function encodePathSegment(string $segment): string
    {
        return rawurlencode($segment);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, blocked403: bool}
     */
    private function fetchAllPages(string $path): array
    {
        $token = (string) config('github.token');
        $page = 1;
        /** @var list<array<string, mixed>> $rows */
        $rows = [];

        while (true) {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->get('https://api.github.com'.$path, [
                    'per_page' => 100,
                    'page' => $page,
                ]);

            if ($response->status() === 403 && $page === 1) {
                return ['rows' => [], 'blocked403' => true];
            }

            if (! $response->successful()) {
                throw new RuntimeException(
                    'GitHub API request failed ('.$response->status().'): '.$response->body()
                );
            }

            /** @var array<int, array<string, mixed>>|mixed $data */
            $data = $response->json();
            if (! is_array($data) || $data === []) {
                break;
            }

            foreach ($data as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }

            if (count($data) < 100) {
                break;
            }

            $page++;
        }

        return ['rows' => $rows, 'blocked403' => false];
    }

    private function parseGithubDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function makeSummary(string $body): ?string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');

        return $plain !== '' ? Str::limit($plain, 500) : null;
    }

    private function normalizeVersion(string $tagName): ?string
    {
        $v = trim($tagName);

        return $v !== '' ? Str::limit($v, 50, '') : null;
    }

    private function inferType(string $body, string $title): string
    {
        $haystack = strtolower($body.' '.$title);
        if (str_contains($haystack, 'security') || str_contains($haystack, 'cve')) {
            return 'security';
        }
        if (preg_match('/\b(fix|fixes|fixed|bug|patch)\b/i', $haystack) === 1) {
            return 'fix';
        }
        if (str_contains($haystack, 'maintenance')) {
            return 'maintenance';
        }

        return 'feature';
    }

    private function repinLatestGithubNote(): void
    {
        $latest = ReleaseNote::query()
            ->whereNull('tenant_id')
            ->whereNotNull('external_ref')
            ->where('external_ref', 'like', 'github:%')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            return;
        }

        ReleaseNote::query()
            ->whereNull('tenant_id')
            ->whereNotNull('external_ref')
            ->where('external_ref', 'like', 'github:%')
            ->where('id', '<>', $latest->getKey())
            ->update(['is_pinned' => false]);

        $latest->forceFill(['is_pinned' => true])->save();
    }
}
