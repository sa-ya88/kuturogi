<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlanListInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_list_uses_datetime_inventory_dates_for_stay_search(): void
    {
        $openRoom = $this->makeRoom('空き客室');
        $fullRoom = $this->makeRoom('満室客室');
        $plan = Plan::query()->create([
            'name' => 'テストプラン',
            'price_per_person' => 10000,
            'description' => '説明',
            'images' => [],
        ]);
        $openRoom->plans()->attach($plan->id);
        $fullRoom->plans()->attach($plan->id);

        DB::table('room_inventories')->insert([
            [
                'room_id' => $openRoom->id,
                'date' => '2026-09-01 00:00:00',
                'remains' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id' => $fullRoom->id,
                'date' => '2026-09-01 00:00:00',
                'remains' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('reservations.create', [
            'checkin' => '2026-09-01',
            'checkout' => '2026-09-02',
            'room_count' => 1,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reservations/Create')
                ->where('rooms', function (array $rooms) use ($openRoom, $fullRoom) {
                    $byId = collect($rooms)->keyBy('id');

                    return (int) $byId[$openRoom->id]['current_inventory'] === 2
                        && (int) $byId[$fullRoom->id]['current_inventory'] === 0;
                })
            );
    }

    protected function makeRoom(string $name): Room
    {
        return Room::query()->create([
            'name' => $name,
            'price_per_person' => 20000,
            'stock_count' => 2,
            'description' => '説明',
            'features' => [],
            'images' => [],
            'is_active' => true,
        ]);
    }
}
