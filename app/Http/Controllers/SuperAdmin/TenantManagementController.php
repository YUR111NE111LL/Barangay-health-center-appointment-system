<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stancl\Tenancy\Database\Models\Domain;

class TenantManagementController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::with('plan', 'domains')->orderBy('name')->paginate(15);

        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        $plans = Plan::orderBy('name')->get();

        return view('superadmin.tenants.create', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'address' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'is_active' => ['boolean'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $tenant = Tenant::create([
            'plan_id' => $validated['plan_id'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $validated['is_active'],
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
        ]);

        $tenant->domains()->create([
            'domain' => Str::lower($validated['domain']),
        ]);

        return redirect()->route('super-admin.tenants.index')->with('success', 'Tenant created.');
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load('plan', 'domains');
        $tenant->loadCount(['users', 'appointments']);
        $tenant->load(['users' => fn ($q) => $q->orderBy('role')->orderBy('name')]);

        return view('superadmin.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant): View
    {
        $tenant->load('plan', 'domains');
        $plans = Plan::orderBy('name')->get();

        return view('superadmin.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', Rule::unique('domains', 'domain')->ignore($tenant->domains()->first())],
            'address' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'is_active' => ['boolean'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $tenant->update([
            'plan_id' => $validated['plan_id'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $validated['is_active'],
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
        ]);

        $primary = $tenant->domains()->first();
        if ($primary) {
            $primary->update(['domain' => Str::lower($validated['domain'])]);
        } else {
            $tenant->domains()->create(['domain' => Str::lower($validated['domain'])]);
        }

        $message = $validated['is_active']
            ? 'Tenant activated successfully. All users can now access the system.'
            : 'Tenant deactivated successfully. All users have been blocked from accessing the system.';

        return redirect()->route('super-admin.tenants.show', $tenant)->with('success', $message);
    }

    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => !$tenant->is_active]);

        $message = $tenant->is_active
            ? 'Tenant activated successfully. All users can now access the system.'
            : 'Tenant deactivated successfully. All users have been blocked from accessing the system.';

        return redirect()->route('super-admin.tenants.show', $tenant)->with('success', $message);
    }
}
