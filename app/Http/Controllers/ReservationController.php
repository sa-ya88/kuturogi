<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Plan;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\RoomInventory;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    // プラン選択画面を表示
    public function create(Request $request)
    {
        $checkin = $request->query('checkin');
        $checkout = $request->query('checkout');
        $roomCount = $request->query('room_count', 1);

        $rooms = Room::with(['plans'])->get()->map(function ($room) use ($checkin, $checkout) {
            $minStock = null;

            if ($checkin && $checkout) {
                // チェックアウト前日までの在庫を取得
                $lastNight = date('Y-m-d', strtotime($checkout . ' -1 day'));
                
                // 期間中の1日ごとの在庫を取得
                $inventories = \App\Models\RoomInventory::where('room_id', $room->id)
                    ->whereBetween('date', [$checkin, $lastNight])
                    ->pluck('remains');

                // 1. 指定期間のデータが揃っているか確認 (泊数と一致するか)
                $nights = (strtotime($checkout) - strtotime($checkin)) / 86400;
                
                if ($inventories->count() < $nights) {
                    // 在庫データが1日分でも足りない場合は「在庫なし(0)」とする
                    $minStock = 0;
                } else {
                    // 2. 期間中の一番少ない在庫数をその部屋の在庫とする
                    $minStock = $inventories->min();
                }
            }

            // roomオブジェクトに在庫情報を追加
            $room->current_inventory = $minStock;
            return $room;
        });

        return Inertia::render('Reservations/Create', [
            'rooms' => $rooms,
            'selectedRoomId' => $request->query('room_id'),
            // 検索条件をReact側へ引き継ぐ
            'searchParams' => [
                'checkin' => $checkin,
                'checkout' => $checkout,
                'room_count' => (int)$roomCount,
            ]
        ]);
    }


    // 予約内容詳細画面
    public function details(Request $request)
    {
        return Inertia::render('Reservations/Details', [
            'input' => $request->all(),
            'room' => Room::find($request->room_id),
            'plan' => Plan::find($request->plan_id),
        ]);
    }

    // 最終確認画面
    public function confirm(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        // 宿泊日数の計算（最低1泊）
        $days = max(1, (strtotime($request->checkout_date) - strtotime($request->checkin_date)) / 86400);
        // 合計金額の計算
        $total = $plan->price_per_person * ($request->adult_count + $request->child_count) * $days * $request->room_count;

        return Inertia::render('Reservations/Confirm', [
            'input' => $request->all(),
            'room' => Room::find($request->room_id),
            'plan' => $plan,
            'totalPrice' => $total,
        ]);
    }

    // 予約実行
    public function store(Request $request)
    {
        // 1. バリデーションの拡張
        $validated = $request->validate([
            'plan_id'       => 'required|exists:plans,id',
            'room_id'       => 'required|exists:rooms,id',
            'checkin_date'  => 'required|date|after_or_equal:today',
            'checkout_date' => 'required|date|after:checkin_date',
            'adult_count'   => 'required|integer|min:1',
            'child_count'   => 'required|integer|min:0',
            'room_count'    => 'required|integer|min:1',
        ]);

        // 2. 料金計算（例：子供は大人料金の70%として計算）
        $plan = \App\Models\Plan::findOrFail($validated['plan_id']);
        $days = (strtotime($validated['checkout_date']) - strtotime($validated['checkin_date'])) / 86400;
        
        $adultPrice = $plan->price_per_person * $validated['adult_count'];
        $childPrice = ($plan->price_per_person * 0.7) * $validated['child_count'];
        
        $totalPrice = ($adultPrice + $childPrice) * $days * $validated['room_count'];

        // 3. 保存
        \App\Models\Reservation::create([
            'user_id'       => auth()->id(),
            'plan_id'       => $validated['plan_id'],
            'room_id'       => $validated['room_id'], // RoomIDも保存
            'checkin_date'  => $validated['checkin_date'],
            'checkout_date' => $validated['checkout_date'],
            'guest_count'   => $validated['adult_count'] + $validated['child_count'],
            'total_price'   => $totalPrice,
            'status'        => 'confirmed',
        ]);

        // 4.部屋在庫を減らす
        DB::transaction(function () use ($request) {
            // 宿泊期間（チェックアウト日は含まない）の各日付を取得
            $period = new \DatePeriod(
                new \DateTime($request->checkin_date),
                new \DateInterval('P1D'),
                new \DateTime($request->checkout_date)
            );

            foreach ($period as $date) {
                $formattedDate = $date->format('Y-m-d');

                // 在庫を取得（他の予約と重ならないようロックをかける）
                $inventory = RoomInventory::where('room_id', $request->room_id)
                    ->where('date', $formattedDate)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->remains < $request->room_count) {
                    throw new \Exception($formattedDate . "は満室です。");
                }

                // 在庫を減らす
                $inventory->decrement('remains', $request->room_count);
            }
        });

        return redirect()->route('top')->with('success', 'ご予約が完了いたしました。');

    }
}
