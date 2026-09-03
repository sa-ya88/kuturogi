<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = Room::orderBy('sort_order')->orderBy('id')->get();

        return response()->json($rooms);
    }
}
