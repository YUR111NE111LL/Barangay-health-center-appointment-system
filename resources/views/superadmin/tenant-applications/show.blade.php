@extends('superadmin.layouts.app')

@section('title', 'Tenant request')

@section('content')
<div class="mb-6">
    <a href="{{ route('super-admin.tenant-applications.index') }}" class="text-sm font-medium text-violet-600 hover:text-violet-800">← Back to tenant requests</a>
    <h1 class="mt-4 text-2xl font-bold text-slate-800">Tenant request</h1>
    <p class="mt-1 text-sm text-slate-600">Submitted {{ \App\Support\DateDisplay::format($tenantApplication->created_at) }}</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
        <h2 class="text-lg font-semibold text-slate-800">Details</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
                <dd class="mt-1">
                    @if($tenantApplication->status === \App\Models\TenantApplication::STATUS_PENDING)
                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900">Pending Super Admin approval</span>
                    @elseif($tenantApplication->status === \App\Models\TenantApplication::STATUS_APPROVED)
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Approved</span>
                    @else
                        <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-800">Rejected</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Plan</dt>
                <dd class="mt-1 text-sm text-slate-900">
                    {{ $tenantApplication->plan?->name ?? '—' }}
                    @if($tenantApplication->plan && $tenantApplication->plan->price !== null)
                        <span class="block text-xs font-normal text-slate-600">{{ $tenantApplication->plan->formattedPrice() }} {{ __('/ month') }}</span>
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Barangay / tenant name</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $tenantApplication->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Barangay name</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $tenantApplication->barangay }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Domain</dt>
                <dd class="mt-1 text-sm font-mono text-slate-900">{{ $tenantApplication->domain ?? '—' }}</dd>
                @if($tenantApplication->status === \App\Models\TenantApplication::STATUS_PENDING && ! $tenantApplication->domain)
                    <p class="mt-1 text-xs text-slate-500">{{ __('Applicants no longer set the domain. Enter it when you approve.') }}</p>
                @endif
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Address</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $tenantApplication->address ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Contact</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $tenantApplication->contact_number ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $tenantApplication->email ?: '—' }}</dd>
            </div>
        </dl>

        @if($tenantApplication->status === \App\Models\TenantApplication::STATUS_REJECTED && $tenantApplication->rejection_reason)
            <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-900 ring-1 ring-rose-200">
                <div class="font-semibold">Rejection note</div>
                <p class="mt-1 whitespace-pre-wrap">{{ $tenantApplication->rejection_reason }}</p>
            </div>
        @endif

        @if($tenantApplication->reviewed_at)
            <div class="border-t border-slate-200 pt-4 text-sm text-slate-600">
                Reviewed {{ \App\Support\DateDisplay::format($tenantApplication->reviewed_at) }}
                @if($tenantApplication->reviewer)
                    by {{ $tenantApplication->reviewer->name }}
                @endif
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @if($tenantApplication->status === \App\Models\TenantApplication::STATUS_APPROVED && $tenantApplication->tenant)
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
                <h3 class="text-sm font-semibold text-slate-800">Created tenant</h3>
                <p class="mt-2 text-sm text-slate-600">The barangay site was created when you approved this request.</p>
                <a href="{{ route('super-admin.tenants.show', $tenantApplication->tenant) }}" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-violet-700">Open tenant</a>
            </div>
        @endif

        @if($tenantApplication->status === \App\Models\TenantApplication::STATUS_PENDING)
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
                <h3 class="text-sm font-semibold text-slate-800">Approve</h3>
                <p class="mt-1 text-xs text-slate-600">{{ __('Set the tenant domain (only Super Admin). Suggested from barangay name:') }} <span class="font-mono text-slate-800">{{ $suggestedDomain !== '' ? $suggestedDomain : '—' }}</span></p>
                <form action="{{ route('super-admin.tenant-applications.approve', $tenantApplication) }}" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="approve_domain" class="mb-1 block text-xs font-medium text-slate-700">{{ __('Domain') }} <span class="text-rose-500">*</span></label>
                        <input type="text" name="domain" id="approve_domain" value="{{ old('domain', $tenantApplication->domain ?? $suggestedDomain) }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-3 py-2 text-sm font-mono focus:border-violet-500 focus:ring-violet-500" required autocomplete="off">
                        @error('domain')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">{{ __('Approve & create tenant') }}</button>
                </form>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
                <h3 class="text-sm font-semibold text-slate-800">Reject</h3>
                <form action="{{ route('super-admin.tenant-applications.reject', $tenantApplication) }}" method="POST" class="mt-3 space-y-3">
                    @csrf
                    <div>
                        <label for="rejection_reason" class="mb-1 block text-xs font-medium text-slate-600">Note (optional)</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" class="w-full rounded-xl border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-violet-500 focus:ring-violet-500" placeholder="{{ __('Shown to the applicant in the rejection email if provided') }}">{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-800 hover:bg-rose-100">Reject application</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
