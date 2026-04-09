<?php

namespace App\Http\Controllers\TenantUser;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $announcements = $tenant->announcements()
            ->where('is_published', true)
            ->latest()
            ->paginate(10);
        $hasAnnouncementsEvents = $tenant->hasFeature('announcements_events');

        return view('tenant-user.announcements.index', compact('announcements', 'hasAnnouncementsEvents'));
    }

    public function show(Announcement $announcement): View
    {
        if ($announcement->tenant_id !== auth()->user()->tenant_id || ! $announcement->is_published) {
            abort(404);
        }
        $hasAnnouncementsEvents = auth()->user()->tenant?->hasFeature('announcements_events') ?? false;

        return view('tenant-user.announcements.show', compact('announcement', 'hasAnnouncementsEvents'));
    }
}
