<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\RoomInventory;
use Carbon\Carbon;

class RoomInventorySeeder extends Seeder
{
    public function run()
    {
        // 全ての部屋を取得
        $rooms = Room::all();

        // 今日から365日分
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(365);

        foreach ($rooms as $room) {
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                RoomInventory::updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'date'    => $currentDate->format('Y-m-d'),
                    ],
                    [
                        // 初期在庫数は部屋の基本設定（あれば）か、固定値（例: 5室）を入れる
                        'remains' => 5, 
                    ]
                );
                $currentDate->addDay();
            }
        }
    }
}
