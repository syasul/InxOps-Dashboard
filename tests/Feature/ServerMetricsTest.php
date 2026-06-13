<?php

namespace Tests\Feature;

use App\Models\ServerMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_log_metrics_command_logs_metrics_successfully()
    {
        // 1. Act: Call the artisan command
        $this->artisan('server:log-metrics')
             ->assertExitCode(0);

        // 2. Assert: Verify the metric is logged in the database
        $this->assertDatabaseCount('server_metrics', 1);
        
        $metric = ServerMetric::first();
        $this->assertNotNull($metric->cpu_usage);
        $this->assertNotNull($metric->ram_usage);
        $this->assertNotNull($metric->disk_usage);
        $this->assertNotNull($metric->load_avg);
    }
}
