@extends('superadmin.layouts.app')

@section('title', 'Edit ' . $tenant->name)

@section('content')
<h1 class="mb-6 text-2xl font-bold text-slate-800">Edit Tenant</h1>

<form action="{{ route('super-admin.tenants.update', $tenant) }}" method="POST" class="max-w-2xl space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 sm:p-8">
    @csrf
    @method('PUT')
    <div>
        <label for="plan_id" class="mb-1 block text-sm font-medium text-slate-700">Plan <span class="text-rose-500">*</span></label>
        <select name="plan_id" id="plan_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500" required>
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}" {{ old('plan_id', $tenant->plan_id) == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
            @endforeach
        </select>
        @error('plan_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-rose-500">*</span></label>
        <input type="text" name="name" id="name" value="{{ old('name', $tenant->name) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500" required>
        @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="domain" class="mb-1 block text-sm font-medium text-slate-700">Domain <span class="text-rose-500">*</span></label>
        <input type="text" name="domain" id="domain" value="{{ old('domain', $tenant->domains->first()?->domain) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500" required>
        @error('domain')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
        <input type="text" name="address" id="address" value="{{ old('address', $tenant->address) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500">
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="contact_number" class="mb-1 block text-sm font-medium text-slate-700">Contact number</label>
            <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number', $tenant->contact_number) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $tenant->email) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500">
        </div>
    </div>
    <div>
        <label for="subscription_ends_at" class="mb-1 block text-sm font-medium text-slate-700">Subscription ends at</label>
        <input type="date" name="subscription_ends_at" id="subscription_ends_at" value="{{ old('subscription_ends_at', $tenant->subscription_ends_at?->format('Y-m-d')) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500">
        <p class="mt-1 text-xs text-slate-500">Leave empty for no expiry. System will notify before expiry and allow 3-day grace period.</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $tenant->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
            <label for="is_active" class="text-sm font-medium text-slate-700">Active</label>
        </div>
        @if($tenant->is_active)
            <p class="mt-2 text-xs text-slate-600">
                <strong>Note:</strong> Unchecking this will deactivate the tenant and block access for all {{ $tenant->users_count ?? 0 }} user(s). You will be asked to confirm this action.
            </p>
        @else
            <p class="mt-2 text-xs text-slate-600">
                <strong>Note:</strong> Checking this will activate the tenant and restore access for all {{ $tenant->users_count ?? 0 }} user(s).
            </p>
        @endif
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2.5 font-medium text-white hover:bg-violet-700">Update</button>
        <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>

<!-- Professional Confirmation Modal -->
<div id="tenant-status-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="relative mx-4 w-full max-w-lg rounded-2xl bg-white shadow-xl ring-1 ring-slate-200/60">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div id="modal-icon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"></div>
                <div class="flex-1">
                    <h3 id="modal-title" class="text-lg font-semibold text-slate-800"></h3>
                    <div id="modal-content" class="mt-3 text-sm text-slate-600"></div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="modal-cancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" id="modal-confirm" class="rounded-xl px-4 py-2.5 text-sm font-medium text-white"></button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('tenant-status-modal');
    const modalIcon = document.getElementById('modal-icon');
    const modalTitle = document.getElementById('modal-title');
    const modalContent = document.getElementById('modal-content');
    const modalCancel = document.getElementById('modal-cancel');
    const modalConfirm = document.getElementById('modal-confirm');
    
    const activeCheckbox = document.getElementById('is_active');
    const form = activeCheckbox.closest('form');
    const wasActive = {{ $tenant->is_active ? 'true' : 'false' }};
    const userCount = {{ $tenant->users_count ?? 0 }};
    const tenantName = '{{ $tenant->name }}';
    
    let pendingAction = null;
    
    function showModal(config) {
        modalIcon.innerHTML = config.icon;
        modalTitle.textContent = config.title;
        modalContent.innerHTML = config.content;
        modalConfirm.textContent = config.confirmText;
        modalConfirm.className = 'rounded-xl px-4 py-2.5 text-sm font-medium text-white ' + config.confirmClass;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        pendingAction = config.onConfirm;
    }
    
    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        pendingAction = null;
    }
    
    modalCancel.addEventListener('click', hideModal);
    modalConfirm.addEventListener('click', function() {
        if (pendingAction) {
            pendingAction();
        }
        hideModal();
    });
    
    // Close on backdrop click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideModal();
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            hideModal();
        }
    });
    
    activeCheckbox.addEventListener('change', function(e) {
        const isNowActive = e.target.checked;
        
        // If deactivating (was active, now unchecked)
        if (wasActive && !isNowActive) {
            e.preventDefault();
            e.target.checked = true; // Revert the change
            
            showModal({
                icon: '<svg class="h-6 w-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
                title: 'Deactivate Tenant',
                content: `
                    <p class="mb-3 font-medium text-slate-800">${tenantName}</p>
                    <p class="mb-4">By deactivating this tenant, you will immediately revoke access for all <strong>${userCount} user(s)</strong> associated with this tenant.</p>
                    <div class="mb-4 rounded-lg bg-rose-50 p-3">
                        <p class="mb-2 text-sm font-medium text-rose-800">This action will:</p>
                        <ul class="ml-4 list-disc space-y-1 text-sm text-rose-700">
                            <li>Block all users from accessing the system</li>
                            <li>Prevent new appointments from being created</li>
                            <li>Restrict access to all tenant features</li>
                        </ul>
                    </div>
                    <p class="text-sm text-slate-600">All data will be preserved and can be restored by reactivating the tenant.</p>
                `,
                confirmText: 'Deactivate Tenant',
                confirmClass: 'bg-rose-600 hover:bg-rose-700',
                onConfirm: function() {
                    activeCheckbox.checked = false;
                    form.submit();
                }
            });
        }
        // If activating (was inactive, now checked)
        else if (!wasActive && isNowActive) {
            showModal({
                icon: '<svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                title: 'Activate Tenant',
                content: `
                    <p class="mb-3 font-medium text-slate-800">${tenantName}</p>
                    <p class="mb-4">By activating this tenant, you will restore access for all <strong>${userCount} user(s)</strong> associated with this tenant.</p>
                    <div class="mb-4 rounded-lg bg-emerald-50 p-3">
                        <p class="mb-2 text-sm font-medium text-emerald-800">This action will:</p>
                        <ul class="ml-4 list-disc space-y-1 text-sm text-emerald-700">
                            <li>Restore access for all users</li>
                            <li>Enable appointment creation</li>
                            <li>Restore all tenant features</li>
                        </ul>
                    </div>
                `,
                confirmText: 'Activate Tenant',
                confirmClass: 'bg-emerald-600 hover:bg-emerald-700',
                onConfirm: function() {
                    form.submit();
                }
            });
        }
    });
});
</script>
@endpush
@endsection
