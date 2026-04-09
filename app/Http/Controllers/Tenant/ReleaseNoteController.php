<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ReleaseNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReleaseNoteController extends Controller
{
    public function index(): View
    {
        $tenant = Auth::user()?->tenant;
        $isAdmin = Auth::user()?->role === 'Health Center Admin';
        $routeBase = request()->routeIs('resident.*') ? 'resident.support' : 'backend.support';

        $notes = ReleaseNote::query()
            ->where(function ($query) use ($tenant): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
            })
            ->whereNotNull('published_at')
            ->with('creator:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('tenant.support.updates.index', compact('notes', 'isAdmin', 'routeBase'));
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('tenant.support.updates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $tenant = Auth::user()?->tenant;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:feature,fix,maintenance,security'],
            'is_pinned' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        ReleaseNote::create([
            'tenant_id' => $tenant->id,
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'content' => $validated['content'] ?? null,
            'version' => $validated['version'] ?? null,
            'type' => $validated['type'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $validated['published_at'] ?? now(),
        ]);

        return redirect()->route('backend.support.updates.index')->with('success', 'Update note published.');
    }

    public function edit(ReleaseNote $update): View
    {
        $this->ensureAdmin();
        $this->ensureTenantOwned($update);

        return view('tenant.support.updates.edit', ['note' => $update]);
    }

    public function update(Request $request, ReleaseNote $update): RedirectResponse
    {
        $this->ensureAdmin();
        $this->ensureTenantOwned($update);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:feature,fix,maintenance,security'],
            'is_pinned' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $update->update([
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'content' => $validated['content'] ?? null,
            'version' => $validated['version'] ?? null,
            'type' => $validated['type'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $validated['published_at'] ?? now(),
        ]);

        return redirect()->route('backend.support.updates.index')->with('success', 'Update note updated.');
    }

    public function destroy(ReleaseNote $update): RedirectResponse
    {
        $this->ensureAdmin();
        $this->ensureTenantOwned($update);
        $update->delete();

        return redirect()->route('backend.support.updates.index')->with('success', 'Update note deleted.');
    }

    private function ensureAdmin(): void
    {
        if (Auth::user()?->role !== 'Health Center Admin') {
            abort(403);
        }
    }

    private function ensureTenantOwned(ReleaseNote $note): void
    {
        if ((int) $note->tenant_id !== (int) Auth::user()?->tenant_id) {
            abort(403);
        }
    }
}
