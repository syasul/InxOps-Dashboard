<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body class="font-sans antialiased bg-background text-slate-200">
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <aside class="w-72 glass-dark border-r border-white/5 flex flex-col fixed h-full z-50 overflow-hidden">
                <!-- Branding -->
                <div class="p-8 pb-4 relative">
                    <div class="absolute -top-10 -left-10 w-32 h-32 bg-primary/20 rounded-full blur-3xl"></div>
                    <div class="flex items-center gap-4 relative">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary to-blue-600 shadow-[0_0_20px_rgba(59,130,246,0.3)] flex items-center justify-center border border-white/20">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <span class="text-2xl font-black tracking-tighter text-white block leading-none">Inx<span class="text-primary italic">Ops</span></span>
                            <span class="text-[9px] uppercase font-black tracking-[0.3em] text-slate-500 mt-1 block">Control Center</span>
                        </div>
                    </div>
                </div>

                <!-- Nav Section -->
                <div class="mt-8 flex-1 px-4 space-y-1.5 font-medium">
                    <div class="px-4 mb-4">
                        <span class="text-[10px] uppercase font-bold text-slate-600 tracking-widest">Main Menu</span>
                    </div>
                    <x-nav-link-custom :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="dashboard">
                        Command Center
                    </x-nav-link-custom>
                    <x-nav-link-custom :href="route('projects.index')" :active="request()->routeIs('projects.*')" icon="projects">
                        Deployments
                    </x-nav-link-custom>
                    <x-nav-link-custom :href="route('storage.index')" :active="request()->routeIs('storage.*')" icon="storage">
                        Drive Explorer
                    </x-nav-link-custom>
                    <x-nav-link-custom :href="route('monitoring')" :active="request()->routeIs('monitoring')" icon="monitoring">
                        System Health
                    </x-nav-link-custom>
                    <x-nav-link-custom :href="route('subdomains.index')" :active="request()->routeIs('subdomains.*')" icon="subdomains">
                        Global Network
                    </x-nav-link-custom>
                    <x-nav-link-custom :href="route('terminal.index')" :active="request()->routeIs('terminal.*')" icon="terminal">
                        Secure Terminal
                    </x-nav-link-custom>
                </div>

                <!-- Footer / Profile -->
                <div class="p-6">
                    <div class="p-4 glass rounded-[2rem] border border-white/5 relative group overflow-hidden">
                        <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="flex items-center gap-4 relative">
                            <div class="w-12 h-12 rounded-2xl bg-surface border border-white/10 flex items-center justify-center text-primary font-black text-xl shadow-inner group-hover:scale-105 transition-transform">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white truncate leading-tight">{{ Auth::user()->name }}</p>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold text-slate-500 hover:text-primary uppercase tracking-widest mt-1">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col ml-72">
                <!-- Header -->
                <header class="h-24 glass-dark border-b border-white/5 flex items-center justify-between px-10 sticky top-0 z-40 backdrop-blur-3xl">
                    <div class="flex-1">
                        @isset($header)
                            {{ $header }}
                        @else
                            <h2 class="text-2xl font-black text-white tracking-tight uppercase">Dashboard</h2>
                        @endisset
                    </div>
                    
                    <div class="flex items-center gap-8 pl-10">
                        <div class="hidden xl:flex items-center gap-3 px-4 py-2 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 shadow-[0_0_20px_rgba(16,185,129,0.05)]">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest leading-none">Server Online</span>
                        </div>
                        <div class="relative">
                            <button class="p-2.5 text-slate-400 hover:text-white transition-all hover:scale-110">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-primary border-2 border-background rounded-full"></span>
                            </button>
                        </div>
                    </div>
                </header>

                <main class="p-10 pb-20">
                    {{ $slot }}
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
