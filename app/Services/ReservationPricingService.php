<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PricingCancelRule;
use App\Models\PricingChildRate;
use App\Models\PricingOptionFee;
use App\Models\PricingSeasonRate;
use App\Models\PricingWeekendRule;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReservationPricingService
{
    public function __construct(
        protected JapanHolidayService $holidays,
    ) {}

    /**
     * @param  array<int, int|string>  $selectedOptionIds
     * @return array{
     *   total: int,
     *   base_per_person_per_night: int,
     *   nights_count: int,
     *   child_percent: int,
     *   child_label: string,
     *   nights: list<array<string, mixed>>,
     *   lodging_subtotal: int,
     *   options_subtotal: int,
     *   selected_options: list<array{id:int,name:string,price:int}>,
     *   cancel_policy: list<string>,
     *   summary_adjustments: list<string>
     * }
     */
    public function calculate(
        Room $room,
        Plan $plan,
        string $checkinDate,
        string $checkoutDate,
        int $adultCount,
        int $childCount,
        int $roomCount,
        array $selectedOptionIds = [],
    ): array {
        $adultCount = max(1, $adultCount);
        $childCount = max(0, $childCount);
        $roomCount = max(1, $roomCount);

        $base = (int) $plan->price_per_person + (int) $room->price_per_person;
        $child = PricingChildRate::current();
        $childPercent = $child->is_active ? (int) $child->percent_of_adult : 70;
        $weekend = PricingWeekendRule::current();
        $seasons = PricingSeasonRate::query()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $from = Carbon::parse($checkinDate)->startOfDay();
        $to = Carbon::parse($checkoutDate)->startOfDay();
        $nights = [];
        $lodgingSubtotal = 0;
        $adjustmentLabels = [];

        for ($date = $from->copy(); $date->lt($to); $date->addDay()) {
            $adjustment = $this->resolveNightAdjustment($date, $weekend, $seasons);
            $factor = 1 + ($adjustment['signed_percent'] / 100);
            $adultNight = (int) round($base * $adultCount * $factor);
            $childNight = (int) round($base * ($childPercent / 100) * $childCount * $factor);
            $nightTotal = ($adultNight + $childNight) * $roomCount;
            $lodgingSubtotal += $nightTotal;

            if ($adjustment['signed_percent'] !== 0 && $adjustment['label'] !== null) {
                $adjustmentLabels[$adjustment['label']] = true;
            }

            $nights[] = [
                'date' => $date->toDateString(),
                'signed_percent' => $adjustment['signed_percent'],
                'label' => $adjustment['label'],
                'source' => $adjustment['source'],
                'adult_amount' => $adultNight * $roomCount,
                'child_amount' => $childNight * $roomCount,
                'amount' => $nightTotal,
            ];
        }

        $selectedOptions = $this->resolveSelectedOptions($selectedOptionIds);
        $optionsSubtotal = (int) $selectedOptions->sum('price');
        $total = $lodgingSubtotal + $optionsSubtotal;

        return [
            'total' => $total,
            'base_per_person_per_night' => $base,
            'nights_count' => count($nights),
            'child_percent' => $childPercent,
            'child_label' => $child->name,
            'nights' => $nights,
            'lodging_subtotal' => $lodgingSubtotal,
            'options_subtotal' => $optionsSubtotal,
            'selected_options' => $selectedOptions->values()->all(),
            'cancel_policy' => PricingCancelRule::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (PricingCancelRule $rule): string => $rule->displayText())
                ->all(),
            'summary_adjustments' => array_keys($adjustmentLabels),
        ];
    }

    /**
     * @param  Collection<int, PricingSeasonRate>  $seasons
     * @return array{signed_percent: int, label: ?string, source: string}
     */
    protected function resolveNightAdjustment(
        Carbon $date,
        PricingWeekendRule $weekend,
        Collection $seasons,
    ): array {
        $matchingSeasons = $seasons->filter(
            fn (PricingSeasonRate $season): bool => $date->betweenIncluded(
                Carbon::parse($season->date_from)->startOfDay(),
                Carbon::parse($season->date_to)->startOfDay(),
            )
        );

        if ($matchingSeasons->isNotEmpty()) {
            /** @var PricingSeasonRate $season */
            $season = $matchingSeasons->sortBy([
                ['priority', 'desc'],
                ['id', 'asc'],
            ])->first();

            $signed = $season->signedPercent();
            $typeLabel = $signed < 0 ? '割引' : '割増';

            return [
                'signed_percent' => $signed,
                'label' => "{$season->name}（{$typeLabel}".abs($signed).'%）',
                'source' => 'season',
            ];
        }

        $candidates = [];
        $dow = $date->dayOfWeek; // 0=Sun ... 6=Sat

        if ($dow === Carbon::FRIDAY && $weekend->friday_percent > 0) {
            $candidates['金曜日'] = (int) $weekend->friday_percent;
        }
        if ($dow === Carbon::SATURDAY && $weekend->saturday_percent > 0) {
            $candidates['土曜日'] = (int) $weekend->saturday_percent;
        }
        if ($dow === Carbon::SUNDAY && $weekend->sunday_percent > 0) {
            $candidates['日曜日'] = (int) $weekend->sunday_percent;
        }
        if ($this->holidays->isHoliday($date) && $weekend->holiday_percent > 0) {
            $candidates['祝日'] = (int) $weekend->holiday_percent;
        }
        if ($this->holidays->isDayBeforeHoliday($date) && $weekend->day_before_holiday_percent > 0) {
            $candidates['祝前日'] = (int) $weekend->day_before_holiday_percent;
        }

        if ($candidates === []) {
            return [
                'signed_percent' => 0,
                'label' => null,
                'source' => 'none',
            ];
        }

        arsort($candidates);
        $label = array_key_first($candidates);
        $percent = $candidates[$label];

        return [
            'signed_percent' => $percent,
            'label' => "{$label}（割増{$percent}%）",
            'source' => 'weekend',
        ];
    }

    /**
     * @param  array<int, int|string>  $selectedOptionIds
     * @return Collection<int, array{id: int, name: string, price: int}>
     */
    protected function resolveSelectedOptions(array $selectedOptionIds): Collection
    {
        $ids = collect($selectedOptionIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return PricingOptionFee::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PricingOptionFee $fee): array => [
                'id' => $fee->id,
                'name' => $fee->name,
                'price' => (int) $fee->price,
            ]);
    }
}
