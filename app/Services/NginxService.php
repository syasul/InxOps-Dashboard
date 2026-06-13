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

        // 3. Kita tidak perlu lagi menjalankan php artisan serve!
        // Aplikasi akan otomatis ditangani secara profesional oleh Nginx dan PHP-FPM.
        return true;
    }

    public function startApplication($project)
    {
        // FUNGSI INI DIBIARKAN KOSONG NAMUN TETAP ADA (RETURN TRUE)
        // Agar script lain di InxOps Dashboard yang memanggil fungsi ini tidak error.
        // Mesin Nginx FPM tidak butuh proses port manual.
        return true;
    }

    protected function getTemplate($domain, $project)
    {
        // Mengambil path dasar project
        $path = $project->normalized_path ?? $project->directory_path;

        // Expand tilde (~) ke absolute home directory server Anda
        if (str_starts_with($path, '~')) {
            $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
            $path = str_replace('~', $home, $path);
        }

        // Tentukan folder "public" tempat file index.php Laravel berada
        $root = rtrim($path, '/') . '/public';

        return $this->fpmTemplate($domain, $root);
    }

    protected function fpmTemplate($domain, $root)
    {
        // Template standar Nginx Laravel (Bebas Error 502/522, mendukung banyak pengunjung)
        return "server {
    listen 80;
    server_name {$domain};
    root {$root};

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
        // Try systemctl first, fallback to nginx -s reload
        if ($this->runSudo(['systemctl', 'reload', 'nginx'])) {
            return true;
        }
        return $this->runSudo(['nginx', '-s', 'reload']);
    }
}