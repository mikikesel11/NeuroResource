<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function login(?string $redirect = null)
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        if ($redirect !== null) {
            $component->set('redirect', $redirect);
        }

        return $component->call('login');
    }

    public function test_returns_to_the_originating_page(): void
    {
        $this->login('/shop')
            ->assertHasNoErrors()
            ->assertRedirect('/shop');
    }

    public function test_defaults_to_home_not_dashboard(): void
    {
        $this->login()->assertRedirect(route('home', absolute: false));
    }

    public function test_ignores_external_redirects(): void
    {
        $this->login('https://evil.example.com')->assertRedirect(route('home', absolute: false));
        $this->login('//evil.example.com')->assertRedirect(route('home', absolute: false));
    }

    public function test_ignores_auth_pages_to_avoid_loops(): void
    {
        $this->login('/login')->assertRedirect(route('home', absolute: false));
        $this->login('/register')->assertRedirect(route('home', absolute: false));
    }
}
