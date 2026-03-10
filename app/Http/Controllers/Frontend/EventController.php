<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $events = $tenant->events()
            ->where('is_published', true)
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->paginate(10);
        $hasAnnouncementsEvents = $tenant->hasFeature('announcements_events');
        return view('frontend.events.index', compact('events', 'hasAnnouncementsEvents'));
    }

    public function show(Event $event): View
    {
        if ($event->tenant_id !== auth()->user()->tenant_id || ! $event->is_published) {
            abort(404);
        }
        $hasAnnouncementsEvents = auth()->user()->tenant?->hasFeature('announcements_events') ?? false;
        return view('frontend.events.show', compact('event', 'hasAnnouncementsEvents'));
    }
}
