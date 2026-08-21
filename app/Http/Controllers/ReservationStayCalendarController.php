<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Room;
use App\Services\StayCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationStayCalendarController extends Controller
{
    public function __invoke(Request $request, StayCalendarService $calendar): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'room_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'adult_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'child_count' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $room = Room::findOrFail($validated['room_id']);
        $plan = Plan::findOrFail($validated['plan_id']);

        if (! $room->plans()->where('plans.id', $plan->id)->exists()) {
            return response()->json([
                'message' => '指定のプランはこの客室ではご利用いただけません。',
            ], 422);
        }

        return response()->json(
            $calendar->forMonth(
                $room,
                $plan,
                (int) $validated['year'],
                (int) $validated['month'],
                (int) ($validated['room_count'] ?? 1),
                (int) ($validated['adult_count'] ?? 1),
                (int) ($validated['child_count'] ?? 0),
            )
        );
    }
}
