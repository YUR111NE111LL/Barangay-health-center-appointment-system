<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    /**
     * List all Super Admin accounts.
     */
    public function index(Request $request): View
    {
        $query = User::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->paginate(15);

        return view('superadmin.users.index', compact('users'));
    }

    /**
     * Show form to create a new Super Admin user.
     */
    public function create(): View
    {
        return view('superadmin.users.create');
    }

    /**
     * Store a new Super Admin user (manual creation).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('tenant_id'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validated['tenant_id'] = null;
        $validated['role'] = User::ROLE_SUPER_ADMIN;
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->syncRoles([User::ROLE_SUPER_ADMIN]);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'Super Admin user created. They can now log in with this email and password.');
    }

    /**
     * Redirect to Google to create a Super Admin user via Google account.
     */
    public function createWithGoogle(): RedirectResponse
    {
        $state = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode([
            'action' => 'create_super_admin',
            'admin_id' => auth()->id(),
        ])));

        $callbackUrl = request()->getSchemeAndHttpHost() . '/super-admin/users/google/callback';

        return Socialite::driver('google')
            ->redirectUrl($callbackUrl)
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Handle Google callback for creating Super Admin user.
     */
    public function googleCallback(Request $request): RedirectResponse
    {
        $action = 'create_super_admin';
        $adminId = auth()->id();
        $stateParam = $request->query('state');
        
        if ($stateParam) {
            $stateParam = str_replace(['-', '_'], ['+', '/'], $stateParam);
            $stateParam .= str_repeat('=', (4 - strlen($stateParam) % 4) % 4);
            $decoded = @json_decode(base64_decode($stateParam), true);
            if (is_array($decoded)) {
                $action = $decoded['action'] ?? 'create_super_admin';
                $adminId = $decoded['admin_id'] ?? auth()->id();
            }
        }

        if ($request->has('error')) {
            return redirect()->route('super-admin.users.create')
                ->with('error', 'Google sign-in was cancelled. Please try again.');
        }

        if (! $request->filled('code')) {
            return redirect()->route('super-admin.users.create')
                ->with('error', 'Google did not return an authorization code. Please try again.');
        }

        $callbackUrl = $request->getSchemeAndHttpHost() . '/super-admin/users/google/callback';

        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl($callbackUrl)
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            return redirect()->route('super-admin.users.create')
                ->with('error', 'Google login failed: ' . $e->getMessage());
        }

        $email = $googleUser->getEmail();
        $name = $googleUser->getName() ?: $email;
        $googleId = $googleUser->getId();
        $emailNormalized = $email !== null ? strtolower($email) : '';

        if (empty($emailNormalized) || empty($email)) {
            return redirect()->route('super-admin.users.create')
                ->with('error', 'Google did not provide an email. Please use manual creation instead.');
        }

        // Check if email is already registered under a tenant
        $alreadyUnderTenant = User::withoutGlobalScopes()
            ->whereNotNull('tenant_id')
            ->whereRaw('LOWER(email) = ?', [$emailNormalized])
            ->exists();
        
        if ($alreadyUnderTenant) {
            return redirect()->route('super-admin.users.create')
                ->with('error', 'This Google account is already registered under a barangay. Super Admin accounts cannot be associated with barangays.');
        }

        // Check if Super Admin already exists
        $existing = User::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('role', User::ROLE_SUPER_ADMIN)
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
            return redirect()->route('super-admin.users.index')
                ->with('info', 'This Google account is already a Super Admin.');
        }

        // Create new Super Admin with Google account
        $user = User::create([
            'tenant_id' => null,
            'role' => User::ROLE_SUPER_ADMIN,
            'name' => $name,
            'email' => $emailNormalized ?: $email,
            'password' => null,
            'google_id' => $googleId,
        ]);
        $user->syncRoles([User::ROLE_SUPER_ADMIN]);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'Super Admin user created with Google account. They can now log in with Google.');
    }
}
