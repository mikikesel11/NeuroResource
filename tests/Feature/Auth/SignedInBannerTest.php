<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignedInBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_a_login_link_and_no_logout(): void
    {
        $response = $this->get('/');

        $response->assertSee('Log in');
        $response->assertDontSee('Log out');
        $response->assertDontSee('signed in as', false);
    }

    public function test_authenticated_users_have_a_persistent_logout_but_no_banner_by_default(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertSee('Log out');                       // persistent control
        $response->assertDontSee('signed in as', false); // banner is not permanent
    }

    public function test_temporary_banner_shows_after_login(): void
    {
        $user = User::factory()->create(['name' => 'Sam Rivers']);

        // The login flow flashes "justLoggedIn"; the next page shows the banner.
        // (It's a true flash in LoginForm, so it only appears on that one page.)
        $response = $this->actingAs($user)
            ->withSession(['justLoggedIn' => true])
            ->get('/');

        $response->assertSee('signed in as', false);
        $response->assertSee('Sam Rivers');
        $response->assertSee('Dismiss');
        $response->assertSee('data-auto-dismiss', false); // auto-dismiss wired
    }

    public function test_login_link_carries_the_current_page_as_redirect(): void
    {
        $this->get('/shop')->assertSee('login?redirect=%2Fshop', false);
    }

    public function test_logout_returns_to_home_and_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
