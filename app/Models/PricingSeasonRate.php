<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingSeasonRate extends Model
{
    public const ADJUSTMENT_SURCHARGE = 'surcharge';

    public const ADJUSTMENT_DISCOUNT = 'discount';

    protected $fillable = [
        'admin_id',
        'name',
        'kind',
        'priority',
        'adjustment_type',
        'date_from',
        'date_to',
        'percent',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function signedPercent(): int
    {
        $percent = (int) $this->percent;

        return $this->adjustment_type === self::ADJUSTMENT_DISCOUNT
            ? -$percent
            : $percent;
    }
}
