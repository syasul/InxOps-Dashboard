<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Subdomain;
use App\Services\NginxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NginxServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_nginx_config_generation_uses_custom_project_port()
    {
        // 1. Arrange: Create a project with a custom port and a subdomain
        $project = Project::create([
            'name' => 'Test App',
            'repo_url' => 'https://github.com/example/test-app',
            'branch' => 'main',
            'directory_path' => '/home/user/test-app',
            'port' => 8899,
            'active' => true,
        ]);

        $subdomain = Subdomain::create([
            'project_id' => $project->id,
            'subdomain_name' => 'sekolah',
            'ssl_enabled' => false,
        ]);

        // 2. Act: Call generateConfig on a mockable NginxService subclass
        $nginxService = new class extends NginxService {
            protected function runSudo(array $command): bool
            {
                // Bypass actual sudo command execution in tests
                return true;
            }
        };

        $tempPath = storage_path("nginx/sekolah.inxdvi.com.conf");
        @unlink($tempPath);

        $nginxService->generateConfig($subdomain);

        // 3. Assert: Verify the file exists and contains the correct port
        $this->assertFileExists($tempPath);
        $configContent = File::get($tempPath);
        $this->assertStringContainsString('proxy_pass http://127.0.0.1:8899;', $configContent);

        // Clean up
        @unlink($tempPath);
    }
}
