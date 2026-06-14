<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CloudflareService
{
    /**
     * Otomatis mendaftarkan subdomain ke Cloudflare Tunnel via CLI cloudflared.
     *
     * @param string $subdomainName
     * @param string|null $content
     * @return bool
     */
    public function registerDns($subdomainName, $content = null)
    {
        if (!filter_var(env('CLOUDFLARE_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
            Log::info('Cloudflare DNS registration is disabled via CLOUDFLARE_ENABLED');
            return true;
        }

        $fullDomain = $subdomainName . '.inxdvi.com';
        $tunnelName = env('CLOUDFLARE_TUNNEL_NAME', 'inxdvi.tunnel');

        Log::info("Menambahkan route DNS ke Cloudflare Tunnel: {$fullDomain} -> {$tunnelName}");

        // Mengeksekusi perintah CLI cloudflared untuk routing DNS otomatis
        $process = new Process(['cloudflared', 'tunnel', 'route', 'dns', $tunnelName, $fullDomain]);
        $process->run();

        if ($process->isSuccessful()) {
            Log::info("Sukses! DNS {$fullDomain} berhasil di-route ke tunnel.");
            return true;
        }

        $error = $process->getErrorOutput();

        // Self-healing: Jika error karena record sudah ada, anggap sukses agar proses deploy tetap jalan
        if (str_contains($error, 'already exists') || str_contains($error, '1003')) {
            Log::info("DNS {$fullDomain} sudah terdaftar sebelumnya di Cloudflare. Melanjutkan proses...");
            return true;
        }

        Log::error("Gagal melakukan routing DNS Cloudflare: " . $error);
        return false;
    }
}