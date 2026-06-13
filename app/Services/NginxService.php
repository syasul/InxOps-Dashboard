<?php

namespace App\Services;

use App\Models\Subdomain;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Exception;

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

        // Bersihkan file lama jika ada
        $this->runSudo(['rm', '-f', $availablePath]);
        $this->runSudo(['rm', '-f', $oldPath]);
        $this->runSudo(['rm', '-f', "/etc/nginx/sites-enabled/{$fullDomain}"]);
        $this->runSudo(['rm', '-f', "/etc/nginx/sites-enabled/{$shortName}"]);

        // Solusi Paling Tangguh: Memasukkan template lewat Input Stream (stdin)
        // Bypass semua masalah karakter khusus dan multi-line di terminal
        $process = new Process(['sudo', 'tee', $availablePath]);
        $process->setInput($template);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception("Gagal menulis file Nginx: " . $process->getErrorOutput());
        }

        return $availablePath;
    }

    public function enableConfig(Subdomain $subdomain)
    {
        $fullDomain = $subdomain->subdomain_name . '.inxdvi.com';
        $availablePath = "/etc/nginx/sites-available/{$fullDomain}";
        $enabledPath = "/etc/nginx/sites-enabled/{$fullDomain}";

        // Menggunakan array untuk menghindari masalah pembacaan shell
        $process = new Process(['sudo', 'ln', '-sf', $availablePath, $enabledPath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception("Gagal membuat symlink Nginx: " . $process->getErrorOutput());
        }

        // Reload Nginx
        if (!$this->reload()) {
            throw new Exception("Gagal me-reload Nginx. Cek sintaks konfigurasi Anda.");
        }

        return true;
    }

    public function removeConfig(Subdomain $subdomain)
    {
        $fullDomain = $subdomain->subdomain_name . '.inxdvi.com';
        $shortName = $subdomain->subdomain_name;

        $this->runSudo(['rm', '-f', "/etc/nginx/sites-available/{$fullDomain}"]);
        $this->runSudo(['rm', '-f', "/etc/nginx/sites-enabled/{$fullDomain}"]);
        $this->runSudo(['rm', '-f', "/etc/nginx/sites-available/{$shortName}"]);
        $this->runSudo(['rm', '-f', "/etc/nginx/sites-enabled/{$shortName}"]);

        $this->reload();
    }

    public function startApplication($project)
    {
        return true;
    }

    protected function getTemplate($domain, $project)
    {
        $path = $project->normalized_path ?? $project->directory_path;

        if (str_starts_with($path, '~')) {
            $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
            $path = str_replace('~', $home, $path);
        }

        $root = rtrim($path, '/') . '/public';

        return $this->fpmTemplate($domain, $root);
    }

    protected function fpmTemplate($domain, $root)
    {
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
        if ($this->runSudo(['systemctl', 'reload', 'nginx'])) {
            return true;
        }
        return $this->runSudo(['nginx', '-s', 'reload']);
    }
}