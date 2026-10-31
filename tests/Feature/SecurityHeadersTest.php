<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_public_pages_send_baseline_security_headers(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader(
                'Permissions-Policy',
                'camera=(self), geolocation=(self), microphone=()'
            );
    }
}
