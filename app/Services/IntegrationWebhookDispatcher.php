<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntegrationWebhookDispatcher
{
    public function dispatch(string $event, array $payload): void
    {
        if (config('integration.shared_database')) {
            Log::debug('Integration webhook skipped: shared database.', [
                'event' => $event,
            ]);

            return;
        }

        $url = config('integration.webhook.url');
        $secret = config('integration.webhook.secret');

        if (empty($url) || empty($secret)) {
            Log::debug('Integration webhook skipped: URL or secret not configured.', [
                'event' => $event,
            ]);

            return;
        }

        $body = json_encode([
            'event' => $event,
            'payload' => $payload,
            'sent_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $signature = hash_hmac('sha256', $body, $secret);

        $endpoint = match ($event) {
            'reservation.created' => '/api/webhooks/kuturogi/reservations',
            'reservation.cancelled' => '/api/webhooks/kuturogi/reservations/cancelled',
            'user.registered' => '/api/webhooks/kuturogi/users',
            default => null,
        };

        if ($endpoint === null) {
            Log::warning('Unknown integration webhook event.', ['event' => $event]);

            return;
        }

        try {
            $response = Http::withHeaders([
                'X-Kuturogi-Signature' => $signature,
                'Accept' => 'application/json',
            ])
                ->withBody($body, 'application/json')
                ->timeout(5)
                ->post(rtrim($url, '/').$endpoint);

            if ($response->failed()) {
                Log::warning('Integration webhook rejected.', [
                    'event' => $event,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Integration webhook failed.', [
                'event' => $event,
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
