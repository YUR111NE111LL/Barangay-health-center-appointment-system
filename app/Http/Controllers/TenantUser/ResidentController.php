<?php

namespace App\Http\Controllers\TenantUser;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();
        $tenant = $user->tenant;
        $canBook = $user->hasTenantPermission('book appointments');
        $appointments = $user->appointments()
            ->with('service')
            ->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->paginate(10);
        $announcements = $tenant->announcements()
            ->where('is_published', true)
            ->latest()
            ->take(3)
            ->get();
        $today = now()->timezone((string) config('bhcas.display_timezone', 'Asia/Manila'))->toDateString();
        $upcomingEvents = $tenant->events()
            ->where('is_published', true)
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->take(3)
            ->get();

        $hasAnnouncementsEvents = $tenant->hasFeature('announcements_events');

        return view('tenant-user.resident.dashboard', compact('appointments', 'announcements', 'upcomingEvents', 'canBook', 'hasAnnouncementsEvents'));
    }
}
