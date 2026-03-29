{{-- Central BHCAS logo from config (bhcas.logo_path); text fallback if missing or broken. --}}
@props(['compact' => false])
@php
    $logoPath = config('bhcas.logo_path');
    $logoUrl = $logoPath ? asset($logoPath) : null;
    $maxH = $compact ? 'max-h-16 md:max-h-20' : 'max-h-28 md:max-h-36';
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center']) }}>
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ config('bhcas.name') }}" class="max-w-full h-auto {{ $maxH }} w-auto object-contain" onerror="this.style.display='none'; var n=this.nextElementSibling; if(n) n.classList.remove('hidden');">
        <div class="hidden text-center">
            <p class="text-base font-semibold text-slate-800">{{ config('bhcas.name') }}</p>
        </div>
    @else
        <p class="text-center text-base font-semibold text-slate-800">{{ config('bhcas.name') }}</p>
        <p class="text-xs text-slate-500 mt-0.5">{{ config('bhcas.acronym') }}</p>
    @endif
</div>
