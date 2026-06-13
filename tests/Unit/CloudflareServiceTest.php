<?php

namespace Tests\Unit;

use App\Services\CloudflareService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareServiceTest extends TestCase
{
    public function test_cloudflare_dns_registration_uses_cname_from_env()
    {
        // 1. Arrange: Set env variables for CNAME DNS registration
        config(['services.cloudflare' => []]); // Clear configuration caches if any
        putenv('CLOUDFLARE_API_TOKEN=fake-token');
        putenv('CLOUDFLARE_ZONE_ID=fake-zone-id');
        putenv('CLOUDFLARE_DNS_TYPE=CNAME');
        putenv('CLOUDFLARE_DNS_CONTENT=dashboard.inxdvi.com');

        Http::fake([
            'https://api.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        // 2. Act: Call registerDns
        $cloudflareService = new CloudflareService();
        $result = $cloudflareService->registerDns('sekolah');

        // 3. Assert: Verify the HTTP request payload and successful result
        $this->assertTrue($result);
        
        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);
            return $request->url() === 'https://api.cloudflare.com/client/v4/zones/fake-zone-id/dns_records' &&
                   $data['type'] === 'CNAME' &&
                   $data['name'] === 'sekolah.inxdvi.com' &&
                   $data['content'] === 'dashboard.inxdvi.com';
        });

        // Clean up env variables
        putenv('CLOUDFLARE_API_TOKEN');
        putenv('CLOUDFLARE_ZONE_ID');
        putenv('CLOUDFLARE_DNS_TYPE');
        putenv('CLOUDFLARE_DNS_CONTENT');
    }

    public function test_cloudflare_dns_registration_can_be_disabled()
    {
        // 1. Arrange: Disable Cloudflare DNS registration
        putenv('CLOUDFLARE_ENABLED=false');

        Http::fake();

        // 2. Act: Call registerDns
        $cloudflareService = new CloudflareService();
        $result = $cloudflareService->registerDns('sekolah');

        // 3. Assert: Verify true is returned and no HTTP requests are sent
        $this->assertTrue($result);
        Http::assertNothingSent();

        // Clean up env variables
        putenv('CLOUDFLARE_ENABLED');
    }
}
