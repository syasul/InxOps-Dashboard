<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Global Networking</h1>
                <p class="text-slate-500 text-sm">Manage virtual hosts and internal routing</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex -space-x-3 overflow-hidden">
                    @foreach($subdomains->take(3) as $sub)
                    <div class="inline-block h-8 w-8 rounded-full ring-2 ring-background bg-surface flex items-center justify-center text-[10px] font-bold text-primary border border-white/10 uppercase">
                        {{ substr($sub->subdomain_name, 0, 1) }}
                    </div>
                    @endforeach
                </div>
                <span class="text-xs text-slate-500 font-medium ml-2">{{ $subdomains->count() }} Subdomains active</span>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pb-20">
        <!-- Add Subdomain Form -->
        <div class="lg:col-span-4 lg:sticky lg:top-32 h-fit">
            <div class="glass p-8 md:p-10 rounded-[2.5rem] border border-white/5 relative overflow-hidden group">
                <div class="absolute -right-24 -bottom-24 w-48 h-48 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors duration-700"></div>
                
                <h3 class="text-xl font-bold text-white mb-8 flex items-center gap-2">
                    <div class="w-2 h-6 bg-primary rounded-full"></div>
                    Register Subdomain
                </h3>
                
                <form action="{{ route('subdomains.store') }}" method="POST" class="space-y-8 relative">
                    @csrf
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-500 mb-3 ml-1">Target Application</label>
                            <select name="project_id" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none appearance-none cursor-pointer" required>
                                <option value="" disabled selected class="bg-[#0c0e14]">Choose project...</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" class="bg-[#0c0e14]">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-500 mb-3 ml-1">Domain Prefix</label>
                            <div class="flex items-center group/input overflow-hidden">
                                <input type="text" name="subdomain_name" class="flex-1 min-w-0 bg-white/5 border border-white/10 rounded-l-2xl px-4 py-4 text-white focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all outline-none text-sm font-bold" placeholder="api-v1" required>
                                <span class="bg-white/10 border border-l-0 border-white/10 px-4 py-4 rounded-r-2xl text-slate-400 font-black text-[11px] tracking-tight">.inxdvi.com</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-5 bg-primary hover:bg-primary/90 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-primary/20 transition-all active:scale-[0.98]">
                        Generate & Broadcast Config
                    </button>

                    <p class="text-[10px] text-slate-600 font-bold text-center uppercase tracking-widest leading-relaxed">
                        This will automatically generate Nginx hosts,<br>
                        Enable symlinks, and Update Cloudflare DNS.
                    </p>
                </form>
            </div>
        </div>

        <!-- Subdomain List -->
        <div class="lg:col-span-8 flex flex-col gap-6">
            @forelse($subdomains as $subdomain)
            <div class="glass p-6 rounded-3xl border border-white/5 hover:border-white/10 transition-all group flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <div class="w-14 h-14 rounded-2xl bg-surface border border-white/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 shadow-xl shadow-black/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-bold text-white tracking-tight">{{ $subdomain->subdomain_name }}.inxdvi.com</h3>
                            @if($subdomain->ssl_enabled)
                                <div class="px-2 py-0.5 rounded-md bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.9L9.003 1.2a1 1 0 01.993 0l6.837 3.7A1 1 0 0117.5 5.76v5.24a8.001 8.001 0 01-5.96 7.734l-.54.14a1 1 0 01-.493 0l-.54-.14a8.001 8.001 0 01-5.96-7.734V5.76a1 1 0 01.659-.958zM10 4.84l4.5 2.438v3.46c0 3.19-2.008 5.92-4.5 6.745V4.84z" clip-rule="evenodd"/></svg>
                                    <span class="text-[9px] font-black text-emerald-500 uppercase tracking-tighter">SSL ACTIVE</span>
                                </div>
                            @endif
                        </div>
                        <p class="text-sm text-slate-500 font-medium mt-1">Routing to <span class="text-slate-300">{{ $subdomain->project->name }}</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-4 bg-white/2 p-2 rounded-2xl border border-white/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <button class="p-2.5 text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <div class="w-px h-4 bg-white/10 mx-1"></div>
                    <form action="{{ route('subdomains.destroy', $subdomain) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2.5 text-rose-500/50 hover:text-rose-500 hover:bg-rose-500/10 rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="glass p-20 rounded-[3rem] text-center border border-white/5">
                <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">No Networking Rules Found</h3>
                <p class="text-slate-500 text-sm">Add your first subdomain to map it to an application.</p>
            </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
