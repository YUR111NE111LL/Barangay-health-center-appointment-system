@extends('superadmin.layouts.app')

@section('title', 'Super Admin Audit Log')

@section('content')
<h1 class="mb-2 text-2xl font-bold text-slate-800">Super Admin audit log</h1>
<p class="mb-6 text-slate-500">Tracks Super Admin account sign-ins, sign-outs, and tenant creation actions. Password values are never stored in plain text.</p>

@if(!empty($auditLogTableMissing))
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-medium">Super Admin audit log table is not set up yet.</p>
        <p class="mt-1 text-amber-800">Run central migrations to create <code class="rounded bg-amber-100 px-1 py-0.5 text-xs">super_admin_audit_logs</code> (for example: <code class="rounded bg-amber-100 px-1 py-0.5 text-xs">php artisan migrate</code>).</p>
    </div>
@endif

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
                            @elseif($log->event === 'viewed') bg-blue-100 text-blue-800
                            @elseif($log->event === 'action') bg-indigo-100 text-indigo-800
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
                            <span class="mt-1 block text-xs text-slate-400">{{ $log->ip_address }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-800">
                        @if($log->event === 'login')
                            <span class="text-slate-800">Super Admin sign-in</span>
                        @elseif($log->event === 'logout')
                            <span class="text-slate-800">Super Admin sign-out</span>
                        @elseif($log->auditable_type === \App\Models\Tenant::class)
                            <span class="font-medium text-slate-800">Tenant</span>
                            @if($log->auditable_id !== null)
                                <span class="font-mono text-xs text-slate-500">#{{ $log->auditable_id }}</span>
                            @endif
                        @elseif($log->auditable_type === 'route')
                            <span class="font-medium text-slate-800">Super Admin route</span>
                            @if(is_array($log->new_values) && filled($log->new_values['route_name'] ?? null))
                                <span class="mt-0.5 block text-xs text-slate-600">{{ $log->new_values['route_name'] }}</span>
                            @endif
                        @else
                            <span class="font-medium text-slate-800">{{ class_basename((string) $log->auditable_type) }}</span>
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
                                <summary class="cursor-pointer list-none text-violet-700 hover:underline">View changes</summary>
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
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">No Super Admin audit entries yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6 flex justify-center">
    {{ $logs->withQueryString()->links() }}
</div>
@endsection
