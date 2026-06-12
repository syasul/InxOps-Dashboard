<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 w-full">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-[2rem] bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-2xl shadow-primary/20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-white uppercase tracking-tighter italic leading-none mb-1">{{ $project->name }}</h1>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 text-xs font-black uppercase tracking-widest">{{ $project->branch }}</span>
                        <span class="w-1 h-1 bg-slate-800 rounded-full"></span>
                        <a href="{{ $project->repo_url }}" target="_blank" class="text-primary text-[10px] font-black uppercase tracking-widest hover:underline flex items-center gap-1">
                            View Repository
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <form action="{{ route('projects.pull', $project) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl text-xs font-black text-slate-300 uppercase tracking-widest transition-all flex items-center gap-2 group">
                        <svg class="w-4 h-4 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Sync & Pull
                    </button>
                </form>

                <form action="{{ route('projects.deploy', $project) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-8 py-4 bg-emerald-500 hover:bg-emerald-400 rounded-2xl text-xs font-black text-white uppercase tracking-widest transition-all shadow-xl shadow-emerald-500/20 active:scale-95 flex items-center gap-2 group">
                        <svg class="w-4 h-4 group-hover:animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Build Now
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar Info -->
        <div class="space-y-8">
            <div class="glass p-8 rounded-[3rem] border border-white/5 space-y-6 overflow-hidden relative group">
                <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-transparent"></div>
                
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest relative z-10">Infrastructure Details</h3>
                
                <div class="space-y-4 relative z-10">
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Root Directory</p>
                        <p class="text-xs font-mono text-white truncate">{{ $project->directory_path }}</p>
                    </div>
                    
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Local Port</p>
                        <p class="text-xs font-mono text-white">{{ $project->port ?? 'None (Static/PHP)' }}</p>
                    </div>

                    <div class="p-4 bg-white/5 rounded-2xl border border-white/5">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Active Subdomains</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                             {{-- Placeholder for subdomains relation --}}
                             <span class="text-[10px] font-black text-primary bg-primary/10 px-3 py-1 rounded-full uppercase">Global Entrypoint</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="glass p-8 rounded-[3rem] border border-rose-500/10 space-y-4">
                <h3 class="text-xs font-black text-rose-500 uppercase tracking-widest">Danger Zone</h3>
                <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Archive project forever?')">
                    @csrf @method('DELETE')
                    <button class="w-full py-4 bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white border border-rose-500/20 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                        Archive Project
                    </button>
                </form>
            </div>
        </div>

        <!-- Timeline / Deployments -->
        <div class="lg:col-span-2 space-y-8">
            <div class="glass p-8 rounded-[3rem] border border-white/5 min-h-[500px]">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest">Deployment Timeline</h3>
                    <span class="text-[10px] font-black text-slate-600 bg-white/5 px-4 py-1.5 rounded-full uppercase">Last 10 Actions</span>
                </div>

                @if($project->deployments->count() > 0)
                <div class="space-y-6">
                    @foreach($project->deployments as $deployment)
                    <div class="flex gap-6 group">
                        <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full border-2 {{ $deployment->status === 'completed' ? 'border-emerald-500 bg-emerald-500' : ($deployment->status === 'failed' ? 'border-rose-500 bg-rose-500' : 'border-primary bg-primary animate-pulse') }}"></div>
                            <div class="w-px h-full bg-slate-800 group-last:bg-transparent mt-2"></div>
                        </div>
                        <div class="flex-1 pb-8">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-black text-white uppercase tracking-tight italic">{{ $deployment->status === 'completed' ? 'Production Build Success' : ($deployment->status === 'failed' ? 'Operation Terminated' : 'Synchronizing Data...') }}</span>
                                <span class="text-[10px] text-slate-600 font-bold uppercase tracking-widest">{{ $deployment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-xs text-slate-500 font-medium">Commit: <span class="font-mono text-slate-400">#{{ substr(md5($deployment->id), 0, 7) }}</span> • Triggered via InxOps Console</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="flex flex-col items-center justify-center py-20 text-center space-y-6">
                    <div class="w-24 h-24 rounded-[2.5rem] bg-white/2 flex items-center justify-center text-slate-800">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-white font-black uppercase tracking-tighter text-xl mb-2">No Deployments Recorded</h4>
                        <p class="text-slate-600 text-sm max-w-xs mx-auto font-medium leading-relaxed">Initialize your first server synchronization to see build reports here.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
