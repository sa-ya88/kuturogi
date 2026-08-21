<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function hasBlockingReservations(): bool
    {
        return $this->reservations()->exists();
    }

    public function deletionBlockedMessage(): string
    {
        $total = $this->reservations()->count();

        return "このプランは予約履歴があるため削除できません（全{$total}件）。"
            .'サイトから外す場合は、管理画面で対象客室の紐付けを外すか、公開をOFFにしてください。';
    }
}
