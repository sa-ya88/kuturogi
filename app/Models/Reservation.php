<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'room_id', 'checkin_date', 'checkout_date', 'guest_count', 'total_price', 'status',
        'guest_name', 'guest_name_kana', 'guest_tel', 'guest_email', 'guest_zip_code', 'guest_address', 'guest_building', 'payment_method'
    ];

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
