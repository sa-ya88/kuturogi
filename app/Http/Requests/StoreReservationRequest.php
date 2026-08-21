<?php

namespace App\Http\Requests;

use App\Support\PersonName;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if (! $user) {
            return;
        }

        $fromMember = PersonName::guestFieldsFromUser($user);
        $merged = [];
        foreach ($fromMember as $key => $value) {
            if (blank($this->input($key)) && $value !== '') {
                $merged[$key] = $value;
            }
        }
        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $guestRule = $this->user() ? 'nullable' : 'required';

        return [
            'plan_id' => 'required|exists:plans,id',
            'room_id' => 'required|exists:rooms,id',
            'checkin_date' => 'nullable|date',
            'check_in_date' => 'nullable|date',
            'checkout_date' => 'nullable|date',
            'check_out_date' => 'nullable|date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'required|integer|min:0',
            'room_count' => 'required|integer|min:1',
            'last_name' => [$guestRule, 'string'],
            'first_name' => [$guestRule, 'string'],
            'last_name_kana' => [$guestRule, 'string'],
            'first_name_kana' => [$guestRule, 'string'],
            'tel' => [$guestRule, 'string'],
            'email' => [$this->user() ? 'nullable' : 'required', 'email'],
            'zip_code' => [$guestRule, 'string'],
            'address' => [$guestRule, 'string'],
            'building' => 'nullable|string',
            'payment_method' => 'required|in:local,credit',
            'payment_intent_id' => 'nullable|string',
            'selected_choices' => 'nullable|array',
            'selected_choices.*' => 'nullable|string|max:255',
            'selected_option_ids' => 'nullable|array',
            'selected_option_ids.*' => 'integer',
            'representatives' => 'nullable|array',
            'representatives.*' => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'プランを選択してください',
            'room_id.required' => 'お部屋を選択してください',
        ];
    }
}
