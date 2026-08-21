<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Room::with('plans')->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price_per_person' => 'required|integer|min:0',
            'stock_count' => 'sometimes|integer|min:0',
            'available_from' => 'sometimes|nullable|date',
            'available_to' => 'nullable|date|after_or_equal:available_from',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'details' => 'nullable|array',
            'details.facilities' => 'nullable|array',
            'details.facilities.*' => 'string|max:100',
            'details.internet' => 'nullable|string|max:255',
            'details.smoking' => 'nullable|string|max:255',
            'details.amenities' => 'nullable|array',
            'details.amenities.*' => 'string|max:100',
            'images' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'plan_ids' => 'nullable|array',
            'plan_ids.*' => 'integer|exists:plans,id',
        ]);

        $data = collect($validated)->except('plan_ids')->all();

        if (! array_key_exists('sort_order', $data)) {
            $data['sort_order'] = (Room::max('sort_order') ?? 0) + 1;
        }

        $room = Room::create($data);

        if (! empty($validated['plan_ids'])) {
            $room->plans()->sync($validated['plan_ids']);
        }

        return response()->json($room->fresh()->load('plans'), 201);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price_per_person' => 'sometimes|integer|min:0',
            'stock_count' => 'sometimes|integer|min:0',
            'available_from' => 'sometimes|nullable|date',
            'available_to' => 'nullable|date|after_or_equal:available_from',
            'description' => 'sometimes|string',
            'features' => 'sometimes|array',
            'details' => 'nullable|array',
            'details.facilities' => 'nullable|array',
            'details.facilities.*' => 'string|max:100',
            'details.internet' => 'nullable|string|max:255',
            'details.smoking' => 'nullable|string|max:255',
            'details.amenities' => 'nullable|array',
            'details.amenities.*' => 'string|max:100',
            'images' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'plan_ids' => 'nullable|array',
            'plan_ids.*' => 'integer|exists:plans,id',
        ]);

        $room->update(collect($validated)->except('plan_ids')->all());

        if (array_key_exists('plan_ids', $validated)) {
            $room->plans()->sync($validated['plan_ids'] ?? []);
        }

        return response()->json($room->fresh()->load('plans'));
    }

    public function destroy(Room $room, RoomImageStorageService $imageStorage): JsonResponse
    {
        if ($room->hasBlockingReservations()) {
            return response()->json([
                'message' => $room->deletionBlockedMessage(),
            ], 422);
        }

        $imageStorage->deleteRoomImages($room);
        $room->plans()->detach();
        $room->delete();

        return response()->json(['status' => 'ok']);
    }
}
