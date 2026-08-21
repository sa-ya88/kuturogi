<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Room;
use App\Models\RoomInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservationStayCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_stay_calendar_returns_inventory_and_price_per_night(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-16 03:00:00', 'Asia/Tokyo'));

        $room = Room::query()->create([
            'name' => 'テスト客室',
            'price_per_person' => 20000,
            'stock_count' => 5,
            'description' => '説明',
            'features' => [],
            'images' => [],
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'name' => 'テストプラン',
            'price_per_person' => 11900,
            'description' => '説明',
            'images' => [],
        ]);

        $room->plans()->attach($plan->id);

        RoomInventory::query()->create([
            'room_id' => $room->id,
            'date' => '2026-11-02',
            'remains' => 3,
        ]);
        RoomInventory::query()->create([
            'room_id' => $room->id,
            'date' => '2026-11-03',
            'remains' => 0,
        ]);

        $response = $this->getJson(route('reservations.stay-calendar', [
            'plan_id' => $plan->id,
            'room_id' => $room->id,
            'year' => 2026,
            'month' => 11,
            'room_count' => 1,
        ]));

        $response->assertOk()
            ->assertJsonPath('year', 2026)
            ->assertJsonPath('month', 11)
            ->assertJsonPath('low_stock_threshold', 10);

        $days = collect($response->json('days'))->keyBy('date');

        $this->assertSame([
            'date' => '2026-11-02',
            'in_month' => true,
            'available' => true,
            'remains' => 3,
            'price' => 31900,
        ], $days['2026-11-02']);

        $this->assertSame([
            'date' => '2026-11-03',
            'in_month' => true,
            'available' => false,
            'remains' => 0,
            'price' => null,
        ], $days['2026-11-03']);
    }

    public function test_stay_calendar_price_includes_adults_and_children(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-16 03:00:00', 'Asia/Tokyo'));

        $room = Room::query()->create([
            'name' => 'テスト客室',
            'price_per_person' => 20000,
            'stock_count' => 5,
            'description' => '説明',
            'features' => [],
            'images' => [],
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'name' => 'テストプラン',
            'price_per_person' => 11900,
            'description' => '説明',
            'images' => [],
        ]);

        $room->plans()->attach($plan->id);

        RoomInventory::query()->create([
            'room_id' => $room->id,
            'date' => '2026-11-02',
            'remains' => 3,
        ]);

        $response = $this->getJson(route('reservations.stay-calendar', [
            'plan_id' => $plan->id,
            'room_id' => $room->id,
            'year' => 2026,
            'month' => 11,
            'room_count' => 1,
            'adult_count' => 2,
            'child_count' => 1,
        ]));

        $days = collect($response->json('days'))->keyBy('date');

        $this->assertSame(86130, $days['2026-11-02']['price']);
    }

    public function test_stay_calendar_reads_sqlite_datetime_inventory_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-16 03:00:00', 'Asia/Tokyo'));

        $room = Room::query()->create([
            'name' => 'テスト客室',
            'price_per_person' => 20000,
            'stock_count' => 5,
            'description' => '説明',
            'features' => [],
            'images' => [],
            'is_active' => true,
        ]);

        $plan = Plan::query()->create([
            'name' => 'テストプラン',
            'price_per_person' => 11900,
            'description' => '説明',
            'images' => [],
        ]);

        $room->plans()->attach($plan->id);

        \Illuminate\Support\Facades\DB::table('room_inventories')->insert([
            [
                'room_id' => $room->id,
                'date' => '2026-11-02 00:00:00',
                'remains' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id' => $room->id,
                'date' => '2026-11-03 00:00:00',
                'remains' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $days = collect($this->getJson(route('reservations.stay-calendar', [
            'plan_id' => $plan->id,
            'room_id' => $room->id,
            'year' => 2026,
            'month' => 11,
            'room_count' => 1,
        ]))->json('days'))->keyBy('date');

        $this->assertSame(4, $days['2026-11-02']['remains']);
        $this->assertTrue($days['2026-11-02']['available']);
        $this->assertSame(0, $days['2026-11-03']['remains']);
        $this->assertFalse($days['2026-11-03']['available']);
    }

    public function test_stay_calendar_rejects_unrelated_plan_and_room(): void
    {
        $room = Room::query()->create([
            'name' => '客室A',
            'price_per_person' => 10000,
            'description' => '説明',
            'features' => [],
            'images' => [],
        ]);
        $otherRoom = Room::query()->create([
            'name' => '客室B',
            'price_per_person' => 10000,
            'description' => '説明',
            'features' => [],
            'images' => [],
        ]);
        $plan = Plan::query()->create([
            'name' => 'プラン',
            'price_per_person' => 1000,
            'description' => '説明',
            'images' => [],
        ]);
        $otherRoom->plans()->attach($plan->id);

        $this->getJson(route('reservations.stay-calendar', [
            'plan_id' => $plan->id,
            'room_id' => $room->id,
            'year' => 2026,
            'month' => 11,
        ]))->assertStatus(422);
    }
}
