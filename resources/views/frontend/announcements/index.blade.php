@extends('frontend.layouts.app')
@section('title', 'Health Announcements')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Health Announcements</h1>
    <p class="mt-1 text-slate-500">Updates and tips from your barangay health center.</p>
</div>
<div class="{{ ($hasAnnouncementsEvents ?? false) ? 'space-y-4' : 'divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white' }}">
    @forelse($announcements as $a)
    <article class="{{ ($hasAnnouncementsEvents ?? false) ? 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60' : 'p-4' }}">
        @if(($hasAnnouncementsEvents ?? false) && $a->image_path)
            <a href="{{ route('resident.announcements.show', $a) }}" class="block">
                <img src="{{ $a->image_url }}" alt="" class="h-48 w-full object-cover">
            </a>
        @endif
        <div class="{{ ($hasAnnouncementsEvents ?? false) ? 'p-4 sm:p-6' : '' }}">
            <h2 class="text-lg font-semibold text-slate-800">
                <a href="{{ route('resident.announcements.show', $a) }}" class="hover:text-teal-600">{{ $a->title }}</a>
            </h2>
            <p class="mt-1 text-sm text-slate-500">{{ $a->created_at->format('M d, Y') }}</p>
            <p class="mt-2 text-slate-600">{{ Str::limit(strip_tags($a->body), ($hasAnnouncementsEvents ?? false) ? 200 : 120) }}</p>
            <a href="{{ route('resident.announcements.show', $a) }}" class="mt-3 inline-block text-sm font-medium text-teal-600 hover:text-teal-700">Read more</a>
        </div>
    </article>
    @empty
    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center">
        <p class="text-slate-500">No announcements at the moment. Check back later.</p>
    </div>
    @endforelse
</div>
<div class="mt-6">{{ $announcements->links() }}</div>
@endsection
