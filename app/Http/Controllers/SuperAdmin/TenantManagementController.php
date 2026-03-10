<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantManagementController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::with('plan')->orderBy('name')->paginate(15);

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
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug'],
            'address' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'is_active' => ['boolean'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        Tenant::create($validated);

        return redirect()->route('super-admin.tenants.index')->with('success', 'Tenant created.');
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load('plan');
        $tenant->loadCount(['users', 'appointments']);
        $tenant->load(['users' => fn ($q) => $q->orderBy('role')->orderBy('name')]);

        return view('superadmin.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant): View
    {
        $tenant->load('plan');
        $plans = Plan::orderBy('name')->get();

        return view('superadmin.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug,' . $tenant->id],
            'address' => ['nullable', 'string'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'is_active' => ['boolean'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $tenant->update($validated);

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
