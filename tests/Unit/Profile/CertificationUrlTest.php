<?php

declare(strict_types=1);

namespace Tests\Unit\Profile;

use App\Domains\Profile\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CertificationUrlTest extends TestCase
{
    use RefreshDatabase;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profile = Profile::create([
            'name' => 'Test User',
            'headline' => ['en' => 'Tester'],
            'bio' => ['en' => 'Bio.'],
        ]);
    }

    public function test_https_url_is_stored_unchanged(): void
    {
        // Arrange / Act
        $cert = $this->profile->certifications()->create([
            'name' => 'Cert A',
            'issuer' => 'Org A',
            'credential_url' => 'https://example.org/verify/123',
        ]);

        // Assert
        $this->assertSame('https://example.org/verify/123', $cert->fresh()->credential_url);
    }

    public function test_http_url_is_stored_unchanged(): void
    {
        // Arrange / Act
        $cert = $this->profile->certifications()->create([
            'name' => 'Cert B',
            'issuer' => 'Org B',
            'credential_url' => 'http://example.org/verify/456',
        ]);

        // Assert
        $this->assertSame('http://example.org/verify/456', $cert->fresh()->credential_url);
    }

    public function test_javascript_scheme_is_rejected_and_stored_as_null(): void
    {
        // Arrange / Act
        $cert = $this->profile->certifications()->create([
            'name' => 'Cert C',
            'issuer' => 'Org C',
            'credential_url' => 'javascript:alert(document.cookie)',
        ]);

        // Assert
        $this->assertNull($cert->fresh()->credential_url);
    }

    public function test_data_uri_scheme_is_rejected_and_stored_as_null(): void
    {
        // Arrange / Act
        $cert = $this->profile->certifications()->create([
            'name' => 'Cert D',
            'issuer' => 'Org D',
            'credential_url' => 'data:text/html,<script>alert(1)</script>',
        ]);

        // Assert
        $this->assertNull($cert->fresh()->credential_url);
    }

    public function test_null_credential_url_is_stored_as_null(): void
    {
        // Arrange / Act
        $cert = $this->profile->certifications()->create([
            'name' => 'Cert E',
            'issuer' => 'Org E',
            'credential_url' => null,
        ]);

        // Assert
        $this->assertNull($cert->fresh()->credential_url);
    }

    public function test_about_page_does_not_render_link_for_javascript_scheme(): void
    {
        // Arrange — bypass the mutator to simulate a legacy row with a bad URL
        $cert = $this->profile->certifications()->create([
            'name' => 'Cert F',
            'issuer' => 'Org F',
        ]);
        DB::table('certifications')
            ->where('id', $cert->id)
            ->update(['credential_url' => 'javascript:alert(1)']);

        // Act
        $html = $this->get(route('about'))->assertOk()->getContent();

        // Assert — the raw javascript: value must not appear in an href
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
    }
}
