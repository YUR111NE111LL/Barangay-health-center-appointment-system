<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TenantRoleEffectivePermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    /**
     * Roles available when creating a user (Barangay Admin only may create users).
     *
     * @return array<string, string> role => label
     */
    private function assignableRolesForCreate(): array
    {
        return [
            User::ROLE_RESIDENT => 'Resident (Patient)',
            User::ROLE_STAFF => 'Staff',
            User::ROLE_NURSE => 'Nurse / Midwife',
            User::ROLE_HEALTH_CENTER_ADMIN => 'Health Center Admin',
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedRoleKeysForStore(): array
    {
        return array_keys($this->assignableRolesForCreate());
    }

    /**
     * List users belonging to the current tenant (actual users: residents, staff, etc.).
     */
    public function index(Request $request): View
    {
        $query = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('role')
            ->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $users = $query->paginate(15);

        $tenant = auth()->user()->tenant;
        $tenantId = auth()->user()->tenant_id;
        $canAddUser = $tenant ? $tenant->canAddUser() : false;
        $userCount = User::where('tenant_id', $tenantId)->count();
        $maxUsers = $tenant ? $tenant->maxUsersFromPlan() : 0;
        $planName = $tenant && $tenant->plan ? $tenant->plan->name : null;
        $canAddUser = $canAddUser && auth()->user()?->role === User::ROLE_HEALTH_CENTER_ADMIN;
        $baseRoleOptions = array_keys($this->assignableRolesForCreate());
        $roleOptions = collect($baseRoleOptions);
        if (Schema::hasTable('tenant_role_permissions')) {
            $customRoles = DB::table('tenant_role_permissions')
                ->where('tenant_id', $tenantId)
                ->whereNotIn('role_name', array_merge($baseRoleOptions, [User::ROLE_SUPER_ADMIN]))
                ->distinct()
                ->orderBy('role_name')
                ->pluck('role_name');
            $roleOptions = $roleOptions->merge($customRoles);
        }
        $userRoles = User::where('tenant_id', $tenantId)
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->distinct()
            ->orderBy('role')
            ->pluck('role');
        $roleOptions = $roleOptions
            ->merge($userRoles)
            ->filter(static fn ($role): bool => is_string($role) && $role !== '')
            ->unique()
            ->values();

        $tenantRolePermissionsByKey = [];
        if ($tenantId !== null && Schema::hasTable('tenant_role_permissions')) {
            $tenantRolePermissionsByKey = TenantRoleEffectivePermissions::groupedByRoleKey((int) $tenantId);
        }

        return view('tenant.users.index', compact(
            'users',
            'canAddUser',
            'userCount',
            'maxUsers',
            'planName',
            'roleOptions',
            'tenantRolePermissionsByKey',
        ));
    }

    /**
     * Show form to create a new user (Barangay / Health Center Admin only).
     */
    public function create(): View|RedirectResponse
    {
        $this->authorize('manage users');

        $tenant = auth()->user()->tenant;
        if (! $tenant || ! $tenant->canAddUser()) {
            return redirect()->route('backend.users.index')
                ->with('error', 'User limit for your plan has been reached. Upgrade your plan to add more users.');
        }

        $roles = $this->assignableRolesForCreate();

        return view('tenant.users.create', compact('roles'));
    }

    /**
     * Store a new user (actual user account for the tenant).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage users');

        $tenant = auth()->user()->tenant;
        $tenantId = auth()->user()->tenant_id;

        if (! $tenant || ! $tenant->canAddUser()) {
            return redirect()->route('backend.users.index')
                ->with('error', 'User limit for your plan has been reached. Upgrade your plan to add more users.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where('tenant_id', $tenantId),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($this->allowedRoleKeysForStore())],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->syncRoles([$validated['role']]);

        $message = $validated['password']
            ? 'User created. They can now log in with this email and password.'
            : 'User created. They will need to set up Google login or contact you for password setup.';

        return redirect()->route('backend.users.index')->with('success', $message);
    }

    /**
     * Redirect to Google to create a user via Google account.
     */
    public function createWithGoogle(Request $request): RedirectResponse
    {
        $this->authorize('manage users');

        $tenant = auth()->user()->tenant;
        $tenantId = auth()->user()->tenant_id;

        if (! $tenant || ! $tenant->canAddUser()) {
            return redirect()->route('backend.users.index')
                ->with('error', 'User limit for your plan has been reached. Upgrade your plan to add more users.');
        }

        $state = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode([
            'action' => 'create_user',
            'tenant_id' => $tenantId,
            'admin_id' => auth()->id(),
        ])));

        $callbackUrl = request()->getSchemeAndHttpHost().'/backend/users/google/callback';

        return Socialite::driver('google')
            ->redirectUrl($callbackUrl)
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Handle Google callback for creating user.
     */
    public function googleCallback(Request $request): RedirectResponse
    {
        $this->authorize('manage users');

        $tenant = auth()->user()->tenant;
        $tenantId = auth()->user()->tenant_id;

        if (! $tenant || ! $tenant->canAddUser()) {
            return redirect()->route('backend.users.index')
                ->with('error', 'User limit for your plan has been reached. Upgrade your plan to add more users.');
        }

        $action = 'create_user';
        $stateTenantId = $tenantId;
        $adminId = auth()->id();
        $stateParam = $request->query('state');

        if ($stateParam) {
            $stateParam = str_replace(['-', '_'], ['+', '/'], $stateParam);
            $stateParam .= str_repeat('=', (4 - strlen($stateParam) % 4) % 4);
            $decoded = @json_decode(base64_decode($stateParam), true);
            if (is_array($decoded)) {
                $action = $decoded['action'] ?? 'create_user';
                $stateTenantId = $decoded['tenant_id'] ?? $tenantId;
                $adminId = $decoded['admin_id'] ?? auth()->id();
            }
        }

        // Verify tenant matches
        if ((int) $stateTenantId !== (int) $tenantId) {
            return redirect()->route('backend.users.create')
                ->with('error', 'Invalid request. Please try again.');
        }

        if ($request->has('error')) {
            return redirect()->route('backend.users.create')
                ->with('error', 'Google sign-in was cancelled. Please try again.');
        }

        if (! $request->filled('code')) {
            return redirect()->route('backend.users.create')
                ->with('error', 'Google did not return an authorization code. Please try again.');
        }

        $callbackUrl = $request->getSchemeAndHttpHost().'/backend/users/google/callback';

        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl($callbackUrl)
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            return redirect()->route('backend.users.create')
                ->with('error', 'Google login failed: '.$e->getMessage());
        }

        $email = $googleUser->getEmail();
        $name = $googleUser->getName() ?: $email;
        $googleId = $googleUser->getId();
        $emailNormalized = $email !== null ? strtolower($email) : '';

        if (empty($emailNormalized) || empty($email)) {
            return redirect()->route('backend.users.create')
                ->with('error', 'Google did not provide an email. Please use manual creation instead.');
        }

        // Check if user already exists in this tenant
        $existing = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($googleId, $emailNormalized) {
                $q->where('google_id', $googleId)
                    ->orWhereRaw('LOWER(email) = ?', [$emailNormalized]);
            })
            ->first();

        if ($existing) {
            // Link Google if not already linked
            if (! $existing->google_id) {
                $existing->update(['google_id' => $googleId]);
            }

            return redirect()->route('backend.users.index')
                ->with('info', 'This Google account is already registered in your barangay.');
        }

        // Check if email is registered as Super Admin
        $isSuperAdmin = User::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->whereRaw('LOWER(email) = ?', [$emailNormalized])
            ->exists();

        if ($isSuperAdmin) {
            return redirect()->route('backend.users.create')
                ->with('error', 'This Google account is registered as a Super Admin. Super Admin accounts cannot be added to barangays.');
        }

        // Return to create form with Google data pre-filled, admin needs to select role
        return redirect()->route('backend.users.create')
            ->with('google_user_data', [
                'name' => $name,
                'email' => $emailNormalized,
                'google_id' => $googleId,
            ])
            ->with('info', 'Google account selected. Please choose a role and click "Create user with Google account".');
    }

    /**
     * Store a user created with Google account.
     */
    public function storeWithGoogle(Request $request): RedirectResponse
    {
        $this->authorize('manage users');

        $tenant = auth()->user()->tenant;
        $tenantId = auth()->user()->tenant_id;

        if (! $tenant || ! $tenant->canAddUser()) {
            return redirect()->route('backend.users.index')
                ->with('error', 'User limit for your plan has been reached. Upgrade your plan to add more users.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where('tenant_id', $tenantId),
            ],
            'google_id' => ['required', 'string'],
            'role' => ['required', Rule::in($this->allowedRoleKeysForStore())],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['password'] = null; // Google users don't need password

        $user = User::create($validated);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('backend.users.index')
            ->with('success', 'User created with Google account. They can now log in with Google.');
    }

    /**
     * Delete a user in the current tenant (Barangay Admin only).
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('manage users');

        $tenantId = auth()->user()->tenant_id;
        if ((int) $user->tenant_id !== (int) $tenantId) {
            return redirect()->route('backend.users.index')
                ->with('error', 'You can only delete users from your own barangay.');
        }

        if ((int) $user->id === (int) auth()->id()) {
            return redirect()->route('backend.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        $user->syncRoles([]);
        $user->delete();

        return redirect()->route('backend.users.index')
            ->with('success', "User \"{$userName}\" deleted successfully.");
    }
}
