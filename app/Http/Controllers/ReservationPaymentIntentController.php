<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Room;
use App\Services\ReservationPricingService;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ReservationPaymentIntentController extends Controller
{
    public function __invoke(
        Request $request,
        ReservationPricingService $pricing,
        StripePaymentService $stripe,
    ): JsonResponse {
        if (! $stripe->isConfigured()) {
            return response()->json(['message' => 'クレジットカード決済は現在利用できません。'], 503);
        }

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'room_id' => 'required|exists:rooms,id',
            'checkin_date' => 'nullable|date',
            'check_in_date' => 'nullable|date',
            'checkout_date' => 'nullable|date',
            'check_out_date' => 'nullable|date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'required|integer|min:0',
            'room_count' => 'required|integer|min:1',
            'selected_option_ids' => 'nullable|array',
            'selected_option_ids.*' => 'integer',
        ]);

        $checkinDate = $validated['checkin_date'] ?? $validated['check_in_date'] ?? null;
        $checkoutDate = $validated['checkout_date'] ?? $validated['check_out_date'] ?? null;

        if (! $checkinDate || ! $checkoutDate) {
            return response()->json(['message' => 'チェックイン日とチェックアウト日を入力してください。'], 422);
        }

        $plan = Plan::findOrFail($validated['plan_id']);
        $room = Room::findOrFail($validated['room_id']);

        $quote = $pricing->calculate(
            $room,
            $plan,
            (string) $checkinDate,
            (string) $checkoutDate,
            (int) $validated['adult_count'],
            (int) $validated['child_count'],
            (int) $validated['room_count'],
            $validated['selected_option_ids'] ?? [],
        );

        try {
            $intent = $stripe->createAuthorizationIntent((int) $quote['total'], [
                'room_id' => (string) $room->id,
                'plan_id' => (string) $plan->id,
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'payment_intent_id' => $intent['id'],
            'client_secret' => $intent['client_secret'],
            'amount' => $intent['amount'],
        ]);
    }
}
