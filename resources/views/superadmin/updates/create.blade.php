@extends('superadmin.layouts.app')

@section('title', 'Publish Global Update')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Publish Global Update</h1>
    <a href="{{ route('super-admin.updates.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Back to updates</a>
</div>

<form action="{{ route('super-admin.updates.store') }}" method="POST" class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    @csrf
    @include('tenant.support.updates.partials.form')
    <div class="rounded-xl border border-slate-200 bg-slate-50/90 p-4 space-y-3">
        <p class="text-sm font-medium text-slate-800">GitHub (optional)</p>
        <label class="inline-flex items-start gap-2 text-sm text-slate-700">
            <input type="checkbox" name="create_github_release" value="1" {{ old('create_github_release') ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
            <span>Also create a GitHub release (uses <strong>Version</strong> as the tag; targets the branch below or the repo default).</span>
        </label>
        <p class="ml-6 text-xs text-slate-500">Requires <code class="rounded bg-slate-100 px-1">GITHUB_TOKEN</code> with write access (classic: <code class="rounded bg-slate-100 px-1">repo</code>; fine-grained: Contents read/write for this repo). Your update is always saved in BHCAS first; if GitHub fails, you will see a warning.</p>
        <div class="ml-6 max-w-md">
            <label for="github_target_branch" class="mb-1 block text-xs font-medium text-slate-600">Target branch (optional)</label>
            <input type="text" name="github_target_branch" id="github_target_branch" value="{{ old('github_target_branch', config('github.default_branch')) }}" placeholder="e.g. main" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-violet-700 px-4 py-2.5 font-medium text-white hover:bg-violet-800">Publish</button>
        <a href="{{ route('super-admin.updates.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
