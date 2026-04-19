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
                $query->whereNull('tenant_id');
                if ($tenant) {
                    $query->orWhere('tenant_id', $tenant->id);
                }
            })
            ->whereNotNull('published_at')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $isAdmin = $user?->role === User::ROLE_HEALTH_CENTER_ADMIN;
        $isResidentPortal = request()->routeIs('resident.*');
        $isStaffPortal = ! $isResidentPortal;

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
        $quickActions = $this->quickActions($routeBase, $isResidentPortal, $isStaffPortal, $isAdmin);
        $roleGuides = $this->roleGuides($isResidentPortal, $isAdmin);
        $ticketChecklist = $this->ticketChecklist($isResidentPortal);

        return view('tenant.support.help', compact(
            'tenant',
            'recentNotes',
            'faqs',
            'routeBase',
            'supportContact',
            'isAdmin',
            'quickActions',
            'roleGuides',
            'ticketChecklist'
        ));
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

    /**
     * @return array<int, array{label:string,description:string,url:string}>
     */
    private function quickActions(string $routeBase, bool $isResidentPortal, bool $isStaffPortal, bool $isAdmin): array
    {
        $items = [
            [
                'label' => 'Create support ticket',
                'description' => 'Report bugs, login problems, or data issues with screenshots.',
                'url' => route($routeBase.'.tickets.create'),
            ],
            [
                'label' => 'View support tickets',
                'description' => 'Check replies and ticket status updates.',
                'url' => route($routeBase.'.tickets.index'),
            ],
            [
                'label' => 'Read release notes',
                'description' => 'See fixes and new features before they affect your workflow.',
                'url' => route($routeBase.'.updates.index'),
            ],
        ];

        if ($isResidentPortal) {
            $items[] = [
                'label' => 'Book appointment',
                'description' => 'Open booking form and try another slot if one is full.',
                'url' => route('resident.book'),
            ];
            $items[] = [
                'label' => 'Update profile',
                'description' => 'Keep your name and contact details accurate for confirmations.',
                'url' => route('resident.profile.show'),
            ];
        }

        if ($isStaffPortal) {
            $items[] = [
                'label' => 'Open appointments',
                'description' => 'Check pending or failed appointment workflows quickly.',
                'url' => route('backend.appointments.index'),
            ];
        }

        if ($isAdmin) {
            $items[] = [
                'label' => 'Review approvals',
                'description' => 'Approve or deny pending staff and nurse accounts.',
                'url' => route('backend.pending-approvals.index'),
            ];
            $items[] = [
                'label' => 'Manage users',
                'description' => 'Verify roles and deactivate incorrect accounts safely.',
                'url' => route('backend.users.index'),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{title:string,steps:array<int,string>}>
     */
    private function roleGuides(bool $isResidentPortal, bool $isAdmin): array
    {
        if ($isResidentPortal) {
            return [
                [
                    'title' => 'If booking fails',
                    'steps' => [
                        'Check that your selected date/time is still available.',
                        'Confirm your internet connection and refresh once.',
                        'Try booking a different slot, then submit again.',
                        'If it still fails, create a support ticket and include the exact time of failure.',
                    ],
                ],
                [
                    'title' => 'If you cannot log in',
                    'steps' => [
                        'Use the Forgot password option from the login page.',
                        'Make sure you are on your barangay domain, not the central app.',
                        'If account status is pending, wait for approval then try again.',
                    ],
                ],
            ];
        }

        $guides = [
            [
                'title' => 'If dashboard numbers look wrong',
                'steps' => [
                    'Refresh the page once to pull latest tenant updates.',
                    'Check if filters/date range are limiting records.',
                    'Open release notes to confirm any recent behavior changes.',
                    'Report a ticket with sample record IDs and screenshots.',
                ],
            ],
            [
                'title' => 'If staff access is blocked',
                'steps' => [
                    'Open Users and verify role assignment.',
                    'Check Pending approvals for unapproved accounts.',
                    'Confirm the user is signing in through the correct barangay domain.',
                ],
            ],
        ];

        if ($isAdmin) {
            $guides[] = [
                'title' => 'Before escalating to Super Admin',
                'steps' => [
                    'Capture ticket number and exact error text.',
                    'Include affected user email and action they were doing.',
                    'Attach screenshots and approximate timestamp.',
                ],
            ];
        }

        return $guides;
    }

    /**
     * @return array<int, string>
     */
    private function ticketChecklist(bool $isResidentPortal): array
    {
        $base = [
            'What page were you on?',
            'What did you expect to happen?',
            'What actually happened (exact error message)?',
            'When did it happen (date and time)?',
            'Can you attach a screenshot?',
        ];

        if ($isResidentPortal) {
            $base[] = 'Include your booking date/time and service selected (if related).';
        } else {
            $base[] = 'Include affected user email and role (if related).';
        }

        return $base;
    }
}
