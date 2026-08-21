<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PricingCancelRule extends Model
{
    protected $fillable = [
        'admin_id',
        'label',
        'days_before_from',
        'days_before_to',
        'is_no_show',
        'charge_percent',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_no_show' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function displayText(): string
    {
        return "{$this->label}：宿泊料金の{$this->charge_percent}%";
    }

    /**
     * @return list<string>
     */
    public static function activeDisplayTexts(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (self $rule): string => $rule->displayText())
            ->values()
            ->all();
    }

    public static function daysBeforeCheckin(DateTimeInterface|string $checkinDate, ?Carbon $asOf = null): int
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $checkin = Carbon::parse($checkinDate)->startOfDay();

        if ($checkin->lt($asOf)) {
            return -1;
        }

        return (int) $asOf->diffInDays($checkin);
    }

    public static function chargePercentForDaysBefore(int $daysBefore): int
    {
        $percents = static::query()
            ->where('is_active', true)
            ->where('is_no_show', false)
            ->where('days_before_from', '>=', $daysBefore)
            ->where('days_before_to', '<=', $daysBefore)
            ->pluck('charge_percent');

        if ($percents->isEmpty()) {
            return 0;
        }

        return (int) $percents->max();
    }

    public static function allowsFreeCancellation(DateTimeInterface|string $checkinDate, ?Carbon $asOf = null): bool
    {
        $daysBefore = static::daysBeforeCheckin($checkinDate, $asOf);

        if ($daysBefore < 0) {
            return false;
        }

        return static::chargePercentForDaysBefore($daysBefore) === 0;
    }
}
