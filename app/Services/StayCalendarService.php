<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Room;
use App\Models\RoomInventory;
use Illuminate\Support\Carbon;

class StayCalendarService
{
    public const LOW_STOCK_THRESHOLD = 10;

    public function __construct(
        protected ReservationPricingService $pricing,
    ) {}

    /**
     * @return array{
     *   year: int,
     *   month: int,
     *   low_stock_threshold: int,
     *   days: list<array{date: string, in_month: bool, available: bool, remains: int, price: int|null}>
     * }
     */
    public function forMonth(
        Room $room,
        Plan $plan,
        int $year,
        int $month,
        int $roomCount,
        int $adultCount = 1,
        int $childCount = 0,
    ): array {
        $roomCount = max(1, $roomCount);
        $adultCount = max(1, $adultCount);
        $childCount = max(0, $childCount);
        $monthStart = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Tokyo')->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SATURDAY);
        $today = Carbon::now('Asia/Tokyo')->startOfDay();

        $priceFrom = $gridStart->toDateString();
        $priceTo = $gridEnd->copy()->addDay()->toDateString();
        $quote = $this->pricing->calculate(
            $room,
            $plan,
            $priceFrom,
            $priceTo,
            $adultCount,
            $childCount,
            $roomCount,
        );
        $priceByDate = collect($quote['nights'])->keyBy('date');

        $inventories = RoomInventory::query()
            ->where('room_id', $room->id)
            ->onDateRange($gridStart->toDateString(), $gridEnd->toDateString())
            ->get()
            ->mapWithKeys(fn (RoomInventory $inventory) => [
                $inventory->dateString() => (int) $inventory->remains,
            ]);

        $availableFrom = $room->available_from?->toDateString();
        $availableTo = $room->available_to?->toDateString();

        $days = [];
        for ($date = $gridStart->copy(); $date->lte($gridEnd); $date->addDay()) {
            $dateStr = $date->toDateString();
            $inMonth = $date->month === $month;
            $remains = $inventories->has($dateStr) ? (int) $inventories->get($dateStr) : 0;
            $inSalePeriod = ($availableFrom === null || $dateStr >= $availableFrom)
                && ($availableTo === null || $dateStr <= $availableTo);
            $available = $inSalePeriod
                && $date->gte($today)
                && $remains >= $roomCount;

            $days[] = [
                'date' => $dateStr,
                'in_month' => $inMonth,
                'available' => $available,
                'remains' => $remains,
                'price' => $available ? (int) ($priceByDate[$dateStr]['amount'] ?? 0) : null,
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
            'days' => $days,
        ];
    }
}
