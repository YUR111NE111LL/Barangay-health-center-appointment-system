@extends('superadmin.layouts.app')

@section('title', $tenant->name)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">{{ $tenant->name }}</h1>
    <div class="flex flex-wrap items-center gap-2">
        @php
            $primaryDomain = $tenant->domains->first()?->domain;
            $tenantLoginUrl = $primaryDomain
                ? (request()->getScheme() . '://' . $primaryDomain . (in_array(request()->getPort(), [80, 443, null]) ? '' : ':' . request()->getPort()) . '/login')
                : null;
        @endphp
        @if($tenantLoginUrl)
            <a href="{{ $tenantLoginUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 font-medium text-emerald-700 hover:bg-emerald-100">Login at {{ $primaryDomain }} <span aria-hidden="true">↗</span></a>
        @endif
        <form action="{{ route('super-admin.tenants.toggle-status', $tenant) }}" method="POST" class="inline" id="toggle-status-form">
            @csrf
            @method('PATCH')
            @if($tenant->is_active)
                <button type="submit" class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 font-medium text-rose-700 hover:bg-rose-100" id="toggle-status-btn">Deactivate Tenant</button>
            @else
                <button type="submit" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 font-medium text-emerald-700 hover:bg-emerald-100" id="toggle-status-btn">Activate Tenant</button>
            @endif
        </form>
        <a href="{{ route('super-admin.tenants.rbac.index', $tenant) }}" class="rounded-xl border border-violet-300 bg-violet-50 px-4 py-2 font-medium text-violet-700 hover:bg-violet-100">RBAC for this tenant</a>
        <a href="{{ route('super-admin.tenants.edit', $tenant) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 font-medium text-slate-700 hover:bg-slate-50">Edit</a>
    </div>
</div>

<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
    <dl class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-slate-500">Domain(s)</dt>
            <dd class="mt-1">
                @forelse($tenant->domains as $d)
                    @php
                        $domainLoginUrl = request()->getScheme() . '://' . $d->domain . (in_array(request()->getPort(), [80, 443, null]) ? '' : ':' . request()->getPort()) . '/login';
                    @endphp
                    <a href="{{ $domainLoginUrl }}" target="_blank" rel="noopener noreferrer" class="mr-2 inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-sm text-violet-700 hover:bg-violet-100 hover:text-violet-800">{{ $d->domain }} <span aria-hidden="true">↗</span></a>
                @empty
                    <span class="text-slate-500">—</span>
                @endforelse
            </dd>
        </div>
        <div><dt class="text-sm font-medium text-slate-500">Plan</dt><dd class="mt-1 text-slate-800">{{ $tenant->plan->name }}</dd></div>
        <div><dt class="text-sm font-medium text-slate-500">Active</dt><dd class="mt-1">{{ $tenant->is_active ? 'Yes' : 'No' }}</dd></div>
        @if($tenant->address)<div class="sm:col-span-2"><dt class="text-sm font-medium text-slate-500">Address</dt><dd class="mt-1 text-slate-800">{{ $tenant->address }}</dd></div>@endif
        @if($tenant->contact_number)<div><dt class="text-sm font-medium text-slate-500">Contact</dt><dd class="mt-1 text-slate-800">{{ $tenant->contact_number }}</dd></div>@endif
        @if($tenant->email)<div><dt class="text-sm font-medium text-slate-500">Email</dt><dd class="mt-1 text-slate-800">{{ $tenant->email }}</dd></div>@endif
        <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-slate-500">Subscription Status</dt>
            <dd class="mt-1">
                @php
                    $status = $tenant->getSubscriptionStatus();
                    $daysUntil = $tenant->daysUntilExpiry();
                    $daysInGrace = $tenant->daysRemainingInGracePeriod();
                @endphp
                @if($tenant->subscription_ends_at)
                    @if($status === 'active')
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                            Active
                            @if($daysUntil !== null)
                                ({{ $daysUntil }} day(s) remaining)
                            @endif
                        </span>
                    @elseif($status === 'expiring_soon')
                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                            ⚠️ Expiring Soon ({{ $daysUntil }} day(s))
                        </span>
                    @elseif($status === 'expired')
                        <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800">
                            Expired
                        </span>
                    @elseif($status === 'grace_period')
                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                            🚨 Grace Period ({{ $daysInGrace }} day(s) left)
                        </span>
                    @elseif($status === 'deactivated')
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                            ❌ Deactivated
                        </span>
                    @endif
                    <div class="mt-2 text-sm text-slate-600">
                        <strong>Expiry Date:</strong> {{ $tenant->subscription_ends_at->format('l, F j, Y') }}
                        @if($tenant->grace_period_ends_at)
                            <br><strong>Grace Period Ends:</strong> {{ $tenant->grace_period_ends_at->format('l, F j, Y') }}
                        @endif
                    </div>
                @else
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">No expiry set</span>
                @endif
            </dd>
        </div>
        <div><dt class="text-sm font-medium text-slate-500">Users</dt><dd class="mt-1 text-slate-800">{{ $tenant->users_count }}</dd></div>
        <div><dt class="text-sm font-medium text-slate-500">Appointments</dt><dd class="mt-1 text-slate-800">{{ $tenant->appointments_count }}</dd></div>
    </dl>
</div>

@if($tenant->users->isNotEmpty())
<div class="mt-8">
    <h2 class="mb-4 text-lg font-semibold text-slate-800">Users in this tenant</h2>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Profile</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($tenant->users as $u)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-medium text-teal-700" aria-hidden="true">{{ $u->initials }}</span>
                                <span class="text-sm font-medium text-slate-800">{{ $u->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $u->email }}</td>
                        <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $u->role }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<a href="{{ route('super-admin.tenants.index') }}" class="mt-4 inline-block text-sm font-medium text-violet-600 hover:text-violet-700">← Back to list</a>

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
    
    const form = document.getElementById('toggle-status-form');
    const isActive = {{ $tenant->is_active ? 'true' : 'false' }};
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
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!isActive) {
            // Activating
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
        } else {
            // Deactivating
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
                    form.submit();
                }
            });
        }
    });
});
</script>
@endpush
@endsection
