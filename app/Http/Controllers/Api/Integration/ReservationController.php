<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\RoomInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct(
        protected RoomInventoryService $inventoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Reservation::with(['plan', 'room'])->latest();

        if ($request->filled('since')) {
            $query->where('updated_at', '>=', $request->query('since'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json($query->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'room_id' => 'required|exists:rooms,id',
            'checkin_date' => 'required|date',
            'checkout_date' => 'required|date|after:checkin_date',
            'guest_count' => 'required|integer|min:1',
            'room_count' => 'required|integer|min:1',
            'adult_count' => 'nullable|integer|min:0',
            'child_count' => 'nullable|integer|min:0',
            'total_price' => 'required|integer|min:0',
            'guest_name' => 'required|string',
            'guest_email' => 'nullable|email',
            'guest_tel' => 'nullable|string',
            'payment_method' => 'nullable|in:local,credit',
            'payment_status' => 'nullable|in:unpaid,authorized,paid,refunded,failed',
        ]);

        $reservation = DB::transaction(function () use ($validated) {
            $this->inventoryService->adjustForStay(
                $validated['room_id'],
                $validated['checkin_date'],
                $validated['checkout_date'],
                $validated['room_count'],
                'decrement',
            );

            $paymentMethod = $validated['payment_method'] ?? 'local';

            return Reservation::create([
                'plan_id' => $validated['plan_id'],
                'room_id' => $validated['room_id'],
                'checkin_date' => $validated['checkin_date'],
                'checkout_date' => $validated['checkout_date'],
                'guest_count' => $validated['guest_count'],
                'room_count' => $validated['room_count'],
                'adult_count' => $validated['adult_count'] ?? $validated['guest_count'],
                'child_count' => $validated['child_count'] ?? 0,
                'total_price' => $validated['total_price'],
                'status' => 'confirmed',
                'payment_method' => $paymentMethod,
                'payment_status' => $validated['payment_status'] ?? Reservation::PAYMENT_UNPAID,
                'guest_name' => $validated['guest_name'],
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_tel' => $validated['guest_tel'] ?? null,
            ]);
        });

        return response()->json($reservation->load(['plan', 'room']), 201);
    }

    public function updatePayment(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => 'nullable|in:unpaid,authorized,paid,refunded,failed',
            'stripe_payment_intent_id' => 'nullable|string',
            'stripe_latest_charge_id' => 'nullable|string',
            'authorized_at' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'refunded_at' => 'nullable|date',
            'cancel_fee_amount' => 'nullable|integer|min:0',
            'stripe_cancel_fee_payment_intent_id' => 'nullable|string',
            'cancel_fee_uncollected' => 'nullable|boolean',
        ]);

        $reservation->update($validated);

        return response()->json([
            'status' => 'ok',
            'reservation' => $reservation->fresh()->load(['plan', 'room']),
        ]);
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        if ($reservation->status === 'cancelled') {
            return response()->json(['status' => 'already_cancelled', 'reservation' => $reservation]);
        }

        DB::transaction(function () use ($reservation) {
            $roomCount = max(1, (int) request()->input('room_count', 1));

            $this->inventoryService->adjustForStay(
                $reservation->room_id,
                $reservation->checkin_date->format('Y-m-d'),
                $reservation->checkout_date->format('Y-m-d'),
                $roomCount,
                'increment',
            );

            $reservation->update(['status' => 'cancelled']);
        });

        return response()->json([
            'status' => 'ok',
            'reservation' => $reservation->fresh()->load(['plan', 'room']),
        ]);
    }
}
