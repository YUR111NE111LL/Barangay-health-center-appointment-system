<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\CloudinaryService;
use App\Support\TenantContentEmailNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $announcements = $tenant->announcements()->with('creator')->latest()->paginate(15);

        return view('tenant.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('tenant.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_published' => ['boolean'],
            'notify_users_by_email' => ['boolean'],
        ];
        if ($tenant->hasFeature('announcements_events')) {
            $rules['image'] = ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp'])->max(2048)];
        }
        $validated = $request->validate($rules);
        $validated['is_published'] = $request->boolean('is_published');
        if ($tenant->hasFeature('announcements_events') && $request->hasFile('image')) {
            $uploadResult = CloudinaryService::uploadImage(
                $request->file('image'),
                "announcements/{$tenant->id}",
                [
                    'transformation' => [
                        'width' => 1200,
                        'height' => 800,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'format' => 'auto',
                    ],
                ]
            );
            if ($uploadResult) {
                $validated['image_path'] = $uploadResult['secure_url'];
            }
        }
        /** @var Announcement $announcement */
        $announcement = $tenant->announcements()->create(array_merge($validated, [
            'created_by_user_id' => auth()->id(),
        ]));

        if ($request->boolean('notify_users_by_email') && $announcement->is_published) {
            TenantContentEmailNotifier::queueAnnouncementEmails($tenant, $announcement);
        }

        return redirect()->route('backend.announcements.index')->with('success', 'Announcement created.');
    }

    public function show(Announcement $announcement): View
    {
        if ($announcement->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $announcement->loadMissing('creator');

        return view('tenant.announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement): View
    {
        if ($announcement->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return view('tenant.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        if ($announcement->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        $tenant = auth()->user()->tenant;
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_published' => ['boolean'],
            'notify_users_by_email' => ['boolean'],
        ];
        if ($tenant->hasFeature('announcements_events')) {
            $rules['image'] = ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp'])->max(2048)];
        }
        $validated = $request->validate($rules);
        $validated['is_published'] = $request->boolean('is_published');
        if ($tenant->hasFeature('announcements_events')) {
            if ($request->hasFile('image')) {
                // Delete old image from Cloudinary if exists
                if ($announcement->image_path) {
                    if (str_contains($announcement->image_path, 'cloudinary.com')) {
                        $publicId = basename(parse_url($announcement->image_path, PHP_URL_PATH), '.'.pathinfo($announcement->image_path, PATHINFO_EXTENSION));
                        CloudinaryService::delete($publicId, 'image');
                    } else {
                        Storage::disk('public')->delete($announcement->image_path);
                    }
                }
                // Upload new image to Cloudinary
                $uploadResult = CloudinaryService::uploadImage(
                    $request->file('image'),
                    "announcements/{$tenant->id}",
                    [
                        'transformation' => [
                            'width' => 1200,
                            'height' => 800,
                            'crop' => 'limit',
                            'quality' => 'auto',
                            'format' => 'auto',
                        ],
                    ]
                );
                if ($uploadResult) {
                    $validated['image_path'] = $uploadResult['secure_url'];
                }
            }
            if ($request->boolean('remove_image') && $announcement->image_path) {
                if (str_contains($announcement->image_path, 'cloudinary.com')) {
                    $publicId = basename(parse_url($announcement->image_path, PHP_URL_PATH), '.'.pathinfo($announcement->image_path, PATHINFO_EXTENSION));
                    CloudinaryService::delete($publicId, 'image');
                } else {
                    Storage::disk('public')->delete($announcement->image_path);
                }
                $validated['image_path'] = null;
            }
        }
        $announcement->update($validated);

        if ($request->boolean('notify_users_by_email') && $announcement->is_published) {
            TenantContentEmailNotifier::queueAnnouncementEmails($tenant, $announcement->fresh());
        }

        return redirect()->route('backend.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        if ($announcement->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        if ($announcement->image_path) {
            if (str_contains($announcement->image_path, 'cloudinary.com')) {
                $publicId = basename(parse_url($announcement->image_path, PHP_URL_PATH), '.'.pathinfo($announcement->image_path, PATHINFO_EXTENSION));
                CloudinaryService::delete($publicId, 'image');
            } else {
                Storage::disk('public')->delete($announcement->image_path);
            }
        }
        $announcement->delete();

        return redirect()->route('backend.announcements.index')->with('success', 'Announcement deleted.');
    }
}
