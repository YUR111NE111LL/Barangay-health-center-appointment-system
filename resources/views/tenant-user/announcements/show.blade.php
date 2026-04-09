@extends('tenant-user.layouts.app')
@section('title', $announcement->title)
@section('content')
<div class="mb-6">
    <a href="{{ route('resident.announcements.index') }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">Back to announcements</a>
</div>
<article class="{{ ($hasAnnouncementsEvents ?? false) ? 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60' : 'rounded-xl border border-slate-200 bg-white' }}">
    @if(($hasAnnouncementsEvents ?? false) && $announcement->image_path)
        <img src="{{ $announcement->image_url }}" alt="" class="w-full object-cover" style="max-height: 320px;">
    @endif
    <div class="{{ ($hasAnnouncementsEvents ?? false) ? 'p-6 sm:p-8' : 'p-4 sm:p-6' }}">
        <h1 class="text-2xl font-bold text-slate-800">{{ $announcement->title }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $announcement->created_at->format('F d, Y') }}</p>
        <div class="mt-4 whitespace-pre-wrap text-slate-700">{{ $announcement->body }}</div>
    </div>
</article>
@endsection
