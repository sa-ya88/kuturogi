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
        'details',
        'images',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'details' => 'array',
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

    public function hasBlockingReservations(): bool
    {
        return $this->reservations()->exists();
    }

    public function deletionBlockedMessage(): string
    {
        $total = $this->reservations()->count();
        $upcoming = $this->reservations()
            ->where('status', '!=', 'cancelled')
            ->whereDate('checkout_date', '>=', now()->toDateString())
            ->count();
        $cancelled = $this->reservations()
            ->where('status', 'cancelled')
            ->count();

        $details = ["全{$total}件"];

        if ($upcoming > 0) {
            $details[] = "今後の有効な予約{$upcoming}件";
        }

        if ($cancelled > 0) {
            $details[] = "キャンセル済み{$cancelled}件";
        }

        return 'この客室タイプは予約履歴があるため削除できません（'.implode('、', $details).'）。'
            .'過去の予約も含めデータを残す必要があるため、削除ではなく「公開」をOFFにするとサイトのお部屋一覧から非表示になります。';
    }
}

