<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
