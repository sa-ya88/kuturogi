<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Inertia\Inertia;
use Inertia\Response;

class RoomPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Rooms/Index', [
            'rooms' => Room::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function show(Room $room): Response
    {
        abort_unless($room->is_active, 404);

        return Inertia::render('Rooms/Show', [
            'room' => $room->load('plans'),
        ]);
    }
}
