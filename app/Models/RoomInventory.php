<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInventory extends Model
{
    protected $fillable = ['room_id', 'date', 'remains'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
