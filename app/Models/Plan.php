<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public const DISCOUNT_TYPE_PERCENT = 'percent';

    public const DISCOUNT_TYPE_FIXED = 'fixed';

    protected $fillable = [
        'name',
        'price_per_person',
        'description',
        'choice_options',
        'images',
        'has_breakfast',
        'has_dinner',
        'has_checkin_time',
        'checkin_time',
        'has_checkout_time',
        'checkout_time',
        'has_early_bird',
        'early_bird_discount_type',
        'early_bird_discount_value',
        'early_bird_days_before',
    ];

    protected $casts = [
        'images' => 'array',
        'choice_options' => 'array',
        'has_breakfast' => 'boolean',
        'has_dinner' => 'boolean',
        'has_checkin_time' => 'boolean',
        'has_checkout_time' => 'boolean',
        'has_early_bird' => 'boolean',
        'early_bird_discount_value' => 'integer',
        'early_bird_days_before' => 'integer',
    ];

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }
}
