<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ServerStatsService;
use App\Models\ServerMetric;

class LogServerMetrics extends Command
{
    protected $signature = 'server:log-metrics';
    protected $description = 'Log current server metrics to database';

    public function handle(ServerStatsService $statsService)
    {
        $stats = $statsService->getStats();

        ServerMetric::create([
            'cpu_usage' => $stats['cpu'],
            'ram_usage' => $stats['ram']['percent'],
            'disk_usage' => $stats['disk']['percent'],
            'load_avg' => $stats['load'],
            'recorded_at' => now(),
        ]);

        $this->info('Metrics logged successfully.');
    }
}
