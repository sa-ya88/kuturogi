<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RoomInventory extends Model
{
    protected $fillable = ['room_id', 'date', 'remains', 'synced_at'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'synced_at' => 'datetime',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', Carbon::parse($date)->toDateString());
    }

    public function scopeOnDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query
            ->whereDate('date', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('date', '<=', Carbon::parse($to)->toDateString());
    }

    public function dateString(): string
    {
        return Carbon::parse($this->date)->toDateString();
    }
}
