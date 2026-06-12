@props(['name', 'status'])

@php
$isRunning = $status === 'running';
$colorClass = $isRunning ? 'emerald' : 'rose';
@endphp

<div class="flex items-center gap-3 p-4 rounded-xl bg-surface border border-white/5 hover:border-{{ $colorClass }}-500/30 transition-colors group">
    <div class="w-2 h-2 rounded-full bg-{{ $colorClass }}-500 {{ $isRunning ? 'animate-pulse' : '' }}"></div>
    <div class="flex-1">
        <p class="text-sm font-medium text-white">{{ $name }}</p>
        <p class="text-[10px] uppercase font-bold text-{{ $colorClass }}-500 tracking-wider">{{ $status }}</p>
    </div>
    <div class="opacity-0 group-hover:opacity-100 transition-opacity">
        <button class="text-slate-500 hover:text-white">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
    </div>
</div>
