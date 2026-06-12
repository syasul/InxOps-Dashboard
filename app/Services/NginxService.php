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
        $template = $this->getTemplate($fullDomain, $project->directory_path);
        
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

        // Symlink with sudo
        $this->runSudo(['ln', '-sf', $availablePath, $enabledPath]);
        
        return $this->reload();
    }

    protected function getTemplate($domain, $path)
    {
        // Check if project has a port set, if so use Proxy, otherwise use PHP-FPM
        // This is a simplified check, usually we'd pass this in.
        return $this->reverseProxyTemplate($domain);
    }

    protected function reverseProxyTemplate($domain)
    {
        return "server {
    listen 80;
    server_name {$domain};

    location / {
        proxy_pass http://127.0.0.1:8000;
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
