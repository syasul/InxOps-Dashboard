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
    </head>
    <body class="font-sans antialiased bg-background text-slate-200">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="mb-8 flex flex-col items-center gap-4">
                <a href="/" class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary shadow-lg shadow-primary/20 flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <span class="text-3xl font-bold tracking-tight text-white">Inx<span class="text-primary">Ops</span></span>
                </a>
            </div>

            <div class="w-full sm:max-w-md glass-dark p-10 rounded-3xl border border-white/5 shadow-2xl relative overflow-hidden">
                <!-- Glossy overlay -->
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-primary/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-secondary/10 rounded-full blur-3xl"></div>
                
                <div class="relative">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
