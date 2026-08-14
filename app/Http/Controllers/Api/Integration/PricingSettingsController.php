<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\PricingCancelRule;
use App\Models\PricingChildRate;
use App\Models\PricingOptionFee;
use App\Models\PricingSeasonRate;
use App\Models\PricingWeekendRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingSettingsController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'weekend' => 'required|array',
            'weekend.friday_percent' => 'required|integer|min:0|max:500',
            'weekend.saturday_percent' => 'required|integer|min:0|max:500',
            'weekend.sunday_percent' => 'required|integer|min:0|max:500',
            'weekend.holiday_percent' => 'required|integer|min:0|max:500',
            'weekend.day_before_holiday_percent' => 'required|integer|min:0|max:500',
            'season_rates' => 'nullable|array',
            'season_rates.*.id' => 'nullable|integer',
            'season_rates.*.name' => 'required|string|max:255',
            'season_rates.*.kind' => 'required|string|max:50',
            'season_rates.*.priority' => 'required|integer|min:0',
            'season_rates.*.adjustment_type' => 'required|in:surcharge,discount',
            'season_rates.*.date_from' => 'required|date',
            'season_rates.*.date_to' => 'required|date',
            'season_rates.*.percent' => 'required|integer|min:0|max:500',
            'season_rates.*.is_active' => 'required|boolean',
            'child_rate' => 'required|array',
            'child_rate.name' => 'required|string|max:255',
            'child_rate.percent_of_adult' => 'required|integer|min:0|max:100',
            'child_rate.is_active' => 'required|boolean',
            'option_fees' => 'nullable|array',
            'option_fees.*.id' => 'nullable|integer',
            'option_fees.*.name' => 'required|string|max:255',
            'option_fees.*.price' => 'required|integer|min:0',
            'option_fees.*.description' => 'nullable|string',
            'option_fees.*.is_active' => 'required|boolean',
            'cancel_rules' => 'nullable|array',
            'cancel_rules.*.id' => 'nullable|integer',
            'cancel_rules.*.label' => 'required|string|max:255',
            'cancel_rules.*.days_before_from' => 'required|integer|min:0',
            'cancel_rules.*.days_before_to' => 'required|integer|min:0',
            'cancel_rules.*.charge_percent' => 'required|integer|min:0|max:100',
            'cancel_rules.*.is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated): void {
            $weekend = PricingWeekendRule::current();
            $weekend->update($validated['weekend']);

            $child = PricingChildRate::current();
            $child->update([
                'name' => $validated['child_rate']['name'],
                'percent_of_adult' => $validated['child_rate']['percent_of_adult'],
                'is_active' => $validated['child_rate']['is_active'],
                'sort_order' => 1,
            ]);
            PricingChildRate::query()->where('id', '!=', $child->id)->delete();

            $seasonIds = [];
            foreach (array_values($validated['season_rates'] ?? []) as $index => $row) {
                $payload = [
                    'name' => $row['name'],
                    'kind' => $row['kind'],
                    'priority' => $row['priority'],
                    'adjustment_type' => $row['adjustment_type'],
                    'date_from' => $row['date_from'],
                    'date_to' => $row['date_to'],
                    'percent' => $row['percent'],
                    'sort_order' => $index + 1,
                    'is_active' => $row['is_active'],
                ];

                $adminId = $row['id'] ?? null;
                $rate = $adminId
                    ? PricingSeasonRate::query()->updateOrCreate(['admin_id' => $adminId], $payload)
                    : PricingSeasonRate::query()->create($payload);
                $seasonIds[] = $rate->id;
            }
            if ($seasonIds === []) {
                PricingSeasonRate::query()->delete();
            } else {
                PricingSeasonRate::query()->whereNotIn('id', $seasonIds)->delete();
            }

            $optionIds = [];
            foreach (array_values($validated['option_fees'] ?? []) as $index => $row) {
                $payload = [
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'description' => $row['description'] ?? null,
                    'sort_order' => $index + 1,
                    'is_active' => $row['is_active'],
                ];
                $adminId = $row['id'] ?? null;
                $fee = $adminId
                    ? PricingOptionFee::query()->updateOrCreate(['admin_id' => $adminId], $payload)
                    : PricingOptionFee::query()->create($payload);
                $optionIds[] = $fee->id;
            }
            if ($optionIds === []) {
                PricingOptionFee::query()->delete();
            } else {
                PricingOptionFee::query()->whereNotIn('id', $optionIds)->delete();
            }

            $cancelIds = [];
            foreach (array_values($validated['cancel_rules'] ?? []) as $index => $row) {
                $payload = [
                    'label' => $row['label'],
                    'days_before_from' => max($row['days_before_from'], $row['days_before_to']),
                    'days_before_to' => min($row['days_before_from'], $row['days_before_to']),
                    'is_no_show' => false,
                    'charge_percent' => $row['charge_percent'],
                    'sort_order' => $index + 1,
                    'is_active' => $row['is_active'],
                ];
                $adminId = $row['id'] ?? null;
                $rule = $adminId
                    ? PricingCancelRule::query()->updateOrCreate(['admin_id' => $adminId], $payload)
                    : PricingCancelRule::query()->create($payload);
                $cancelIds[] = $rule->id;
            }
            if ($cancelIds === []) {
                PricingCancelRule::query()->delete();
            } else {
                PricingCancelRule::query()->whereNotIn('id', $cancelIds)->delete();
            }
        });

        return response()->json(['ok' => true]);
    }
}
