<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $events = $tenant->events()->orderBy('event_date', 'desc')->orderBy('event_time')->paginate(15);

        return view('backend.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('backend.events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_published' => ['boolean'],
        ];
        if ($tenant->hasFeature('announcements_events')) {
            $rules['image'] = ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp'])->max(2048)];
        }
        $validated = $request->validate($rules);
        $validated['is_published'] = $request->boolean('is_published');
        if ($tenant->hasFeature('announcements_events') && $request->hasFile('image')) {
            $uploadResult = CloudinaryService::uploadImage(
                $request->file('image'),
                "events/{$tenant->id}",
                [
                    'transformation' => [
                        'width' => 1200,
                        'height' => 800,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'format' => 'auto'
                    ]
                ]
            );
            if ($uploadResult) {
                $validated['image_path'] = $uploadResult['secure_url'];
            }
        }
        $tenant->events()->create($validated);

        return redirect()->route('backend.events.index')->with('success', 'Event created.');
    }

    public function show(Event $event): View
    {
        $this->authorizeTenant($event);
        return view('backend.events.show', compact('event'));
    }

    public function edit(Event $event): View
    {
        $this->authorizeTenant($event);
        return view('backend.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeTenant($event);
        $tenant = auth()->user()->tenant;
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_published' => ['boolean'],
        ];
        if ($tenant->hasFeature('announcements_events')) {
            $rules['image'] = ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp'])->max(2048)];
        }
        $validated = $request->validate($rules);
        $validated['is_published'] = $request->boolean('is_published');
        if ($tenant->hasFeature('announcements_events')) {
            if ($request->hasFile('image')) {
                // Delete old image from Cloudinary if exists
                if ($event->image_path) {
                    if (str_contains($event->image_path, 'cloudinary.com')) {
                        $publicId = basename(parse_url($event->image_path, PHP_URL_PATH), '.' . pathinfo($event->image_path, PATHINFO_EXTENSION));
                        CloudinaryService::delete($publicId, 'image');
                    } else {
                        Storage::disk('public')->delete($event->image_path);
                    }
                }
                // Upload new image to Cloudinary
                $uploadResult = CloudinaryService::uploadImage(
                    $request->file('image'),
                    "events/{$tenant->id}",
                    [
                        'transformation' => [
                            'width' => 1200,
                            'height' => 800,
                            'crop' => 'limit',
                            'quality' => 'auto',
                            'format' => 'auto'
                        ]
                    ]
                );
                if ($uploadResult) {
                    $validated['image_path'] = $uploadResult['secure_url'];
                }
            }
            if ($request->boolean('remove_image') && $event->image_path) {
                if (str_contains($event->image_path, 'cloudinary.com')) {
                    $publicId = basename(parse_url($event->image_path, PHP_URL_PATH), '.' . pathinfo($event->image_path, PATHINFO_EXTENSION));
                    CloudinaryService::delete($publicId, 'image');
                } else {
                    Storage::disk('public')->delete($event->image_path);
                }
                $validated['image_path'] = null;
            }
        }
        $event->update($validated);

        return redirect()->route('backend.events.index')->with('success', 'Event updated.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorizeTenant($event);
        if ($event->image_path) {
            if (str_contains($event->image_path, 'cloudinary.com')) {
                $publicId = basename(parse_url($event->image_path, PHP_URL_PATH), '.' . pathinfo($event->image_path, PATHINFO_EXTENSION));
                CloudinaryService::delete($publicId, 'image');
            } else {
                Storage::disk('public')->delete($event->image_path);
            }
        }
        $event->delete();
        return redirect()->route('backend.events.index')->with('success', 'Event deleted.');
    }

    private function authorizeTenant(Event $event): void
    {
        if ($event->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
