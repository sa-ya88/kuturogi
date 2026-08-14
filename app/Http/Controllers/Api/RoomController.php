<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        // DBから全ての部屋情報を取得
        $rooms = Room::orderBy('sort_order')->orderBy('id')->get();
        
        // JSON形式でレスポンスを返す
        return response()->json($rooms);
    }
}
