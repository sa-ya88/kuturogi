<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomImageStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomImageController extends Controller
{
    public function sync(Request $request, Room $room, RoomImageStorageService $storage): JsonResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'max:5'],
            'images.*' => ['required', 'file', 'mimes:webp,png,jpg,jpeg', 'max:10240'],
        ]);

        $paths = $storage->syncImages($room, $validated['images']);
        $room->update(['images' => $paths]);

        return response()->json([
            'images' => $paths,
        ]);
    }
}
