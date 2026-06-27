<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    // The /about route queries the database; provide a clean schema so it
    // renders its empty state (no Profile) rather than erroring.
    use RefreshDatabase;

    public function test_home_page_renders_with_tagline_and_accessibility_shell(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Practical Tools for Focus, Regulation, and Rest', false);
        $response->assertSee('Skip to main content');
        $response->assertSee('id="main-content"', false);
        $response->assertSee('nsPrefs.setTheme', false); // accessibility widget wired
    }

    public function test_plausible_is_disabled_in_local_and_testing(): void
    {
        $this->get('/')->assertDontSee('data-domain', false);
    }

    public function test_all_nav_destinations_respond(): void
    {
        foreach (['home', 'shop', 'blog', 'resources', 'about', 'play'] as $name) {
            $this->get(route($name))->assertOk();
        }
    }
}
