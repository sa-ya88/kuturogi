<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class JapanHolidayService
{
    /** @var array<int, array<string, true>> */
    protected array $cache = [];

    public function isHoliday(Carbon|string $date): bool
    {
        $date = Carbon::parse($date)->startOfDay();
        $holidays = $this->holidaysForYear($date->year);

        return isset($holidays[$date->toDateString()]);
    }

    public function isDayBeforeHoliday(Carbon|string $date): bool
    {
        $date = Carbon::parse($date)->startOfDay();

        return $this->isHoliday($date->copy()->addDay());
    }

    /**
     * @return array<string, true>
     */
    public function holidaysForYear(int $year): array
    {
        if (isset($this->cache[$year])) {
            return $this->cache[$year];
        }

        $dates = [];

        $add = function (int $month, int $day) use (&$dates, $year): void {
            $dates[sprintf('%04d-%02d-%02d', $year, $month, $day)] = true;
        };

        $add(1, 1);
        $this->addNthMonday($dates, $year, 1, 2);
        $add(2, 11);
        $add(2, 23);
        $add(3, $this->vernalEquinoxDay($year));
        $add(4, 29);
        $add(5, 3);
        $add(5, 4);
        $add(5, 5);
        $this->addNthMonday($dates, $year, 7, 3);
        $add(8, 11);
        $this->addNthMonday($dates, $year, 9, 3);
        $add(9, $this->autumnalEquinoxDay($year));
        $this->addNthMonday($dates, $year, 10, 2);
        $add(11, 3);
        $add(11, 23);

        foreach (array_keys($dates) as $dateString) {
            $date = Carbon::parse($dateString);
            if ($date->isSunday()) {
                $substitute = $date->copy()->addDay();
                while (isset($dates[$substitute->toDateString()])) {
                    $substitute->addDay();
                }
                $dates[$substitute->toDateString()] = true;
            }
        }

        $sorted = array_keys($dates);
        sort($sorted);
        foreach ($sorted as $dateString) {
            $mid = Carbon::parse($dateString)->addDay();
            $after = Carbon::parse($dateString)->addDays(2);
            if (isset($dates[$after->toDateString()])
                && ! isset($dates[$mid->toDateString()])
                && ! $mid->isSunday()) {
                $dates[$mid->toDateString()] = true;
            }
        }

        $this->cache[$year] = $dates;

        return $dates;
    }

    /**
     * @param  array<string, true>  $dates
     */
    protected function addNthMonday(array &$dates, int $year, int $month, int $nth): void
    {
        $date = Carbon::create($year, $month, 1)->startOfDay();
        $count = 0;
        while ($date->month === $month) {
            if ($date->isMonday()) {
                $count++;
                if ($count === $nth) {
                    $dates[$date->toDateString()] = true;

                    return;
                }
            }
            $date->addDay();
        }
    }

    protected function vernalEquinoxDay(int $year): int
    {
        return (int) floor(20.8431 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }

    protected function autumnalEquinoxDay(int $year): int
    {
        return (int) floor(23.2488 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }
}
