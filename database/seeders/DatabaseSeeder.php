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

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DemoGuestUser::ensureAndSyncToAdmin();

        // 2. 各マスターデータの作成（RoomInventoryは最後にするのでここでは外す）
        $this->call([
            NewsSeeder::class,
            RoomSeeder::class,
            PlanSeeder::class,
        ]);

        // 全ての部屋とプランのIDを取得
        $rooms = Room::all();
        $planIds = Plan::pluck('id')->toArray();

        // 4. 全部屋に全プランを紐付ける
        foreach ($rooms as $room) {
            // attachだと重複エラーが出る可能性があるので sync を使用
            $room->plans()->sync($planIds);
        }

        // 5. 最後に在庫データを作成（部屋が存在することが前提）
        $this->call(RoomInventorySeeder::class);

    }
}
