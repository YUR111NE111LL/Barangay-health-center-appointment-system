@extends('superadmin.layouts.app')

@section('title', 'Tenants')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-800">Tenants</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('super-admin.plans.index') }}" class="inline-flex items-center rounded-xl border border-violet-300 bg-violet-50 px-4 py-2.5 font-medium text-violet-700 shadow-sm hover:bg-violet-100">Update Plan Prices</a>
        <a href="{{ route('super-admin.tenants.create') }}" class="inline-flex items-center rounded-xl bg-violet-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-violet-700">Add Tenant</a>
    </div>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Barangay</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tenant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Domain</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Plan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Subscription Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Expiry Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Active</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($tenants as $t)
                <tr class="hover:bg-slate-50/50">
                    @php
                        $primaryDomain = $t->domains->first()?->domain;
                        // Derive barangay name from the domain (e.g. brgy-bangcud.localhost => Brgy Bangcud).
                        $barangayDisplay = '—';
                        if ($primaryDomain) {
                            $firstLabel = explode('.', (string) $primaryDomain)[0] ?: '';
                            $barangayDisplay = ucwords(str_replace('-', ' ', $firstLabel));
                        }
                    @endphp
                    <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $barangayDisplay }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $t->name }}</td>
                    <td class="px-4 py-3">
                        @php
                            $primaryDomain = $t->domains->first()?->domain;
                            $tenantLoginUrl = $primaryDomain
                                ? (request()->getScheme() . '://' . $primaryDomain . (in_array(request()->getPort(), [80, 443, null]) ? '' : ':' . request()->getPort()) . '/login')
                                : null;
                        @endphp
                        @if($tenantLoginUrl)
                            <a href="{{ $tenantLoginUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-sm text-violet-700 hover:bg-violet-100 hover:text-violet-800" title="Open tenant login">{{ $primaryDomain }} <span aria-hidden="true">↗</span></a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">{{ $t->plan->name }}</td>
                    <td class="px-4 py-3">
                        @php
                            $status = $t->getSubscriptionStatus();
                            $daysUntil = $t->daysUntilExpiry();
                            $daysInGrace = $t->daysRemainingInGracePeriod();
                        @endphp
                        @if($status === 'active')
                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                Active
                                @if($t->subscription_ends_at && $daysUntil !== null)
                                    ({{ $daysUntil }}d left)
                                @endif
                            </span>
                        @elseif($status === 'expiring_soon')
                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                ⚠️ Expiring Soon ({{ $daysUntil }}d)
                            </span>
                        @elseif($status === 'expired')
                            <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800">
                                Expired
                            </span>
                        @elseif($status === 'grace_period')
                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                🚨 Grace Period ({{ $daysInGrace }}d left)
                            </span>
                        @elseif($status === 'deactivated')
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                ❌ Deactivated
                            </span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                {{ ucfirst($status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                        @if($t->subscription_ends_at)
                            <div>{{ $t->subscription_ends_at->format('M j, Y') }}</div>
                            @if($t->grace_period_ends_at)
                                <div class="text-xs text-slate-500">Grace: {{ $t->grace_period_ends_at->format('M j, Y') }}</div>
                            @endif
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($t->is_active)
                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Yes</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">No</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <a href="{{ route('super-admin.tenants.show', $t) }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">View</a>
                        <a href="{{ route('super-admin.tenants.rbac.index', $t) }}" class="ml-3 text-sm font-medium text-violet-600 hover:text-violet-700">RBAC</a>
                        <a href="{{ route('super-admin.tenants.edit', $t) }}" class="ml-3 text-sm font-medium text-slate-600 hover:text-slate-700">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No tenants yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4 flex justify-center">
    {{ $tenants->links() }}
</div>
@endsection
