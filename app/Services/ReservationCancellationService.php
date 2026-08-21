<?php

namespace App\Services;

use App\Models\PricingCancelRule;
use App\Models\Reservation;
use App\Support\ReservationPaymentPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ReservationCancellationService
{
    public function __construct(
        protected RoomInventoryService $inventory,
        protected StripePaymentService $stripe,
        protected IntegrationWebhookDispatcher $webhooks,
    ) {}

    public function cancelByGuest(Reservation $reservation): Reservation
    {
        if ($reservation->status === 'cancelled') {
            throw new RuntimeException('この予約はすでにキャンセル済みです。');
        }

        if (! PricingCancelRule::allowsFreeCancellation($reservation->checkin_date)) {
            throw new RuntimeException('キャンセル料が発生するため、オンラインではキャンセルできません。お電話にてご連絡ください。');
        }

        $this->releasePaymentIfNeeded($reservation);

        $cancelled = DB::transaction(function () use ($reservation) {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                throw new RuntimeException('この予約はすでにキャンセル済みです。');
            }

            if (! PricingCancelRule::allowsFreeCancellation($locked->checkin_date)) {
                throw new RuntimeException('キャンセル料が発生するため、オンラインではキャンセルできません。お電話にてご連絡ください。');
            }

            $occupancy = app(SharedReservationOccupancyService::class);

            if ($occupancy->enabled()) {
                $occupancy->release($locked);
            } else {
                $this->inventory->adjustForStay(
                    $locked->room_id,
                    $locked->checkin_date->format('Y-m-d'),
                    $locked->checkout_date->format('Y-m-d'),
                    max(1, (int) $locked->room_count),
                    'increment',
                );
            }

            $locked->update([
                'status' => 'cancelled',
                'cancel_fee_amount' => 0,
                'cancel_fee_uncollected' => false,
            ]);

            return $locked->fresh(['plan', 'room', 'user']);
        });

        $this->dispatchCancelled($cancelled);

        return $cancelled;
    }

    protected function releasePaymentIfNeeded(Reservation $reservation): void
    {
        if ($reservation->payment_method !== 'credit' || blank($reservation->stripe_payment_intent_id)) {
            return;
        }

        if (! $this->stripe->isConfigured()) {
            Log::info('Guest cancel skipped Stripe release because Stripe is not configured.', [
                'reservation_id' => $reservation->id,
            ]);

            return;
        }

        try {
            if ($reservation->payment_status === Reservation::PAYMENT_AUTHORIZED) {
                $this->stripe->voidAuthorization($reservation->stripe_payment_intent_id);
                $reservation->update([
                    'payment_status' => Reservation::PAYMENT_UNPAID,
                    'authorized_at' => null,
                ]);
            } elseif ($reservation->payment_status === Reservation::PAYMENT_PAID) {
                $this->stripe->refundPaymentIntent($reservation->stripe_payment_intent_id);
                $reservation->update([
                    'payment_status' => Reservation::PAYMENT_REFUNDED,
                    'refunded_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Guest cancel payment release failed.', [
                'reservation_id' => $reservation->id,
                'exception' => $e::class,
            ]);

            throw new RuntimeException('決済の取消に失敗したため、キャンセルできませんでした。お電話にてご連絡ください。');
        }
    }

    protected function dispatchCancelled(Reservation $reservation): void
    {
        $this->webhooks->dispatch('reservation.cancelled', [
            'id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'room_id' => $reservation->room_id,
            'plan_id' => $reservation->plan_id,
            'checkin_date' => $reservation->checkin_date?->toDateString(),
            'checkout_date' => $reservation->checkout_date?->toDateString(),
            'guest_count' => $reservation->guest_count,
            'room_count' => $reservation->room_count,
            'adult_count' => $reservation->adult_count,
            'child_count' => $reservation->child_count,
            'total_price' => $reservation->total_price,
            'status' => $reservation->status,
            'guest_name' => $reservation->guest_name ?: $reservation->user?->name,
            'guest_name_kana' => $reservation->guest_name_kana,
            'guest_tel' => $reservation->guest_tel,
            'guest_email' => $reservation->guest_email ?: $reservation->user?->email,
            'selected_choices' => $reservation->selected_choices,
            'selected_option_fees' => $reservation->selected_option_fees,
            ...ReservationPaymentPayload::from($reservation),
        ]);
    }
}
