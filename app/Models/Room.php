<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    // 一括登録を許可するカラム
    protected $fillable = ['name', 'description', 'capacity', 'image_url', 'features'];

    // 【重要】featuresカラムを配列（JSON）として扱う設定
    protected $casts = [
        'features' => 'array',
    ];

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }
}

