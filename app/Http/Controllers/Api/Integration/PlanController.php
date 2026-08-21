<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\PlanChoiceOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Plan::with('rooms')->orderBy('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePlanPayload($request);

        $plan = Plan::create($this->planAttributesFromValidated($validated));

        if (array_key_exists('room_ids', $validated)) {
            $plan->rooms()->sync($validated['room_ids'] ?? []);
        }

        return response()->json($plan->fresh()->load('rooms'), 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $this->validatePlanPayload($request, partial: true);

        $plan->update($this->planAttributesFromValidated($validated));

        if (array_key_exists('room_ids', $validated)) {
            $plan->rooms()->sync($validated['room_ids'] ?? []);
        }

        return response()->json($plan->fresh()->load('rooms'));
    }

    public function destroy(Plan $plan): JsonResponse
    {
        if ($plan->hasBlockingReservations()) {
            return response()->json([
                'message' => $plan->deletionBlockedMessage(),
            ], 422);
        }

        $plan->rooms()->detach();
        $plan->delete();

        return response()->json(['status' => 'ok']);
    }

    protected function validatePlanPayload(Request $request, bool $partial = false): array
    {
        $requiredRule = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => "{$requiredRule}|string|max:255",
            'price_per_person' => "{$requiredRule}|integer|min:0",
            'description' => 'nullable|string',
            'choice_options' => 'nullable|array',
            'choice_options.*.prompt' => 'required_with:choice_options|string|max:255',
            'choice_options.*.choices' => 'required_with:choice_options|array|min:1',
            'choice_options.*.choices.*.label' => 'required|string|max:255',
            'images' => 'nullable|array',
            'has_breakfast' => 'sometimes|boolean',
            'has_dinner' => 'sometimes|boolean',
            'has_checkin_time' => 'sometimes|boolean',
            'checkin_time' => [
                Rule::requiredIf(fn () => $request->boolean('has_checkin_time')),
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],
            'has_checkout_time' => 'sometimes|boolean',
            'checkout_time' => [
                Rule::requiredIf(fn () => $request->boolean('has_checkout_time')),
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],
            'has_early_bird' => 'sometimes|boolean',
            'early_bird_discount_type' => [
                Rule::requiredIf(fn () => $request->boolean('has_early_bird')),
                'nullable',
                Rule::in([Plan::DISCOUNT_TYPE_PERCENT, Plan::DISCOUNT_TYPE_FIXED]),
            ],
            'early_bird_discount_value' => [
                Rule::requiredIf(fn () => $request->boolean('has_early_bird')),
                'nullable',
                'integer',
                'min:1',
            ],
            'early_bird_days_before' => [
                Rule::requiredIf(fn () => $request->boolean('has_early_bird')),
                'nullable',
                'integer',
                'min:1',
            ],
            'room_ids' => 'nullable|array',
            'room_ids.*' => 'integer|exists:rooms,id',
        ]);
    }

    protected function planAttributesFromValidated(array $validated): array
    {
        $attributes = collect($validated)
            ->except('room_ids')
            ->all();

        if (array_key_exists('description', $attributes) && $attributes['description'] === null) {
            $attributes['description'] = '';
        }

        if (array_key_exists('images', $attributes) && $attributes['images'] === null) {
            $attributes['images'] = [];
        }

        if (array_key_exists('has_checkin_time', $attributes) && empty($attributes['has_checkin_time'])) {
            $attributes['has_checkin_time'] = false;
            $attributes['checkin_time'] = null;
        }

        if (array_key_exists('has_checkout_time', $attributes) && empty($attributes['has_checkout_time'])) {
            $attributes['has_checkout_time'] = false;
            $attributes['checkout_time'] = null;
        }

        if (array_key_exists('has_early_bird', $attributes) && empty($attributes['has_early_bird'])) {
            $attributes['has_early_bird'] = false;
            $attributes['early_bird_discount_type'] = null;
            $attributes['early_bird_discount_value'] = null;
            $attributes['early_bird_days_before'] = null;
        }

        if (array_key_exists('choice_options', $attributes)) {
            $attributes['choice_options'] = PlanChoiceOptions::normalize($attributes['choice_options']);
        }

        return $attributes;
    }
}
