<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_AUTHORIZED = 'authorized';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_FAILED = 'failed';

    protected $fillable = [
        'user_id', 'plan_id', 'room_id', 'checkin_date', 'checkout_date', 'guest_count',
        'room_count', 'adult_count', 'child_count', 'total_price', 'status',
        'guest_name', 'guest_name_kana', 'guest_tel', 'guest_email', 'guest_zip_code',
        'guest_address', 'guest_building', 'payment_method', 'payment_status',
        'stripe_payment_intent_id', 'stripe_latest_charge_id',
        'authorized_at', 'paid_at', 'refunded_at',
        'cancel_fee_amount', 'stripe_cancel_fee_payment_intent_id', 'cancel_fee_uncollected',
        'selected_choices', 'selected_option_fees',
        'source', 'stay_status', 'customer_id', 'kuturogi_reservation_id',
    ];

    protected function casts(): array
    {
        return [
            'checkin_date' => 'date',
            'checkout_date' => 'date',
            'selected_choices' => 'array',
            'selected_option_fees' => 'array',
            'authorized_at' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'cancel_fee_uncollected' => 'boolean',
        ];
    }

    public static function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            self::PAYMENT_AUTHORIZED => '与信済',
            self::PAYMENT_PAID => '支払済',
            self::PAYMENT_REFUNDED => '返金済',
            self::PAYMENT_FAILED => '失敗',
            default => '未払',
        };
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
