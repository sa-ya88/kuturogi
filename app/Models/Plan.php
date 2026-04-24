<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'room_id',
        'name',
        'price_per_person',
        'description',
        'images',
        'has_breakfast',
        'has_dinner',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }

}
