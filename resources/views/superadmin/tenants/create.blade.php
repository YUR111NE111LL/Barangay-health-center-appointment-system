@extends('superadmin.layouts.app')

@section('title', 'Add Tenant')

@section('content')
<h1 class="mb-6 text-2xl font-bold text-slate-800">Add Tenant</h1>

<form action="{{ route('super-admin.tenants.store') }}" method="POST" class="max-w-2xl space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 sm:p-8">
    @csrf
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
        <label for="domain" class="mb-1 block text-sm font-medium text-slate-700">Domain <span class="text-rose-500">*</span></label>
        <input type="text" name="domain" id="domain" value="{{ old('domain') }}" placeholder="brgy-sumpong.test" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-2.5 focus:border-violet-500 focus:ring-violet-500" required>
        <p class="mt-1 text-xs text-slate-500">Tenant is identified by this domain (e.g. brgy-sumpong.test or subdomain.yourdomain.com).</p>
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
@endsection
