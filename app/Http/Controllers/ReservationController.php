<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Plan;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservationController extends Controller
{
    // ① プラン選択画面を表示
    public function create(Request $request)
    {
        return Inertia::render('Reservations/Create', [
            'rooms' => Room::with('plans')->get(),
            'selectedRoomId' => $request->query('room_id'),
        ]);
    }

    // ② 予約内容詳細画面
    public function details(Request $request)
    {
        return Inertia::render('Reservations/Details', [
            'input' => $request->all(),
            'room' => Room::find($request->room_id),
            'plan' => Plan::find($request->plan_id),
        ]);
    }

    // ③ 最終確認画面
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

    // ④ 予約実行（既存のstoreメソッド）
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

        return redirect()->route('top')->with('success', 'ご予約が完了いたしました。');

    }
}
