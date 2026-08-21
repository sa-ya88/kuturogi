<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\RoomInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RoomInventory::with('room')->orderBy('date');

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->integer('room_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->query('to'));
        }

        return response()->json($query->get());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.room_id' => 'required|integer|exists:rooms,id',
            'items.*.date' => 'required|date',
            'items.*.remains' => 'required|integer|min:0',
        ]);

        $updated = [];

        foreach ($validated['items'] as $item) {
            $inventory = RoomInventory::updateOrCreate(
                [
                    'room_id' => $item['room_id'],
                    'date' => $item['date'],
                ],
                ['remains' => $item['remains']]
            );

            $updated[] = $inventory->only(['room_id', 'date', 'remains']);
        }

        return response()->json([
            'status' => 'ok',
            'updated' => $updated,
        ]);
    }
}
