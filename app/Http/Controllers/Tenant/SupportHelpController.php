<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ReleaseNote;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportHelpController extends Controller
{
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $tenant = $user?->tenant;
        $routeBase = request()->routeIs('resident.*') ? 'resident.support' : 'backend.support';
        $recentNotes = ReleaseNote::query()
            ->where(function ($query) use ($tenant): void {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->whereNotNull('published_at')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $faqs = [
            [
                'q' => 'How do I book an appointment?',
                'a' => 'Residents can go to the Resident dashboard and use the booking form.',
            ],
            [
                'q' => 'How can I reset my password?',
                'a' => 'Use the Forgot password link on the login page, then check your email for reset instructions.',
            ],
            [
                'q' => 'Why can I not access some pages?',
                'a' => 'Your role permissions may not include that feature. Contact your Health Center Admin.',
            ],
            [
                'q' => 'Where can I report a bug?',
                'a' => 'Open Support Tickets and click "Create ticket" with details and screenshots.',
            ],
        ];

        $supportContact = $this->resolveSupportContact($user);

        return view('tenant.support.help', compact('tenant', 'recentNotes', 'faqs', 'routeBase', 'supportContact'));
    }

    /**
     * Resolve support contact details based on the current tenant user's role.
     *
     * @return array{email:string,contact:string,office_hours:string}
     */
    private function resolveSupportContact(?User $user): array
    {
        $tenant = $user?->tenant;
        $defaultEmail = 'bayronyurineil@gmail.com';
        $fallbackContact = (string) ($tenant?->contact_number ?: 'Not set');
        $baseOfficeHours = 'Mon-Fri, 8:00 AM - 5:00 PM';

        if (! $user || ! $tenant) {
            return [
                'email' => $defaultEmail,
                'contact' => $fallbackContact,
                'office_hours' => $baseOfficeHours,
            ];
        }

        if ($user->role === User::ROLE_HEALTH_CENTER_ADMIN) {
            return [
                'email' => $defaultEmail,
                'contact' => $fallbackContact,
                'office_hours' => 'Mon-Sat, 8:00 AM - 6:00 PM',
            ];
        }

        // Staff/Nurse users are directed to their Barangay Admin.
        if (in_array($user->role, [User::ROLE_STAFF, User::ROLE_NURSE], true)) {
            $admin = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('role', User::ROLE_HEALTH_CENTER_ADMIN)
                ->orderBy('id')
                ->first();

            return [
                'email' => (string) ($admin?->email ?: ($tenant->email ?: $defaultEmail)),
                'contact' => $fallbackContact,
                'office_hours' => $baseOfficeHours,
            ];
        }

        // Resident and resident-like users use tenant support details.
        return [
            'email' => (string) ($tenant->email ?: $defaultEmail),
            'contact' => $fallbackContact,
            'office_hours' => $baseOfficeHours,
        ];
    }
}
