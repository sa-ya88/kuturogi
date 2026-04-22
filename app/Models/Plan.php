<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['room_id', 'name', 'price_per_person', 'description'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

}
