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
        $template = $this->getTemplate($fullDomain, $project->directory_path);
        
        $availablePath = "/etc/nginx/sites-available/{$fullDomain}";
        
        // Write locally first then move with sudo for safety
        $tempPath = storage_path("nginx/{$fullDomain}.conf");
        File::ensureDirectoryExists(storage_path("nginx"));
        File::put($tempPath, $template);

        // Move to available sites
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
        return "server {
    listen 80;
    server_name {$domain};
    root {$path}/public;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-Content-Type-Options \"nosniff\";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
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
        return $this->runSudo(['nginx', '-s', 'reload']);
    }
}
