<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SharedReservationOccupancyService
{
    public function enabled(): bool
    {
        return (bool) config('integration.shared_database')
            && Schema::hasTable('reservation_stays')
            && Schema::hasTable('room_unit_date_occupancies');
    }

    public function assign(Reservation $reservation): void
    {
        if (! $this->enabled()) {
            return;
        }

        $roomCount = max(1, (int) $reservation->room_count);
        $dates = $this->nightDates($reservation);

        if ($dates === []) {
            throw new \RuntimeException('宿泊日が不正です。');
        }

        $existing = DB::table('reservation_stays')
            ->where('reservation_id', $reservation->id)
            ->count();

        if ($existing === 0) {
            $now = now();
            for ($i = 1; $i <= $roomCount; $i++) {
                DB::table('reservation_stays')->insert([
                    'reservation_id' => $reservation->id,
                    'representative_name' => $i === 1
                        ? (string) ($reservation->guest_name ?: '未設定')
                        : '',
                    'sort_order' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $stays = DB::table('reservation_stays')
            ->where('reservation_id', $reservation->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($stays as $stay) {
            if ($stay->room_unit_id) {
                continue;
            }

            $unitId = $this->firstAvailableUnitId((int) $reservation->room_id, $dates, (int) $reservation->id);

            if ($unitId === null) {
                throw new \RuntimeException('空きのある個別客室が不足しています。');
            }

            $now = now();
            DB::table('reservation_stays')
                ->where('id', $stay->id)
                ->update([
                    'room_unit_id' => $unitId,
                    'updated_at' => $now,
                ]);

            foreach ($dates as $date) {
                DB::table('room_unit_date_occupancies')->insert([
                    'room_unit_id' => $unitId,
                    'date' => $date,
                    'reservation_id' => $reservation->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('room_units')
                ->where('id', $unitId)
                ->update([
                    'current_status' => 'awaiting_arrival',
                    'updated_at' => $now,
                ]);
        }

        $this->refreshRemains((int) $reservation->room_id, $dates);
    }

    public function release(Reservation $reservation): void
    {
        if (! $this->enabled()) {
            return;
        }

        $dates = $this->nightDates($reservation);
        $unitIds = DB::table('room_unit_date_occupancies')
            ->where('reservation_id', $reservation->id)
            ->pluck('room_unit_id');

        DB::table('room_unit_date_occupancies')
            ->where('reservation_id', $reservation->id)
            ->delete();

        DB::table('reservation_stays')
            ->where('reservation_id', $reservation->id)
            ->update([
                'room_unit_id' => null,
                'updated_at' => now(),
            ]);

        foreach ($unitIds as $unitId) {
            $stillUsed = DB::table('room_unit_date_occupancies')
                ->where('room_unit_id', $unitId)
                ->exists();

            if (! $stillUsed) {
                DB::table('room_units')
                    ->where('id', $unitId)
                    ->whereIn('current_status', ['awaiting_arrival', 'in_house'])
                    ->update([
                        'current_status' => 'bookable',
                        'updated_at' => now(),
                    ]);
            }
        }

        $this->refreshRemains((int) $reservation->room_id, $dates);
    }

    /**
     * @param  list<string>  $dates
     */
    protected function firstAvailableUnitId(int $roomId, array $dates, int $reservationId): ?int
    {
        $units = DB::table('room_units')
            ->where('room_id', $roomId)
            ->where('operation_status', 'in_service')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->lockForUpdate()
            ->get();

        foreach ($units as $unit) {
            $taken = DB::table('room_unit_date_occupancies')
                ->where('room_unit_id', $unit->id)
                ->where(function ($query) use ($dates): void {
                    foreach ($dates as $date) {
                        $query->orWhereDate('date', $date);
                    }
                })
                ->where('reservation_id', '!=', $reservationId)
                ->exists();

            if (! $taken) {
                return (int) $unit->id;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $dates
     */
    protected function refreshRemains(int $roomId, array $dates): void
    {
        $stock = (int) DB::table('room_units')
            ->where('room_id', $roomId)
            ->where('operation_status', 'in_service')
            ->count();

        foreach ($dates as $date) {
            $occupied = (int) DB::table('room_unit_date_occupancies as occupancies')
                ->join('room_units as units', 'units.id', '=', 'occupancies.room_unit_id')
                ->where('units.room_id', $roomId)
                ->whereDate('occupancies.date', $date)
                ->count();

            $inventory = DB::table('room_inventories')
                ->where('room_id', $roomId)
                ->whereDate('date', $date)
                ->first();

            $payload = [
                'remains' => max(0, $stock - $occupied),
                'updated_at' => now(),
            ];

            if ($inventory) {
                DB::table('room_inventories')->where('id', $inventory->id)->update($payload);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function nightDates(Reservation $reservation): array
    {
        $from = Carbon::parse($reservation->checkin_date)->startOfDay();
        $to = Carbon::parse($reservation->checkout_date)->startOfDay();
        $dates = [];

        for ($date = $from->copy(); $date->lt($to); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }
}
