<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReleaseNote;
use App\Support\GitHubReleaseSyncService;
use App\Support\SystemUpdateCommandRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class GitHubReleaseSyncController extends Controller
{
    public function __invoke(GitHubReleaseSyncService $sync, SystemUpdateCommandRunner $runner): RedirectResponse
    {
        $preSyncStatus = $sync->checkLatestReleaseStatus();

        $canCheckGithub = is_array($preSyncStatus) && (($preSyncStatus['ok'] ?? false) === true);
        $hasUpdate = is_array($preSyncStatus) && (($preSyncStatus['has_update'] ?? false) === true);

        if (! $canCheckGithub) {
            $statusMessage = is_array($preSyncStatus) && is_string($preSyncStatus['message'] ?? null)
                ? (string) $preSyncStatus['message']
                : 'Could not check GitHub release status right now.';

            return redirect()
                ->route('super-admin.updates.index')
                ->with('error', $statusMessage.' Sync was skipped to avoid partial or broken updates.');
        }

        if (! $hasUpdate) {
            $statusMessage = is_array($preSyncStatus) && is_string($preSyncStatus['message'] ?? null)
                ? (string) $preSyncStatus['message']
                : 'No release update available.';

            return redirect()
                ->route('super-admin.updates.index')
                ->with('success', $statusMessage.' Sync skipped because the system is already up to date.');
        }

        try {
            $result = $sync->sync(Auth::id());
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('super-admin.updates.index')
                ->with('error', $e->getMessage());
        }

        $commandResult = $runner->run();

        $message = $result['message'].' '.$commandResult['message'];
        $syncedVersion = $this->latestGitHubVersionAfterSync();

        if ($syncedVersion !== null) {
            $this->publishTenantSyncNotice($syncedVersion);
            $message .= ' Use this as your current version: '.$syncedVersion.'. All tenants were notified in Support & Updates.';
        }

        return redirect()
            ->route('super-admin.updates.index')
            ->with($commandResult['ok'] ? 'success' : 'error', $message);
    }

    private function latestGitHubVersionAfterSync(): ?string
    {
        /** @var string|null $version */
        $version = ReleaseNote::query()
            ->whereNull('tenant_id')
            ->whereNotNull('version')
            ->where('external_ref', 'like', 'github:release:%')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->value('version');

        if (is_string($version) && trim($version) !== '') {
            return trim($version);
        }

        /** @var string|null $fallback */
        $fallback = ReleaseNote::query()
            ->whereNull('tenant_id')
            ->whereNotNull('version')
            ->where('external_ref', 'like', 'github:%')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->value('version');

        return is_string($fallback) && trim($fallback) !== '' ? trim($fallback) : null;
    }

    private function publishTenantSyncNotice(string $version): void
    {
        $normalizedVersion = ltrim(trim($version), 'vV');
        $label = 'v'.$normalizedVersion;
        $now = now();

        $notice = ReleaseNote::query()->firstOrNew([
            'tenant_id' => null,
            'external_ref' => 'github:sync-notice:'.$normalizedVersion,
        ]);

        $notice->fill([
            'created_by' => Auth::id(),
            'title' => 'System update synced ('.$label.')',
            'summary' => 'A new synced version is available. Please use '.$label.' as your current version.',
            'content' => 'The Super Admin synced GitHub updates and applied system commands. This version is now recommended as the current system version.',
            'version' => $normalizedVersion,
            'type' => 'maintenance',
            'is_pinned' => false,
            'published_at' => $now,
        ]);
        $notice->save();
    }
}
