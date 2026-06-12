<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Live Operations</h1>
                <p class="text-slate-500 text-sm">Real-time infrastructure health monitoring</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.1)]">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-xs font-bold text-emerald-500 uppercase tracking-widest">Active</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">
        <!-- Monitoring Top Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-monitor-stat-card id="cpu" label="Processor (CPU)" color="primary" icon="cpu" />
            <x-monitor-stat-card id="ram" label="Physical RAM" color="secondary" icon="ram" />
            <x-monitor-stat-card id="disk" label="Storage (Disk)" color="accent" icon="disk" />
            <div class="card-stat border-l-4 border-orange-500 bg-white/2">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate-400 text-sm font-medium uppercase tracking-widest">Load Average</span>
                    <div class="p-2 bg-orange-500/10 rounded-lg text-orange-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>
                <div class="flex items-end justify-between">
                    <h3 class="text-2xl font-bold text-white" id="load-stat">--</h3>
                    <span class="text-slate-500 text-[10px] font-bold uppercase tracking-tighter">System Load</span>
                </div>
            </div>
        </div>

        <!-- System Services Status -->
        <div class="glass rounded-3xl overflow-hidden border border-white/5 relative">
            <div class="absolute -right-32 -top-32 w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
            <div class="p-6 border-b border-white/5 bg-white/2 relative">
                <h3 class="font-bold text-white text-lg">System Services</h3>
            </div>
            <div class="p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 relative">
                <x-service-badge name="Web Server (Nginx)" status="running" />
                <x-service-badge name="Database (MySQL)" status="running" />
                <x-service-badge name="Runtime (PHP-FPM)" status="running" />
                <x-service-badge name="Background Queue" status="stopped" />
            </div>
        </div>

        <!-- Realtime Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="glass p-8 rounded-3xl border border-white/5 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-tr from-primary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                <div class="flex items-center justify-between mb-8 relative">
                    <h3 class="text-white font-bold text-xl">CPU Performance</h3>
                    <span class="text-xs text-slate-500 bg-white/5 px-3 py-1 rounded-full uppercase font-black tracking-widest leading-none">Last 30m</span>
                </div>
                <div class="h-72 relative">
                    <canvas id="cpuChart"></canvas>
                </div>
            </div>
            
            <div class="glass p-8 rounded-3xl border border-white/5 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-tr from-secondary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                <div class="flex items-center justify-between mb-8 relative">
                    <h3 class="text-white font-bold text-xl">Memory Utilization</h3>
                    <span class="text-xs text-slate-500 bg-white/5 px-3 py-1 rounded-full uppercase font-black tracking-widest leading-none">Usage Trend</span>
                </div>
                <div class="h-72 relative">
                    <canvas id="ramChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartConfig = (label, color) => ({
                type: 'line',
                data: {
                    labels: Array(40).fill(''),
                    datasets: [{
                        label: label,
                        data: Array(40).fill(null),
                        borderColor: color,
                        borderWidth: 2,
                        tension: 0.5,
                        pointRadius: 0,
                        fill: true,
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                            gradient.addColorStop(0, color + '40');
                            gradient.addColorStop(1, color + '00');
                            return gradient;
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { display: false },
                        y: { 
                            beginAtZero: true, 
                            max: 100,
                            grid: { color: 'rgba(255,255,255,0.03)' },
                            ticks: { 
                                color: 'rgba(255,255,255,0.3)', 
                                font: { size: 10 },
                                callback: val => val + '%'
                            }
                        }
                    }
                }
            });

            const cpuChart = new Chart(document.getElementById('cpuChart'), chartConfig('CPU', '#3b82f6'));
            const ramChart = new Chart(document.getElementById('ramChart'), chartConfig('RAM', '#8b5cf6'));

            async function updateStats() {
                try {
                    const response = await fetch('{{ route("api.metrics") }}');
                    const data = await response.json();
                    
                    const cpuVal = data.cpu;
                    const ramVal = data.ram.percent;
                    
                    document.getElementById('cpu-stat').innerText = cpuVal.toFixed(1) + '%';
                    document.getElementById('ram-stat').innerText = data.ram.used + ' / ' + data.ram.total + ' GB';
                    document.getElementById('disk-stat').innerText = data.disk.percent + '%';
                    document.getElementById('load-stat').innerText = data.load;
                    
                    document.getElementById('cpu-bar').style.width = cpuVal + '%';
                    document.getElementById('ram-bar').style.width = ramVal + '%';
                    document.getElementById('disk-bar').style.width = data.disk.percent + '%';

                    [cpuChart, ramChart].forEach((chart, i) => {
                        const val = i === 0 ? cpuVal : ramVal;
                        chart.data.datasets[0].data.shift();
                        chart.data.datasets[0].data.push(val);
                        chart.update('none');
                    });
                } catch (e) { console.error(e); }
            }

            setInterval(updateStats, 3000);
            updateStats();
        });
    </script>
    @endpush
</x-app-layout>
