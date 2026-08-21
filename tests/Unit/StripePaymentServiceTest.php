<?php

namespace Tests\Unit;

use App\Services\StripePaymentService;
use Tests\TestCase;

class StripePaymentServiceTest extends TestCase
{
    public function test_live_keys_are_not_configured(): void
    {
        config([
            'services.stripe.key' => 'pk_live_example',
            'services.stripe.secret' => 'sk_live_example',
        ]);

        $this->assertFalse((new StripePaymentService)->isConfigured());
    }

    public function test_placeholder_test_keys_are_not_configured(): void
    {
        config([
            'services.stripe.key' => 'pk_test_xxx',
            'services.stripe.secret' => 'sk_test_xxx',
        ]);

        $this->assertFalse((new StripePaymentService)->isConfigured());
    }

    public function test_real_test_keys_are_configured(): void
    {
        config([
            'services.stripe.key' => 'pk_test_abcdefghijklmnopqrstuvwxyz',
            'services.stripe.secret' => 'sk_test_abcdefghijklmnopqrstuvwxyz',
        ]);

        $this->assertTrue((new StripePaymentService)->isConfigured());
    }
}
