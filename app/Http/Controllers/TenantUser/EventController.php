<?php

namespace App\Http\Controllers\TenantUser;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $tenant = $user->tenant;
        $today = now()->timezone((string) config('bhcas.display_timezone', 'Asia/Manila'))->toDateString();
        $events = $tenant->events()
            ->where('is_published', true)
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->paginate(10);
        $hasAnnouncementsEvents = $tenant->hasFeature('announcements_events');

        return view('tenant-user.events.index', compact('events', 'hasAnnouncementsEvents'));
    }

    public function show(Event $event): View
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($event->tenant_id !== $user->tenant_id || ! $event->is_published) {
            abort(404);
        }
        $hasAnnouncementsEvents = $user->tenant?->hasFeature('announcements_events') ?? false;

        return view('tenant-user.events.show', compact('event', 'hasAnnouncementsEvents'));
    }
}
