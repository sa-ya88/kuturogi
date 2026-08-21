<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\DemoGuestUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuestLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_authenticates_demo_user(): void
    {
        config(['demo.enabled' => true]);
        Http::fake();

        $response = $this->post(route('guest.login'));

        $this->assertAuthenticated();
        $response->assertRedirect(route('top', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => config('demo.guest.email'),
        ]);
    }

    public function test_guest_login_is_hidden_when_demo_mode_is_disabled(): void
    {
        config(['demo.enabled' => false]);

        $this->post(route('guest.login'))->assertNotFound();
        $this->assertGuest();
    }

    public function test_existing_guest_user_can_log_in_again(): void
    {
        config(['demo.enabled' => true]);
        Http::fake();

        User::factory()->create([
            'email' => config('demo.guest.email'),
            'name' => '既存ゲスト',
        ]);

        $this->post(route('guest.login'));

        $this->assertAuthenticated();
        $this->assertSame(1, User::query()->where('email', config('demo.guest.email'))->count());
    }

    public function test_guest_login_notifies_admin_crm_with_matching_signature(): void
    {
        config([
            'demo.enabled' => true,
            'integration.webhook.url' => 'http://admin.test',
            'integration.webhook.secret' => 'test-secret',
        ]);

        Http::fake([
            'http://admin.test/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $this->post(route('guest.login'))->assertRedirect();

        $user = DemoGuestUser::firstOrCreate();

        Http::assertSent(function ($request) use ($user) {
            $signature = $request->header('X-Kuturogi-Signature')[0] ?? $request->header('X-Kuturogi-Signature');
            $body = $request->body();
            $decoded = json_decode($body, true);

            return str_contains($request->url(), '/api/webhooks/kuturogi/users')
                && hash_equals(hash_hmac('sha256', $body, 'test-secret'), (string) $signature)
                && ($decoded['payload']['id'] ?? null) === $user->id
                && ($decoded['payload']['name'] ?? null) === 'ゲスト 太郎'
                && ($decoded['payload']['email'] ?? null) === config('demo.guest.email');
        });
    }
}
