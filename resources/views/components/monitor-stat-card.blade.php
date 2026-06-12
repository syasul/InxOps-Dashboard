@props(['id', 'label', 'color', 'icon'])

@php
$colors = [
    'primary' => 'text-primary bg-primary',
    'secondary' => 'text-secondary bg-secondary',
    'accent' => 'text-accent bg-accent',
];
$c = $colors[$color] ?? 'text-primary bg-primary';
@endphp

<div class="card-stat border-l-4 border-{{ $color }} bg-white/2">
    <div class="flex items-center justify-between mb-4">
        <span class="text-slate-400 text-sm font-medium uppercase tracking-widest">{{ $label }}</span>
        <div class="p-2 {{ str_replace('text-', 'bg-', explode(' ', $c)[0]) }}/10 rounded-lg {{ explode(' ', $c)[0] }}">
            @if($icon === 'cpu')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            @elseif($icon === 'ram')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($icon === 'disk')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 1.105 2.239 2 5 2s5-.895 5-2V7m-10 0c0-1.105 2.239-2 5-2s5 .895 5 2m-10 0c0 1.105 2.239 2 5 2s5-.895 5-2m-10 10c0 1.105 2.239 2 5 2s5-.895 5-2"/></svg>
            @endif
        </div>
    </div>
    <div class="space-y-3">
        <div class="flex items-end justify-between">
            <h3 class="text-2xl font-bold text-white tracking-tight" id="{{ $id }}-stat">--</h3>
            <span class="text-slate-500 text-[10px] uppercase font-black tracking-widest mb-1">Live</span>
        </div>
        <div class="w-full bg-white/5 rounded-full h-1.5 overflow-hidden">
            <div id="{{ $id }}-bar" class="bg-{{ $color }} h-full rounded-full transition-all duration-700 shadow-[0_0_10px_rgba(var(--color-{{ $color }}),0.5)]" style="width: 0%"></div>
        </div>
    </div>
</div>
