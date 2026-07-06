<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_x_content_type_options_header_is_set(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_referrer_policy_header_is_set(): void
    {
        $this->get('/')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_permissions_policy_header_is_set(): void
    {
        $this->get('/')->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
