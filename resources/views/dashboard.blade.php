<x-app-layout>
    <div class="space-y-8">
        <!-- Welcome Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight">System Overview</h1>
                <p class="text-slate-400 mt-1">G'day, {{ Auth::user()->name }}. Here's what's happening with your server today.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('projects.create') }}" class="btn-primary flex items-center gap-2 px-6 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    New Project
                </a>
            </div>
        </div>

        <!-- Global Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- CPU (Dynamic via API on Monitoring page, but static here for now) -->
            <div class="card-stat border-l-4 border-primary">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate-400 text-sm font-medium">CPU Status</span>
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <h3 class="text-3xl font-bold text-white" id="dash-cpu">--%</h3>
                    </div>
                    <span class="text-emerald-500 text-xs font-medium flex items-center gap-1 mb-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Healthy
                    </span>
                </div>
            </div>

            <!-- Active Projects -->
            <div class="card-stat border-l-4 border-secondary">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate-400 text-sm font-medium">Manage Projects</span>
                    <div class="p-2 bg-secondary/10 rounded-lg">
                        <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <h3 class="text-3xl font-bold text-white">{{ $activeProjects }}</h3>
                    <span class="text-slate-400 text-xs font-medium mb-1">of {{ $projectCount }} total</span>
                </div>
            </div>

            <!-- Subdomains -->
            <div class="card-stat border-l-4 border-accent">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate-400 text-sm font-medium">Networking</span>
                    <div class="p-2 bg-accent/10 rounded-lg">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <h3 class="text-3xl font-bold text-white">{{ $subdomainCount }}</h3>
                    <span class="text-slate-400 text-xs font-medium mb-1">Subdomains</span>
                </div>
            </div>

            <!-- Uptime/Load -->
            <div class="card-stat border-l-4 border-orange-500">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate-400 text-sm font-medium">Uptime</span>
                    <div class="p-2 bg-orange-500/10 rounded-lg">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <h3 class="text-2xl font-bold text-white" id="dash-uptime">--</h3>
                    <span class="text-slate-400 text-xs font-medium mb-1">System Up</span>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Projects -->
            <div class="lg:col-span-2 space-y-8">
                <div class="glass rounded-3xl overflow-hidden">
                    <div class="p-8 border-b border-white/5 flex items-center justify-between bg-white/2">
                        <div>
                            <h3 class="text-lg font-bold text-white">Recent Projects</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Manage and monitor your latest deployments</p>
                        </div>
                        <a href="{{ route('projects.index') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-sm text-slate-200 transition-all font-medium border border-white/10">View All</a>
                    </div>
                    <div class="divide-y divide-white/5">
                        @forelse($projects as $project)
                        <div class="p-6 flex items-center justify-between hover:bg-white/5 transition-all group">
                            <div class="flex items-center gap-5">
                                <div class="w-14 h-14 rounded-2xl bg-surface border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-white text-lg">{{ $project->name }}</h4>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-slate-500 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M8 7h8"/></svg>
                                            {{ $project->branch }}
                                        </span>
                                        <span class="text-xs text-slate-500">•</span>
                                        <span class="text-xs text-slate-500">{{ $project->last_deploy_at?->diffForHumans() ?? 'No deployments' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="hidden md:flex flex-col items-end">
                                    <span class="text-[10px] uppercase font-black tracking-widest text-emerald-500/50 mb-1">Status</span>
                                    <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-500 text-[10px] font-bold uppercase tracking-wider border border-emerald-500/20">Active</span>
                                </div>
                                <a href="{{ route('projects.show', $project) }}" class="p-3 bg-white/5 rounded-xl text-slate-400 hover:text-white hover:bg-primary/20 transition-all">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                        @empty
                        <div class="p-12 text-center text-slate-500">No projects found. Add one to see it here!</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Column: Activity & Mini Monitor -->
            <div class="space-y-8">
                <!-- Mini Real-time Monitor -->
                <div class="glass p-8 rounded-3xl space-y-6 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/10 rounded-full blur-3xl"></div>
                    <div class="flex items-center justify-between relative">
                        <h3 class="font-bold text-white">Live Health</h3>
                        <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-[9px] font-bold text-emerald-500 uppercase tracking-tighter">Live</span>
                        </div>
                    </div>
                    
                    <div class="space-y-4 relative">
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-medium text-slate-400 uppercase tracking-widest">
                                <span>Processor</span>
                                <span id="mini-cpu">--%</span>
                            </div>
                            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                                <div id="mini-cpu-bar" class="h-full bg-primary transition-all duration-700 w-0"></div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-medium text-slate-400 uppercase tracking-widest">
                                <span>Memory</span>
                                <span id="mini-ram">--%</span>
                            </div>
                            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                                <div id="mini-ram-bar" class="h-full bg-secondary transition-all duration-700 w-0"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="glass rounded-3xl overflow-hidden flex-1">
                    <div class="p-6 border-b border-white/5 bg-white/2">
                        <h3 class="font-bold text-white">Activity Feed</h3>
                    </div>
                    <div class="p-6 space-y-8">
                        @forelse($recentDeployments as $deploy)
                        <div class="flex gap-5 relative group">
                            <!-- Timeline Line -->
                            @if(!$loop->last)
                            <div class="absolute top-10 bottom-[-32px] left-[15px] w-0.5 bg-white/5"></div>
                            @endif
                            
                            <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center border {{ $deploy->status === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-500' : 'bg-rose-500/10 border-rose-500/30 text-rose-500' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($deploy->status === 'success')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    @endif
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm text-slate-200">
                                    <span class="text-white font-bold">{{ $deploy->project->name }}</span> 
                                    {{ $deploy->status === 'success' ? 'deployed successfully' : 'deployment failed' }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">{{ $deploy->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-6">
                            <p class="text-sm text-slate-500 italic">No recent activity found.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            async function updateMiniMonitor() {
                try {
                    const response = await fetch('{{ route("api.metrics") }}');
                    const data = await response.json();
                    
                    document.getElementById('dash-cpu').innerText = data.cpu.toFixed(0) + '%';
                    document.getElementById('mini-cpu').innerText = data.cpu.toFixed(0) + '%';
                    document.getElementById('mini-cpu-bar').style.width = data.cpu + '%';
                    
                    document.getElementById('mini-ram').innerText = data.ram.percent + '%';
                    document.getElementById('mini-ram-bar').style.width = data.ram.percent + '%';
                    
                    document.getElementById('dash-uptime').innerText = data.uptime;
                } catch (e) { console.error(e); }
            }
            setInterval(updateMiniMonitor, 5000);
            updateMiniMonitor();
        });
    </script>
    @endpush
</x-app-layout>
