<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'checkin_date', 'checkout_date', 'guest_count', 'total_price', 'status'
    ];

}
