<?php

namespace App\Services;

use App\Models\Subdomain;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Exception;

class NginxService
{
    public function generateConfig(Subdomain $subdomain)
    {
        // === TAMBAHAN OTOMATISASI SANDBOX ===
        // Pastikan PHP 8.4 memiliki izin tulis ke /etc/nginx sebelum melakukan apapun
        $this->unlockFpmSandbox();

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

        // Menulis template lewat Input Stream (stdin) untuk menghindari konflik karakter shell
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
        $project = $subdomain->project;
        $fullDomain = $subdomain->subdomain_name . '.inxdvi.com';
        $availablePath = "/etc/nginx/sites-available/{$fullDomain}";
        $enabledPath = "/etc/nginx/sites-enabled/{$fullDomain}";

        // 1. Pastikan izin folder Laravel sudah benar (Fix 500 error)
        $this->fixPermissions($project);

        // 2. Matikan config default agar tidak konflik
        $this->runSudo(['rm', '-f', '/etc/nginx/sites-enabled/default']);

        // 3. Pastikan Firewall mengizinkan HTTP dan HTTPS
        $this->allowHttpAndHttps();

        // 4. Symlink
        $process = new Process(['sudo', 'ln', '-sf', $availablePath, $enabledPath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception("Gagal membuat symlink Nginx: " . $process->getErrorOutput());
        }

        // 5. Reload
        if (!$this->reload()) {
            throw new Exception("Gagal me-reload Nginx. Cek sintaks konfigurasi Anda.");
        }

        return true;
    }

    protected function fixPermissions($project)
    {
        $path = $project->normalized_path ?? $project->directory_path;
        if (str_starts_with($path, '~')) {
            $path = str_replace('~', env('HOME', '/home/inxdvi'), $path);
        }

        // Jalankan perbaikan izin otomatis
        $this->runSudo(['chown', '-R', 'www-data:www-data', $path]);
        $this->runSudo(['find', $path, '-type', 'd', '-exec', 'chmod', '775', '{}', ';']);
        $this->runSudo(['find', $path, '-type', 'f', '-exec', 'chmod', '664', '{}', ';']);
        $this->runSudo(['chmod', '-R', '777', $path . '/storage', $path . '/bootstrap/cache']);
    }

    protected function allowHttpAndHttps()
    {
        $this->runSudo(['ufw', 'allow', '80']);
        $this->runSudo(['ufw', 'allow', '443']);
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
            $path = str_replace('~', env('HOME', '/home/inxdvi'), $path);
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
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
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

    /**
     * Membuka kunci (Sandbox) Systemd pada PHP-FPM agar bisa mengedit file Nginx di /etc.
     * Dikhususkan untuk standar InxOps (PHP 8.4).
     */
    protected function unlockFpmSandbox()
    {
        $overrideDir = '/etc/systemd/system/php8.4-fpm.service.d';
        $overrideFile = $overrideDir . '/override.conf';

        // Jika file override sudah ada, lewati proses ini (agar tidak restart FPM terus-menerus)
        if (File::exists($overrideFile)) {
            return true;
        }

        Log::info("Membuka kunci Sandbox Read-Only untuk PHP 8.4 FPM...");

        // 1. Buat foldernya
        $this->runSudo(['mkdir', '-p', $overrideDir]);

        // 2. Tulis file konfigurasi override
        $content = "[Service]\nProtectSystem=false\nReadWritePaths=/etc/nginx\n";
        $process = new Process(['sudo', 'tee', $overrideFile]);
        $process->setInput($content);
        $process->run();

        if ($process->isSuccessful()) {
            // 3. Reload systemd dan restart FPM
            $this->runSudo(['systemctl', 'daemon-reload']);

            // Catatan: Proses ini akan me-restart PHP-FPM. 
            // Karena command dijalankan di background, request saat ini mungkin akan sedikit ter-delay.
            $this->runSudo(['systemctl', 'restart', 'php8.4-fpm']);

            Log::info("Sandbox berhasil dibuka. PHP 8.4 FPM telah di-restart.");
            return true;
        }

        throw new Exception("Gagal membuka Sandbox PHP-FPM: " . $process->getErrorOutput());
    }
}