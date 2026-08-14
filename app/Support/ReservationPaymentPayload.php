<?php

namespace App\Support;

use App\Models\Reservation;

class ReservationPaymentPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Reservation $reservation): array
    {
        return [
            'payment_method' => $reservation->payment_method,
            'payment_status' => $reservation->payment_status,
            'stripe_payment_intent_id' => $reservation->stripe_payment_intent_id,
            'stripe_latest_charge_id' => $reservation->stripe_latest_charge_id,
            'authorized_at' => optional($reservation->authorized_at)?->toIso8601String(),
            'paid_at' => optional($reservation->paid_at)?->toIso8601String(),
            'refunded_at' => optional($reservation->refunded_at)?->toIso8601String(),
            'cancel_fee_amount' => $reservation->cancel_fee_amount,
            'stripe_cancel_fee_payment_intent_id' => $reservation->stripe_cancel_fee_payment_intent_id,
            'cancel_fee_uncollected' => (bool) $reservation->cancel_fee_uncollected,
        ];
    }
}
