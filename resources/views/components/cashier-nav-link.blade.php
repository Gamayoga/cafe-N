@props(['active', 'icon'])

@php
$classes = ($active ?? false)
            ? 'sidebar-item-active flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all w-full text-white'
            : 'flex items-center gap-4 px-6 py-4 rounded-2xl font-bold text-gray-500 hover:bg-gray-800/50 hover:text-white transition-all w-full';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if(isset($icon))
        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
        </svg>
    @endif
    <span class="text-sm tracking-tight">{{ $slot }}</span>
</a>
