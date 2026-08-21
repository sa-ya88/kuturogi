<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoPublicProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_visible_but_post_is_blocked_when_demo_mode_is_on(): void
    {
        config([
            'demo.enabled' => true,
            'demo.allow_registration' => false,
        ]);

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('demo.allowRegistration', false)
            );

        $this->post('/register', [
            'email' => 'someone@example.com',
        ])->assertRedirect(route('login'));
    }

    public function test_login_page_explains_demo_policy_when_enabled(): void
    {
        config([
            'demo.enabled' => true,
            'demo.allow_registration' => false,
            'demo.refresh_hours' => 4,
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('guest@example.com')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('demo.enabled', true)
                ->where('demo.allowRegistration', false)
                ->where('demo.refreshHours', 4)
            );
    }
}
