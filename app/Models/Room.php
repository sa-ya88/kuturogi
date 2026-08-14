<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_per_person',
        'stock_count',
        'available_from',
        'available_to',
        'description',
        'features',
        'images',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
        'available_from' => 'date',
        'available_to' => 'date',
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}

