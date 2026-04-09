<?php

namespace App\Http\Controllers\TenantUser;

use App\Events\ProfileUpdated;
use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Resident profile: view and edit own profile (tenant-scoped).
 */
class ProfileController extends Controller
{
    public function show(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load(['tenant.domains']);

        return view('tenant-user.profile.show', compact('user'));
    }

    public function edit(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return view('tenant-user.profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'purok_address' => ['nullable', 'string', 'max:255'],
            'profile_picture' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp'])->max(2048)],
        ];
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->purok_address = $validated['purok_address'] ?? null;

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture from Cloudinary if exists
            if ($user->profile_picture) {
                if (str_contains($user->profile_picture, 'cloudinary.com')) {
                    $publicId = basename(parse_url($user->profile_picture, PHP_URL_PATH), '.'.pathinfo($user->profile_picture, PATHINFO_EXTENSION));
                    CloudinaryService::delete($publicId, 'image');
                } else {
                    // Legacy local storage cleanup
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_picture);
                }
            }

            // Upload to Cloudinary
            $uploadResult = CloudinaryService::uploadImage(
                $request->file('profile_picture'),
                "profile_pictures/{$user->id}",
                [
                    'transformation' => [
                        'width' => 400,
                        'height' => 400,
                        'crop' => 'fill',
                        'gravity' => 'face',
                        'quality' => 'auto',
                        'format' => 'auto',
                    ],
                ]
            );

            if ($uploadResult) {
                $user->profile_picture = $uploadResult['secure_url'];
            } else {
                return back()->withInput()->withErrors(['profile_picture' => 'Failed to upload profile picture. Please try again.']);
            }
        }

        // Handle profile picture removal
        if ($request->boolean('remove_profile_picture')) {
            if ($user->profile_picture) {
                if (str_contains($user->profile_picture, 'cloudinary.com')) {
                    $publicId = basename(parse_url($user->profile_picture, PHP_URL_PATH), '.'.pathinfo($user->profile_picture, PATHINFO_EXTENSION));
                    CloudinaryService::delete($publicId, 'image');
                } else {
                    // Legacy local storage cleanup
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_picture);
                }
            }
            $user->profile_picture = null;
        }

        if (! empty($validated['password'] ?? null)) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        event(new ProfileUpdated($user));

        return redirect()->route('resident.profile.show')->with('success', 'Profile updated successfully.');
    }
}
