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
            'available_from' => 'sometimes|date',
            'available_to' => 'sometimes|date|after_or_equal:available_from',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
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
            'available_from' => 'sometimes|date',
            'available_to' => 'sometimes|date',
            'description' => 'sometimes|string',
            'features' => 'sometimes|array',
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
        if ($room->reservations()->exists()) {
            return response()->json([
                'message' => '予約が存在するため削除できません。',
            ], 422);
        }

        $imageStorage->deleteRoomImages($room);
        $room->plans()->detach();
        $room->delete();

        return response()->json(['status' => 'ok']);
    }
}
