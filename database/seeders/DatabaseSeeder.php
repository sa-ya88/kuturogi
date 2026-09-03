<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Plan;
use App\Support\DemoGuestUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DemoGuestUser::ensureAndSyncToAdmin();

        $this->call([
            NewsSeeder::class,
            RoomSeeder::class,
            PlanSeeder::class,
        ]);

        $rooms = Room::all();
        $planIds = Plan::pluck('id')->toArray();

        foreach ($rooms as $room) {
            $room->plans()->sync($planIds);
        }

        $this->call(RoomInventorySeeder::class);
    }
}
