<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    // データベースで一括登録を許可する項目を指定
    protected $fillable = ['title', 'content', 'published_at'];
}
