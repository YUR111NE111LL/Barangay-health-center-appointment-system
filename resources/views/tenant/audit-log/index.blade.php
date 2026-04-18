@extends('tenant.layouts.app')

@section('title', 'Audit log')

@section('content')
<h1 class="mb-2 text-2xl font-bold text-slate-800">Audit log</h1>
<p class="mb-6 text-slate-500">Sign-ins, sign-outs, and changes to appointments, services, announcements, events, and user accounts. Only <strong>Health Center Admin</strong> can open this page. Password values are never stored in plain text.</p>

@if(!empty($auditLogTableMissing))
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-medium">Audit log table is not set up for this barangay yet.</p>
        <p class="mt-1 text-amber-800">Ask your developer or Super Admin to run tenant migrations (for example: <code class="rounded bg-amber-100 px-1 py-0.5 text-xs">php artisan tenants:migrate</code>) or use <strong>Provision tenant database</strong> from the Super Admin tenant screen if the database was created before audit logging was added.</p>
    </div>
    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/90 px-6 py-14 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-white shadow ring-1 ring-slate-200/80">
            <svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-sm font-medium text-slate-800">Audit log will match other barangays after setup</p>
        <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-slate-600">Once the <code class="rounded bg-white px-1 py-0.5 text-xs ring-1 ring-slate-200">audit_logs</code> table is created in this tenant database, sign-ins and changes will appear in the same table layout as tenants that are already migrated.</p>
    </div>
@else
<div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
            <tr>
                <th class="whitespace-nowrap px-4 py-3">When</th>
                <th class="whitespace-nowrap px-4 py-3">Event</th>
                <th class="whitespace-nowrap px-4 py-3">Who</th>
                <th class="whitespace-nowrap px-4 py-3">What</th>
                <th class="min-w-[200px] px-4 py-3">Details</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($logs as $log)
                <tr class="align-top">
                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ \App\Support\DateDisplay::format($log->created_at, 'Y-m-d H:i') }}</td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                            @if($log->event === 'created') bg-emerald-100 text-emerald-800
                            @elseif($log->event === 'updated') bg-sky-100 text-sky-800
                            @elseif($log->event === 'deleted') bg-rose-100 text-rose-800
                            @elseif($log->event === 'login') bg-violet-100 text-violet-800
                            @elseif($log->event === 'logout') bg-slate-200 text-slate-800
                            @else bg-amber-100 text-amber-900
                            @endif">{{ $log->event }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        @if($log->user)
                            <span class="font-medium">{{ $log->user->name }}</span>
                            <span class="block text-xs text-slate-500">{{ $log->user_role ?? $log->user->role }}</span>
                        @else
                            <span class="text-slate-500">—</span>
                        @endif
                        @if($log->ip_address)
                            <span class="mt-1 block text-xs text-slate-400" title="IP">{{ $log->ip_address }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-800">
                        @if($log->event === 'login')
                            <span class="text-slate-800">Account sign-in</span>
                        @elseif($log->event === 'logout')
                            <span class="text-slate-800">Account sign-out</span>
                        @else
                            <span class="font-medium text-slate-800">{{ \App\Support\TenantAuditLogDisplay::auditableLabel($log->auditable_type) }}</span>
                            @if(($auditContext = \App\Support\TenantAuditLogDisplay::auditableContextLine($log)) !== null)
                                <span class="mt-0.5 block text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($auditContext, 100) }}</span>
                            @endif
                            @if($log->auditable_id !== null)
                                <span class="font-mono text-xs text-slate-500">#{{ $log->auditable_id }}</span>
                            @endif
                        @endif
                    </td>
                    <td class="max-w-xl px-4 py-3 text-sm text-slate-600">
                        @if($log->event === 'login')
                            @if(is_array($log->new_values) && array_key_exists('remember', $log->new_values))
                                <p class="leading-relaxed text-slate-700">Stay signed in on this device: <strong>{{ $log->new_values['remember'] ? __('Yes') : __('No') }}</strong>.</p>
                            @else
                                <span class="text-slate-500">—</span>
                            @endif
                        @elseif($log->event === 'logout')
                            <p class="leading-relaxed text-slate-700">Session ended for this account.</p>
                        @elseif($log->old_values || $log->new_values)
                            <details class="group text-xs">
                                <summary class="cursor-pointer list-none text-teal-700 hover:underline">View changes</summary>
                                <div class="mt-2 max-h-64 overflow-auto rounded-lg bg-slate-50 p-2 ring-1 ring-slate-200">
                                    @if($log->old_values)
                                        <p class="mb-1 font-medium text-slate-700">Before</p>
                                        <pre class="whitespace-pre-wrap break-words text-[11px] leading-relaxed">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @endif
                                    @if($log->new_values)
                                        <p class="mb-1 mt-2 font-medium text-slate-700">After</p>
                                        <pre class="whitespace-pre-wrap break-words text-[11px] leading-relaxed">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @endif
                                </div>
                            </details>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">No audit entries yet. After the audit table exists for this barangay, sign-ins and data changes will appear here.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6 flex justify-center">
    {{ $logs->withQueryString()->links() }}
</div>
@endif
@endsection
