@extends('layouts.requirements')

@section('title', 'Project Requirements – ' . $appName)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">FINAL PROJECT</h1>
        <p class="text-lg text-[#706f6c] dark:text-[#A1A09A]">{{ $subtitle }}</p>
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">{{ $appName }} ({{ $acronym }})</p>
    </div>

    <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Requirements</h2>
    <ul class="space-y-4 mb-8">
        @foreach($requirements as $key => $req)
        <li class="p-4 rounded-lg bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] shadow-sm">
            <h3 class="font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">{{ $req['label'] }}</h3>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-2">{{ $req['description'] }}</p>
            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] border-l-2 border-[#f53003] dark:border-[#FF4433] pl-3"><strong>BHCAS:</strong> {{ $req['implementation'] }}</p>
        </li>
        @endforeach
    </ul>

    <h2 class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Pricing Tiers (Slide → BHCAS)</h2>
    <div class="overflow-x-auto rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] mb-8">
        <table class="w-full text-sm text-left">
            <thead class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC]">
                <tr>
                    <th class="px-4 py-3 font-medium">Slide</th>
                    <th class="px-4 py-3 font-medium">BHCAS Plan</th>
                    <th class="px-4 py-3 font-medium">Capabilities</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC]">
                @foreach($pricingTiers as $tier)
                <tr class="border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                    <td class="px-4 py-3">{{ $tier['slide'] }}</td>
                    <td class="px-4 py-3 font-medium">{{ $tier['plan'] }}</td>
                    <td class="px-4 py-3 text-[#706f6c] dark:text-[#A1A09A]">{{ $tier['features'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
        {{ $appName }} is built to satisfy the final project requirements for a multi-tenant SaaS web application: multi-tenancy, RBAC, plan-based customization, a clear pricing model, single-codebase updates, tenancy via tenant_id, and data isolation through scopes and constraints.
    </p>

    <div class="mt-6">
        <a href="{{ url('/') }}" class="inline-block px-5 py-1.5 dark:bg-[#eeeeec] dark:border-[#eeeeec] dark:text-[#1C1C1A] border border-[#19140035] hover:border-[#1915014a] dark:hover:border-[#62605b] rounded-sm text-sm">
            ← Back to Home
        </a>
    </div>
</div>
@endsection
