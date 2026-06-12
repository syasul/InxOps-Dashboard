<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Project Registry</h1>
                <p class="text-slate-500 text-sm">Manage and deploy your applications</p>
            </div>
            <a href="{{ route('projects.create') }}" class="btn-primary flex items-center gap-2 group px-6 py-3">
                <div class="w-5 h-5 rounded-md bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                </div>
                New Application
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6">
        @forelse($projects as $project)
        <div class="glass p-6 rounded-3xl border border-white/5 hover:border-primary/20 transition-all group relative overflow-hidden">
            <!-- Background Accent -->
            <div class="absolute -right-16 -top-16 w-32 h-32 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors"></div>
            
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative">
                <div class="flex items-start gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-surface border border-white/10 flex items-center justify-center text-primary group-hover:shadow-[0_0_20px_rgba(59,130,246,0.2)] transition-all duration-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-xl font-bold text-white">{{ $project->name }}</h3>
                            <span class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-black uppercase tracking-widest border border-primary/20">
                                {{ $project->branch }}
                            </span>
                        </div>
                        <p class="text-slate-400 text-sm mt-1 font-mono opacity-80">{{ $project->repo_url }}</p>
                        <div class="flex items-center gap-4 mt-4 text-xs text-slate-500 font-medium">
                            <span class="flex items-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-500"></div>
                                {{ $project->directory_path }}
                            </span>
                            @if($project->port)
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                Port: {{ $project->port }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 lg:gap-8 bg-white/2 p-4 rounded-2xl border border-white/5">
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Last Activity</span>
                        <span class="text-sm text-slate-200 font-medium">{{ $project->last_deploy_at?->diffForHumans() ?? 'Never Deployed' }}</span>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Deployments</span>
                        <span class="text-sm text-slate-200 font-medium">{{ $project->deployments_count }} total</span>
                    </div>

                    <div class="flex items-center gap-3 pl-4 border-l border-white/10 ml-auto lg:ml-0">
                        <form action="{{ route('projects.deploy', $project) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-primary p-3 rounded-xl flex items-center justify-center hover:shadow-primary/40 active:scale-90 transition-all">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </button>
                        </form>
                        <a href="{{ route('projects.show', $project) }}" class="p-3 bg-white/5 border border-white/10 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                        <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Delete project?')">
                            @csrf
                            @method('DELETE')
                            <button class="p-3 bg-white/5 border border-white/10 rounded-xl text-rose-500/50 hover:text-rose-500 hover:bg-rose-500/10 hover:border-rose-500/20 transition-all">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="glass p-20 rounded-3xl flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 rounded-full bg-white/5 flex items-center justify-center text-slate-500 mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2-2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">No projects yet</h2>
            <p class="text-slate-500 max-w-sm mb-8">Ready to deploy your first application? Connect your GitHub repository to get started.</p>
            <a href="{{ route('projects.create') }}" class="btn-primary px-8 py-4">Create Your First Project</a>
        </div>
        @endforelse
    </div>
</x-app-layout>
