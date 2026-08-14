<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PricingCancelRule;
use App\Models\PricingOptionFee;
use App\Models\Room;
use App\Services\ReservationPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationQuoteController extends Controller
{
    public function __invoke(Request $request, ReservationPricingService $pricing): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'room_id' => 'required|exists:rooms,id',
            'checkin_date' => 'required|date',
            'checkout_date' => 'required|date|after:checkin_date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'required|integer|min:0',
            'room_count' => 'required|integer|min:1',
            'selected_option_ids' => 'nullable|array',
            'selected_option_ids.*' => 'integer',
        ]);

        $quote = $pricing->calculate(
            Room::findOrFail($validated['room_id']),
            Plan::findOrFail($validated['plan_id']),
            $validated['checkin_date'],
            $validated['checkout_date'],
            (int) $validated['adult_count'],
            (int) $validated['child_count'],
            (int) $validated['room_count'],
            $validated['selected_option_ids'] ?? [],
        );

        return response()->json($quote);
    }

    public function meta(): JsonResponse
    {
        return response()->json([
            'option_fees' => PricingOptionFee::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'price', 'description']),
            'cancel_policy' => PricingCancelRule::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (PricingCancelRule $rule): string => $rule->displayText())
                ->values(),
        ]);
    }
}
