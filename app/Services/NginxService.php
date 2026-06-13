<?php

namespace App\Services;

use App\Models\Subdomain;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class NginxService
{
    public function generateConfig(Subdomain $subdomain)
    {
        $project = $subdomain->project;
        $fullDomain = $subdomain->subdomain_name . '.inxdvi.com';
        $shortName = $subdomain->subdomain_name;
        $template = $this->getTemplate($fullDomain, $project);
        
        $availablePath = "/etc/nginx/sites-available/{$fullDomain}";
        $oldPath = "/etc/nginx/sites-available/{$shortName}";
        
        // Write locally first
        $tempPath = storage_path("nginx/{$fullDomain}.conf");
        File::ensureDirectoryExists(storage_path("nginx"));
        File::put($tempPath, $template);

        // Forced Cleanup: Remove any old potential config files to avoid conflicts
        $this->runSudo(['rm', '-f', $availablePath]);
        $this->runSudo(['rm', '-f', $oldPath]);
        $this->runSudo(['rm', '-f', "/etc/nginx/sites-enabled/{$fullDomain}"]);
        $this->runSudo(['rm', '-f', "/etc/nginx/sites-enabled/{$shortName}"]);

        // Copy new config
        $this->runSudo(['cp', $tempPath, $availablePath]);
        
        return $availablePath;
    }

    public function enableConfig(Subdomain $subdomain)
    {
        $fullDomain = $subdomain->subdomain_name . '.inxdvi.com';
        $availablePath = "/etc/nginx/sites-available/{$fullDomain}";
        $enabledPath = "/etc/nginx/sites-enabled/{$fullDomain}";

        // 1. Symlink with sudo
        $this->runSudo(['ln', '-sf', $availablePath, $enabledPath]);
        
        // 2. Reload Nginx
        $this->reload();

        // 3. Auto-start the application on port 8000
        return $this->startApplication($subdomain->project);
    }

    public function startApplication($project)
    {
        $path = $project->directory_path;
        $port = $project->port ?? 8000;
        
        // Expand tilde (~) to absolute home directory
        if (str_starts_with($path, '~')) {
            $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
            $path = str_replace('~', $home, $path);
        }

        // 1. Forcefully kill any process already using the port to avoid conflicts
        $killCommand = "fuser -k {$port}/tcp > /dev/null 2>&1 || true";
        exec($killCommand);

        // 2. Start the application using 'nohup' to keep it running in the background
        $command = "cd {$path} && nohup php artisan serve --port={$port} > /dev/null 2>&1 &";
        
        $process = Process::fromShellCommandline($command);
        $process->start();
        
        return true;
    }

    protected function getTemplate($domain, $project)
    {
        // Check if project has a port set, if so use Proxy, otherwise use PHP-FPM
        // This is a simplified check, usually we'd pass this in.
        $port = $project->port ?? 8000;
        return $this->reverseProxyTemplate($domain, $port);
    }

    protected function reverseProxyTemplate($domain, $port)
    {
        return "server {
    listen 80;
    server_name {$domain};

    location / {
        proxy_pass http://127.0.0.1:{$port};
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
    }
}";
    }

    protected function runSudo(array $command)
    {
        $process = new Process(array_merge(['sudo'], $command));
        $process->run();
        return $process->isSuccessful();
    }

    public function reload()
    {
        // Try systemctl first, fallback to nginx -s reload
        if ($this->runSudo(['systemctl', 'reload', 'nginx'])) {
            return true;
        }
        return $this->runSudo(['nginx', '-s', 'reload']);
    }
}
