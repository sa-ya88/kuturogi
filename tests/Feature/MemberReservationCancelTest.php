<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PricingCancelRule;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberReservationCancelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-16'));

        PricingCancelRule::query()->create([
            'label' => '8日前まで',
            'days_before_from' => 365,
            'days_before_to' => 8,
            'charge_percent' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        PricingCancelRule::query()->create([
            'label' => '7日前〜当日',
            'days_before_from' => 7,
            'days_before_to' => 0,
            'charge_percent' => 100,
            'sort_order' => 2,
            'is_active' => true,
        ]);
    }

    public function test_member_reservation_index_shows_policy_and_cancel_flags(): void
    {
        [$user, $room, $plan] = $this->seedRoomAndPlan();

        $free = $this->makeReservation($user, $room, $plan, '2026-09-01', '2026-09-02');
        $charged = $this->makeReservation($user, $room, $plan, '2026-08-20', '2026-08-21');

        $this->actingAs($user)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reservations/Index')
                ->where('cancelPolicy.0', '8日前まで：宿泊料金の0%')
                ->has('reservations', 2)
                ->where('reservations', function ($items) use ($free, $charged) {
                    $byId = collect($items)->keyBy('id');

                    return (bool) data_get($byId->get($free->id), 'can_cancel_without_fee') === true
                        && (bool) data_get($byId->get($charged->id), 'can_cancel_without_fee') === false;
                })
            );
    }

    public function test_member_can_cancel_when_fee_is_zero(): void
    {
        [$user, $room, $plan] = $this->seedRoomAndPlan();
        $reservation = $this->makeReservation($user, $room, $plan, '2026-09-01', '2026-09-02', 2);

        $this->actingAs($user)
            ->post(route('reservations.cancel', $reservation))
            ->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'cancel_fee_amount' => 0,
        ]);
        $this->assertGreaterThan(
            2,
            (int) RoomInventory::query()->where('room_id', $room->id)->value('remains'),
        );
    }

    public function test_member_cannot_cancel_online_when_fee_applies(): void
    {
        [$user, $room, $plan] = $this->seedRoomAndPlan();
        $reservation = $this->makeReservation($user, $room, $plan, '2026-08-20', '2026-08-21');

        $this->actingAs($user)
            ->post(route('reservations.cancel', $reservation))
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHasErrors('cancel');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
        ]);
        $this->assertSame(
            3,
            (int) RoomInventory::query()->where('room_id', $room->id)->value('remains'),
        );
    }

    public function test_member_reservation_index_treats_unmatched_dates_as_free(): void
    {
        PricingCancelRule::query()->delete();
        PricingCancelRule::query()->create([
            'label' => '3日前〜前日',
            'days_before_from' => 3,
            'days_before_to' => 1,
            'charge_percent' => 20,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        [$user, $room, $plan] = $this->seedRoomAndPlan();
        $free = $this->makeReservation($user, $room, $plan, '2026-09-01', '2026-09-02');
        $charged = $this->makeReservation($user, $room, $plan, '2026-08-18', '2026-08-19');

        $this->actingAs($user)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reservations/Index')
                ->where('reservations', function ($items) use ($free, $charged) {
                    $byId = collect($items)->keyBy('id');

                    return (bool) data_get($byId->get($free->id), 'can_cancel_without_fee') === true
                        && (bool) data_get($byId->get($charged->id), 'can_cancel_without_fee') === false;
                })
            );
    }

    public function test_member_cannot_cancel_another_users_reservation(): void
    {
        [$user, $room, $plan] = $this->seedRoomAndPlan();
        $reservation = $this->makeReservation($user, $room, $plan, '2026-09-01', '2026-09-02');
        $other = User::factory()->create();

        $this->actingAs($other)
            ->post(route('reservations.cancel', $reservation))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Room, 2: Plan}
     */
    protected function seedRoomAndPlan(): array
    {
        $user = User::factory()->create();
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

        return [$user, $room, $plan];
    }

    protected function makeReservation(
        User $user,
        Room $room,
        Plan $plan,
        string $checkin,
        string $checkout,
        int $remainingAfterBooking = 3,
    ): Reservation {
        RoomInventory::query()->create([
            'room_id' => $room->id,
            'date' => $checkin,
            'remains' => $remainingAfterBooking,
        ]);

        return Reservation::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'room_id' => $room->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'guest_count' => 2,
            'room_count' => 2,
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 50000,
            'status' => 'confirmed',
            'payment_method' => 'local',
        ]);
    }
}
