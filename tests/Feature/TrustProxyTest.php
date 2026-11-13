<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

class TrustProxyTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    public function test_https_proxy_header_is_honoured_when_configured(): void
    {
        TrustProxies::at('*');
        $html = $this->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'contoh.ngrok-free.app', 'X-Forwarded-Port' => '443'])
            ->get('/masuk')->getContent();
        $this->assertStringContainsString('https://contoh.ngrok-free.app', $html);
    }
}
