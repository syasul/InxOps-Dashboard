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
        $template = $this->getTemplate($subdomain->subdomain_name, $project->directory_path, $project->port);
        
        $configPath = "/etc/nginx/sites-available/{$subdomain->subdomain_name}.conf";
        
        // Note: In real life, this needs root permissions. 
        // We'll write to a temp directory first or assume the app has write access (risky).
        // Best approach: write to storage and then symlink via sudo script.
        
        $storagePath = storage_path("nginx/{$subdomain->subdomain_name}.conf");
        File::ensureDirectoryExists(storage_path("nginx"));
        File::put($storagePath, $template);
        
        return $storagePath;
    }

    protected function getTemplate($domain, $path, $port = null)
    {
        if ($port) {
            // Reverse Proxy Template
            return "
server {
    listen 80;
    server_name {$domain};

    location / {
        proxy_pass http://127.0.0.1:{$port};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}";
        }

        // PHP-FPM Template
        return "
server {
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
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}";
    }

    public function reload()
    {
        $process = new Process(['sudo', 'nginx', '-s', 'reload']);
        $process->run();
        return $process->isSuccessful();
    }
}
