@extends('superadmin.layouts.app')

@section('title', 'Add Tenant')

@section('content')
<h1 class="mb-6 text-2xl font-bold text-slate-800">Add Tenant</h1>

<form action="{{ route('super-admin.tenants.store') }}" method="POST" class="max-w-2xl space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 sm:p-8">
    @csrf
    <input type="hidden" id="tenant-domain-root" value="{{ config('bhcas.tenant_domain_root', 'localhost') }}">
    @if($errors->any())
        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div>
        <label for="plan_id" class="mb-1 block text-sm font-medium text-slate-700">Plan <span class="text-rose-500">*</span></label>
        <select name="plan_id" id="plan_id" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500" required>
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
            @endforeach
        </select>
        @error('plan_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name <span class="text-rose-500">*</span></label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500" required>
        @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="barangay" class="mb-1 block text-sm font-medium text-slate-700">Barangay (for domain) <span class="text-rose-500">*</span></label>
        <input type="text"
               name="barangay"
               id="barangay"
               value="{{ old('barangay') }}"
               placeholder="brgy-sumpong"
               class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500"
               required>
        <p class="mt-1 text-xs text-slate-500">Used to auto-generate tenant domain: <strong>barangay.{{ config('bhcas.tenant_domain_root', 'localhost') }}</strong></p>
        @error('barangay')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="domain" class="mb-1 block text-sm font-medium text-slate-700">Domain (auto-generated)</label>
        <input type="text" name="domain" id="domain" value="{{ old('domain') }}" placeholder="auto-generated from barangay" class="w-full rounded-xl border-slate-300 bg-slate-100 px-4 py-2.5 text-slate-600 focus:border-violet-500 focus:ring-violet-500" readonly>
        <p class="mt-1 text-xs text-slate-500">Auto-generated from barangay. You can leave this as-is.</p>
        @error('domain')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
        <input type="text" name="address" id="address" value="{{ old('address') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500">
        @error('address')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="contact_number" class="mb-1 block text-sm font-medium text-slate-700">Contact number</label>
            <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500">
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="subscription_ends_at" class="mb-1 block text-sm font-medium text-slate-700">Subscription ends at</label>
        <input type="date" name="subscription_ends_at" id="subscription_ends_at" value="{{ old('subscription_ends_at') }}" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500">
        <p class="mt-1 text-xs text-slate-500">Leave empty for no expiry. System will notify before expiry and allow 3-day grace period.</p>
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
        <label for="is_active" class="text-sm text-slate-700">Active</label>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2.5 font-medium text-white hover:bg-violet-700">Create Tenant</button>
        <a href="{{ route('super-admin.tenants.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
    </div>
</form>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var barangayEl = document.getElementById('barangay');
    var domainEl = document.getElementById('domain');
    if (!barangayEl || !domainEl) return;

    var manuallyEditedDomain = false;
    domainEl.addEventListener('input', function() {
        manuallyEditedDomain = true;
    });

    var tenantDomainRootEl = document.getElementById('tenant-domain-root');
    var tenantDomainRoot = tenantDomainRootEl ? tenantDomainRootEl.value : 'localhost';

    function slugify(input) {
        return String(input || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    barangayEl.addEventListener('input', function() {
        if (manuallyEditedDomain) return;
        var val = String(barangayEl.value || '').trim();
        if (!val) return;

        // If user types a full domain or URL, normalize as-is.
        var lowered = val.toLowerCase();
        var looksLikeDomain = lowered.includes('.') || lowered.includes('://');
        if (looksLikeDomain) {
            var normalized = lowered
                .replace(/^https?:\/\//i, '')
                .split('/')[0]
                .split('?')[0]
                .split('#')[0];
            domainEl.value = normalized;
            return;
        }

        domainEl.value = slugify(val) + '.' + tenantDomainRoot;
    });
});
</script>
@endpush
@endsection
