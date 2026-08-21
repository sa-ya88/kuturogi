<?php

namespace App\Services;

use App\Models\Reservation;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripePaymentService
{
    protected ?StripeClient $stripe = null;

    public function isConfigured(): bool
    {
        $key = (string) config('services.stripe.key');
        $secret = (string) config('services.stripe.secret');

        return str_starts_with($key, 'pk_test_')
            && str_starts_with($secret, 'sk_test_')
            && ! str_contains($key, 'xxx')
            && ! str_contains($secret, 'xxx');
    }

    protected function client(): StripeClient
    {
        if ($this->stripe) {
            return $this->stripe;
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe はテストモード（pk_test_ / sk_test_）のキーのみ利用できます。');
        }

        return $this->stripe = new StripeClient((string) config('services.stripe.secret'));
    }

    /**
     * @return array{id: string, client_secret: string, amount: int}
     */
    public function createAuthorizationIntent(int $amountYen, array $metadata = []): array
    {
        if ($amountYen < 50) {
            throw new RuntimeException('決済金額が不正です。');
        }

        $intent = $this->client()->paymentIntents->create([
            'amount' => $amountYen,
            'currency' => 'jpy',
            'capture_method' => 'manual',
            // カード与信のみ（redirect系PMとの setup_future_usage 衝突を避ける）
            'payment_method_types' => ['card'],
            'setup_future_usage' => 'off_session',
            'metadata' => $metadata,
        ]);

        return [
            'id' => $intent->id,
            'client_secret' => (string) $intent->client_secret,
            'amount' => (int) $intent->amount,
        ];
    }

    public function retrieveIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId, [
            'expand' => ['latest_charge', 'payment_method'],
        ]);
    }

    public function assertAuthorizedForAmount(string $paymentIntentId, int $expectedAmountYen): PaymentIntent
    {
        $intent = $this->retrieveIntent($paymentIntentId);

        if ((int) $intent->amount !== $expectedAmountYen) {
            throw new RuntimeException('決済金額が予約金額と一致しません。');
        }

        if (! in_array($intent->status, ['requires_capture', 'succeeded'], true)) {
            throw new RuntimeException('カード与信が完了していません（status: '.$intent->status.'）。');
        }

        return $intent;
    }

    public function capture(string $paymentIntentId): PaymentIntent
    {
        $intent = $this->retrieveIntent($paymentIntentId);

        if ($intent->status === 'succeeded') {
            return $intent;
        }

        if ($intent->status !== 'requires_capture') {
            throw new RuntimeException('キャプチャできない決済状態です（'.$intent->status.'）。');
        }

        return $this->client()->paymentIntents->capture($paymentIntentId);
    }

    public function voidAuthorization(string $paymentIntentId): PaymentIntent
    {
        $intent = $this->retrieveIntent($paymentIntentId);

        if (in_array($intent->status, ['canceled', 'cancelled'], true)) {
            return $intent;
        }

        if ($intent->status === 'requires_capture') {
            return $this->client()->paymentIntents->cancel($paymentIntentId);
        }

        throw new RuntimeException('与信取消できない決済状態です（'.$intent->status.'）。');
    }

    public function refundPaymentIntent(string $paymentIntentId): \Stripe\Refund
    {
        return $this->client()->refunds->create([
            'payment_intent' => $paymentIntentId,
        ]);
    }

    public function chargeCancelFee(string $originalPaymentIntentId, int $feeAmountYen, array $metadata = []): ?PaymentIntent
    {
        if ($feeAmountYen <= 0) {
            return null;
        }

        $original = $this->retrieveIntent($originalPaymentIntentId);
        $paymentMethodId = is_string($original->payment_method)
            ? $original->payment_method
            : ($original->payment_method->id ?? null);
        $customerId = is_string($original->customer)
            ? $original->customer
            : ($original->customer->id ?? null);

        if (! $paymentMethodId) {
            return null;
        }

        try {
            $params = [
                'amount' => $feeAmountYen,
                'currency' => 'jpy',
                'payment_method' => $paymentMethodId,
                'confirm' => true,
                'off_session' => true,
                'metadata' => $metadata,
            ];

            if ($customerId) {
                $params['customer'] = $customerId;
            }

            return $this->client()->paymentIntents->create($params);
        } catch (ApiErrorException) {
            return null;
        }
    }

    public static function chargeIdFromIntent(PaymentIntent $intent): ?string
    {
        if (is_string($intent->latest_charge)) {
            return $intent->latest_charge;
        }

        return $intent->latest_charge->id ?? null;
    }
}
