<?php

namespace App\Services;

use App\Models\RoomInventory;
use Illuminate\Support\Facades\DB;

class RoomInventoryService
{
    /**
     * 指定期間の在庫を増減する（チェックアウト日は含まない）。
     *
     * @throws \RuntimeException 在庫不足時
     */
    public function adjustForStay(
        int $roomId,
        string $checkinDate,
        string $checkoutDate,
        int $roomCount,
        string $operation = 'decrement',
    ): void {
        DB::transaction(function () use ($roomId, $checkinDate, $checkoutDate, $roomCount, $operation) {
            $period = new \DatePeriod(
                new \DateTime($checkinDate),
                new \DateInterval('P1D'),
                new \DateTime($checkoutDate)
            );

            foreach ($period as $date) {
                $formattedDate = $date->format('Y-m-d');

                $inventory = RoomInventory::where('room_id', $roomId)
                    ->where('date', $formattedDate)
                    ->lockForUpdate()
                    ->first();

                if ($operation === 'decrement') {
                    if (! $inventory || $inventory->remains < $roomCount) {
                        throw new \RuntimeException("{$formattedDate} は満室です。");
                    }
                    $inventory->decrement('remains', $roomCount);
                } else {
                    if ($inventory) {
                        $inventory->increment('remains', $roomCount);
                    } else {
                        RoomInventory::create([
                            'room_id' => $roomId,
                            'date' => $formattedDate,
                            'remains' => $roomCount,
                        ]);
                    }
                }
            }
        });
    }
}
