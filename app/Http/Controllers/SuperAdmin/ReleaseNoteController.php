<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReleaseNote;
use App\Support\GitHubReleasePublisher;
use App\Support\GitHubReleaseSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReleaseNoteController extends Controller
{
    public function index(): View
    {
        $notes = ReleaseNote::query()
            ->whereNull('tenant_id')
            ->whereNotNull('published_at')
            ->with('creator:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(15);

        $latestVersionNote = ReleaseNote::query()
            ->whereNull('tenant_id')
            ->whereNotNull('published_at')
            ->whereNotNull('version')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first(['version', 'published_at']);

        $releaseStatus = app(GitHubReleaseSyncService::class)->checkLatestReleaseStatus();

        return view('superadmin.updates.index', compact('notes', 'latestVersionNote', 'releaseStatus'));
    }

    public function create(): View
    {
        return view('superadmin.updates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'version' => ['required', 'string', 'max:50', Rule::requiredIf(fn () => $request->boolean('create_github_release'))],
            'type' => ['required', 'in:feature,fix,maintenance,security'],
            'is_pinned' => ['boolean'],
            'published_at' => ['required', 'date'],
            'create_github_release' => ['boolean'],
            'github_target_branch' => ['nullable', 'string', 'max:255'],
        ]);

        ReleaseNote::create([
            'tenant_id' => null,
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'summary' => $validated['summary'],
            'content' => $validated['content'],
            'version' => $validated['version'],
            'type' => $validated['type'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $validated['published_at'],
        ]);

        $successMessage = 'Global update published.';
        $githubWarning = null;

        if ($request->boolean('create_github_release')) {
            $bodyParts = array_filter([
                $validated['summary'] ?? null,
                $validated['content'] ?? null,
            ]);
            $releaseBody = $bodyParts !== [] ? implode("\n\n", $bodyParts) : null;

            try {
                app(GitHubReleasePublisher::class)->publishRelease(
                    tagName: (string) $validated['version'],
                    releaseName: $validated['title'],
                    body: $releaseBody,
                    targetBranch: $validated['github_target_branch'] ?? null,
                );
                $successMessage .= ' GitHub release created.';
            } catch (\Throwable $e) {
                report($e);
                $githubWarning = 'Saved in BHCAS, but GitHub release failed: '.$e->getMessage();
            }
        }

        $redirect = redirect()
            ->route('super-admin.updates.index')
            ->with('success', $successMessage);

        if ($githubWarning !== null) {
            $redirect->with('warning', $githubWarning);
        }

        return $redirect;
    }

    public function edit(ReleaseNote $update): View
    {
        $this->ensureGlobalNote($update);

        return view('superadmin.updates.edit', ['note' => $update]);
    }

    public function update(Request $request, ReleaseNote $update): RedirectResponse
    {
        $this->ensureGlobalNote($update);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'version' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:feature,fix,maintenance,security'],
            'is_pinned' => ['boolean'],
            'published_at' => ['required', 'date'],
        ]);

        $update->update([
            'title' => $validated['title'],
            'summary' => $validated['summary'],
            'content' => $validated['content'],
            'version' => $validated['version'],
            'type' => $validated['type'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $validated['published_at'],
        ]);

        return redirect()->route('super-admin.updates.index')->with('success', 'Global update updated.');
    }

    public function destroy(ReleaseNote $update): RedirectResponse
    {
        $this->ensureGlobalNote($update);
        $update->delete();

        return redirect()->route('super-admin.updates.index')->with('success', 'Global update deleted.');
    }

    private function ensureGlobalNote(ReleaseNote $note): void
    {
        if ($note->tenant_id !== null) {
            abort(404);
        }
    }
}
