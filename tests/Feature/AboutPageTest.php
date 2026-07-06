<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Profile\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_shows_profile_bio_and_certifications(): void
    {
        $profile = Profile::create([
            'name' => 'Alex Morgan',
            'headline' => ['en' => 'NeuroDivergence Coach & Advocate'],
            'bio' => ['en' => 'I build Tools for Focus and Regulation.'],
        ]);

        $profile->certifications()->create([
            'name' => 'Certified Autism Specialist',
            'issuer' => 'IBCCES',
            'issued_on' => '2023-03-01',
            'credential_url' => 'https://example.org/credentials/autism-specialist',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertSee('Alex Morgan');
        $response->assertSee('NeuroDivergence Coach &amp; Advocate', false);
        $response->assertSee('Tools for Focus and Regulation', false);
        $response->assertSee('Certified Autism Specialist');
        $response->assertSee('IBCCES');
        // Descriptive, verifiable credential link.
        $response->assertSee('https://example.org/credentials/autism-specialist', false);
        $response->assertSee('Verify the IBCCES Certified Autism Specialist credential', false);
    }

    public function test_certifications_render_in_sort_order(): void
    {
        $profile = Profile::create([
            'name' => 'Alex Morgan',
            'headline' => ['en' => 'Coach'],
            'bio' => ['en' => 'Bio.'],
        ]);
        $profile->certifications()->createMany([
            ['name' => 'Second Cert', 'issuer' => 'B Org', 'sort_order' => 2],
            ['name' => 'First Cert', 'issuer' => 'A Org', 'sort_order' => 1],
        ]);

        $html = $this->get(route('about'))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'Second Cert'),
            strpos($html, 'First Cert'),
            'Certifications should render ordered by sort_order.'
        );
    }

    public function test_about_page_renders_gracefully_without_a_profile(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertSee('About This Person');
    }
}
