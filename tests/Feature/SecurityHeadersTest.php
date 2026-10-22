<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_send_baseline_security_headers(): void
    {
        $response = $this->get('/incidents');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }

    public function test_csp_nonce_in_the_header_matches_the_one_given_to_the_page(): void
    {
        $response = $this->get('/incidents');

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);

        preg_match("/script-src 'self' 'nonce-([A-Za-z0-9]+)'/", $policy, $matches);
        $nonce = $matches[1] ?? null;
        $this->assertNotNull($nonce, 'script-src must carry a nonce');

        $response->assertSee('<meta property="csp-nonce" nonce="'.$nonce.'">', escape: false);
    }

    public function test_report_mode_sends_the_report_only_header_instead(): void
    {
        config(['security.csp' => 'report']);

        $response = $this->get('/incidents');

        $response->assertHeaderMissing('Content-Security-Policy');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_not_found_pages_carry_the_headers_too(): void
    {
        $this->get('/does-not-exist')
            ->assertNotFound()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy');
    }
}
