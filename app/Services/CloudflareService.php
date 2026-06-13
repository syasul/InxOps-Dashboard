<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected $apiToken;
    protected $zoneId;
    protected $baseUrl = 'https://api.cloudflare.com/client/v4';

    public function __construct()
    {
        $this->apiToken = env('CLOUDFLARE_API_TOKEN');
        $this->zoneId = env('CLOUDFLARE_ZONE_ID');
    }

    public function registerDns($subdomainName, $content = null)
    {
        if (!$this->apiToken || !$this->zoneId) {
            Log::warning('Cloudflare API Token or Zone ID missing in .env');
            return false;
        }

        $type = env('CLOUDFLARE_DNS_TYPE', 'A');
        
        if ($type === 'CNAME') {
            $content = $content ?: env('CLOUDFLARE_DNS_CONTENT', 'dashboard.inxdvi.com');
        } else {
            $content = $content ?: $this->getServerIp();
        }

        $fullDomain = $subdomainName . '.inxdvi.com';

        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/zones/{$this->zoneId}/dns_records", [
                'type' => $type,
                'name' => $fullDomain,
                'content' => $content,
                'ttl' => 1, // Auto
                'proxied' => true,
            ]);

        if ($response->successful()) {
            Log::info("DNS registered for {$fullDomain} (Type: {$type}) pointing to {$content}");
            return true;
        }

        Log::error("Cloudflare DNS error: " . $response->body());
        return false;
    }

    protected function getServerIp()
    {
        try {
            return trim(file_get_contents('https://api.ipify.org'));
        } catch (\Exception $e) {
            return '127.0.0.1'; // Fallback
        }
    }
}
