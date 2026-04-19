@props(['name'])
@php
    $help = config('bhcas.permission_help.' . $name, []);
    $label = $help['label'] ?? str($name)->headline();
    $description = $help['description'] ?? null;
@endphp
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
    @if($description)
        <span class="mt-0.5 block text-xs leading-snug text-slate-500">{{ $description }}</span>
    @endif
    <span class="mt-0.5 block text-[11px] text-slate-400">{{ $name }}</span>
</div>
