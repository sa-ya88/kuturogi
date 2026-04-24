<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'description', 
        'features',
        'images',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }
}

