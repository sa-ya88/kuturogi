<?php

namespace Tests\Feature;

use App\Services\IntegrationWebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationWebhookDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_sends_signed_raw_json_including_unicode_names(): void
    {
        config([
            'integration.webhook.url' => 'http://admin.test',
            'integration.webhook.secret' => 'test-secret',
        ]);

        Http::fake([
            'http://admin.test/*' => Http::response(['status' => 'accepted'], 202),
        ]);

        app(IntegrationWebhookDispatcher::class)->dispatch('reservation.created', [
            'id' => 10,
            'guest_name' => 'ゲスト 太郎',
            'checkin_date' => '2026-09-01',
        ]);

        Http::assertSent(function ($request) {
            $signature = $request->header('X-Kuturogi-Signature')[0] ?? $request->header('X-Kuturogi-Signature');
            $body = $request->body();
            $decoded = json_decode($body, true);

            return str_contains($request->url(), '/api/webhooks/kuturogi/reservations')
                && ! str_contains($request->url(), 'cancelled')
                && hash_equals(hash_hmac('sha256', $body, 'test-secret'), (string) $signature)
                && ($decoded['payload']['guest_name'] ?? null) === 'ゲスト 太郎'
                && ($decoded['payload']['checkin_date'] ?? null) === '2026-09-01';
        });
    }

    public function test_dispatch_is_skipped_when_database_is_shared(): void
    {
        config([
            'integration.shared_database' => true,
            'integration.webhook.url' => 'http://admin.test',
            'integration.webhook.secret' => 'test-secret',
        ]);

        Http::fake();

        app(IntegrationWebhookDispatcher::class)->dispatch('reservation.created', [
            'id' => 10,
        ]);

        Http::assertNothingSent();
    }
}
