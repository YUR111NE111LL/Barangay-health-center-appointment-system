@extends('superadmin.layouts.app')

@section('title', 'Audit log')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Audit log</h1>
    <p class="mt-1 text-slate-500">Open a barangay’s tenant audit log (sign-ins and data changes stored in that tenant’s database).</p>
</div>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Barangay</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tenant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Domain</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Audit log</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse($tenants as $t)
                <tr class="hover:bg-slate-50/50">
                    @php
                        $primaryDomain = $t->domains->first()?->domain;
                        $barangayDisplay = '—';
                        if ($primaryDomain) {
                            $firstLabel = explode('.', (string) $primaryDomain)[0] ?: '';
                            $barangayDisplay = ucwords(str_replace('-', ' ', $firstLabel));
                        }
                    @endphp
                    <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $barangayDisplay }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800">{{ $t->name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $primaryDomain ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('super-admin.tenants.audit-log.index', $t) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Open</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-10 text-center text-slate-500">No tenants yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($tenants->hasPages())
<div class="mt-6 flex justify-center">
    {{ $tenants->withQueryString()->links() }}
</div>
@endif
@endsection
