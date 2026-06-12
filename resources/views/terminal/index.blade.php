<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white uppercase tracking-tighter italic">Mission Control TTY</h1>
                <p class="text-slate-500 text-sm">Hardware-accelerated terminal emulator</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-primary/10 border border-primary/20">
                    <span class="text-[10px] font-black text-primary uppercase tracking-[0.2em]">xterm.js core v5.3</span>
                </div>
            </div>
        </div>
    </x-slot>

    <!-- Dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css" />
    <script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.js"></script>

    <div class="max-w-6xl mx-auto">
        <div class="glass-dark rounded-[2.5rem] border border-white/5 shadow-[0_20px_50px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col h-[75vh]">
            <!-- Terminal Chrome -->
            <div class="bg-white/5 px-8 py-5 flex items-center justify-between border-b border-white/5 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-primary/5 via-transparent to-transparent"></div>
                <div class="flex items-center gap-3 relative">
                    <div class="flex gap-1.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-rose-500/50 shadow-[0_0_10px_rgba(244,63,94,0.3)]"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-amber-500/50 shadow-[0_0_10px_rgba(245,158,11,0.3)]"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/50 shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                    </div>
                    <div class="h-4 w-px bg-white/10 mx-2"></div>
                    <span class="text-[10px] font-mono font-bold text-slate-500 tracking-[0.3em] uppercase">Kernel Access: 127.0.0.1</span>
                </div>
                <div class="flex items-center gap-6 relative">
                    <div class="text-[9px] font-mono font-black text-slate-600 uppercase tracking-widest hidden md:block">Connected via HTTPS/AES-256</div>
                    <button id="clear-btn" class="text-[10px] font-black text-slate-400 hover:text-white uppercase tracking-widest bg-white/5 px-3 py-1 rounded-lg border border-white/5 transition-all">Clear SCR</button>
                </div>
            </div>

            <!-- Terminal Container -->
            <div id="terminal-container" class="flex-1 p-6 bg-black relative">
                <!-- Overlay Glow -->
                <div class="absolute inset-0 pointer-events-none shadow-[inset_0_0_100px_rgba(59,130,246,0.05)] z-10"></div>
                <div id="terminal" class="w-full h-full"></div>
            </div>
        </div>
        
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass p-6 rounded-3xl border border-white/5 flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <div>
                    <h4 class="text-white font-bold">SSH Keys</h4>
                    <p class="text-slate-500 text-xs">Manage authentication identities</p>
                </div>
            </div>
            <div class="glass p-6 rounded-3xl border border-white/5 flex items-center gap-4 group opacity-50">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                </div>
                <div>
                    <h4 class="text-white font-bold">Upload Files</h4>
                    <p class="text-slate-500 text-xs text-nowrap">Drag & drop coming soon</p>
                </div>
            </div>
            <div class="glass p-6 rounded-3xl border border-white/5 flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <h4 class="text-white font-bold">Aliases</h4>
                    <p class="text-slate-500 text-xs">Custom command shortcuts</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const term = new Terminal({
                cursorBlink: true,
                theme: {
                    background: '#000000',
                    foreground: '#ffffff',
                    cursor: '#3b82f6',
                    selection: 'rgba(59, 130, 246, 0.3)',
                    black: '#000000',
                    red: '#f43f5e',
                    green: '#10b981',
                    yellow: '#f59e0b',
                    blue: '#3b82f6',
                    magenta: '#8b5cf6',
                    cyan: '#06b6d4',
                    white: '#ffffff',
                },
                fontSize: 14,
                fontFamily: '"JetBrains Mono", "Fira Code", monospace',
                letterSpacing: 0.5,
                lineHeight: 1.2
            });

            const fitAddon = new FitAddon.FitAddon();
            term.loadAddon(fitAddon);
            term.open(document.getElementById('terminal'));
            fitAddon.fit();

            let currentCwd = '{{ base_path() }}';
            let currentInput = '';

            function getPrompt() {
                const displayCwd = currentCwd.replace('{{ base_path() }}', '~');
                return `\x1b[1;34minxops\x1b[0m:\x1b[1;36m${displayCwd}\x1b[0m$ `;
            }

            term.writeln('\x1b[1;34mInxOps Mission Control Shell [v2.0]\x1b[0m');
            term.writeln('\x1b[2mKernel Access Established. No TTY detected.\x1b[0m\n');
            term.write(getPrompt());

            term.onData(async e => {
                switch (e) {
                    case '\r': // Enter
                        term.write('\r\n');
                        if (currentInput.trim() === 'clear') {
                            term.clear();
                            // Reset prompt after clear
                            currentInput = '';
                            term.write(getPrompt());
                            break;
                        }
                        
                        if (currentInput.trim()) {
                            await executeCommand(currentInput);
                        } else {
                            term.write(getPrompt());
                        }
                        currentInput = '';
                        break;
                    case '\u007F': // Backspace
                        if (currentInput.length > 0) {
                            currentInput = currentInput.slice(0, -1);
                            term.write('\b \b');
                        }
                        break;
                    default:
                        if (e >= ' ' || e === '\t') {
                            currentInput += e;
                            term.write(e);
                        }
                }
            });

            async function executeCommand(command) {
                try {
                    const response = await fetch('{{ route("terminal.execute") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ command, cwd: currentCwd })
                    });
                    
                    const data = await response.json();
                    
                    if (data.output) {
                        // Correctly handle newlines for xterm.js
                        const lines = data.output.split('\n');
                        lines.forEach(line => term.writeln(line));
                    }
                    
                    if (data.cwd) {
                        currentCwd = data.cwd;
                    }
                    
                    term.write(getPrompt());
                } catch (err) {
                    term.writeln(`\x1b[1;31mError: ${err.message}\x1b[0m`);
                    term.write(getPrompt());
                }
            }

            document.getElementById('clear-btn').addEventListener('click', () => {
                term.clear();
                currentInput = '';
                term.write(getPrompt());
                term.focus();
            });

            window.addEventListener('resize', () => fitAddon.fit());
            term.focus();
        });
    </script>
    <style>
        .xterm-viewport::-webkit-scrollbar { width: 8px; }
        .xterm-viewport::-webkit-scrollbar-track { background: transparent; }
        .xterm-viewport::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        .xterm-viewport::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
    </style>
    @endpush
</x-app-layout>
