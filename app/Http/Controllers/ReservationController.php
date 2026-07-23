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


    // 予約一覧（会員向け）
    public function index(Request $request)
    {
        $reservations = Reservation::with(['plan', 'room'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
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
        $room = Room::find($request->room_id);
        // 宿泊日数の計算（最低1泊）
        $days = max(1, (strtotime($request->checkout_date) - strtotime($request->checkin_date)) / 86400);
        // 1泊あたりの1人当たり料金
        $pricePerPersonPerNight = $plan->price_per_person + $room->price_per_person;
        // 合計金額の計算
        $adultTotal = $pricePerPersonPerNight * $request->adult_count * $days;
        $childTotal = ($pricePerPersonPerNight * 0.7) * $request->child_count * $days;
        $total = ($adultTotal + $childTotal) * $request->room_count;

        return Inertia::render('Reservations/Confirm', [
            'input' => $request->all(),
            'room' => $room,
            'plan' => $plan,
            'totalPrice' => $total,
            'pricePerPersonPerNight' => $pricePerPersonPerNight,
            'nights' => $days,
        ]);
    }

    // 予約実行
    public function store(Request $request)
    {
        // 1. バリデーションの拡張
        $validated = $request->validate([
            'plan_id'       => 'required|exists:plans,id',
            'room_id'       => 'required|exists:rooms,id',
            'checkin_date'  => 'nullable|date',
            'check_in_date' => 'nullable|date',
            'checkout_date' => 'nullable|date',
            'check_out_date' => 'nullable|date',
            'adult_count'   => 'required|integer|min:1',
            'child_count'   => 'required|integer|min:0',
            'room_count'    => 'required|integer|min:1',
            'last_name'     => 'required|string',
            'first_name'    => 'required|string',
            'last_name_kana' => 'required|string',
            'first_name_kana' => 'required|string',
            'tel'           => 'required|string',
            'email'         => 'required|email',
            'zip_code'      => 'required|string',
            'address'       => 'required|string',
            'building'      => 'nullable|string',
            'payment_method' => 'required|in:local,credit',
        ], [
            'plan_id.required' => 'プランを選択してください',
            'room_id.required' => 'お部屋を選択してください',
        ]);

        // 2. 料金計算（例：子供は大人料金の70%として計算）
        $plan = Plan::findOrFail($validated['plan_id']);
        $room = Room::findOrFail($validated['room_id']);
        
        // checkin_date と checkout_date を確認
        $checkinDate = $validated['checkin_date'] ?? $validated['check_in_date'] ?? null;
        $checkoutDate = $validated['checkout_date'] ?? $validated['check_out_date'] ?? null;
        
        if (!$checkinDate || !$checkoutDate) {
            return redirect()->back()->withErrors(['dates' => 'チェックイン日とチェックアウト日を入力してください']);
        }
        
        $days = max(1, (strtotime($checkoutDate) - strtotime($checkinDate)) / 86400);
        
        $pricePerPersonPerNight = $plan->price_per_person + $room->price_per_person;
        $adultPrice = $pricePerPersonPerNight * $validated['adult_count'];
        $childPrice = ($pricePerPersonPerNight * 0.7) * $validated['child_count'];
        
        $totalPrice = ($adultPrice + $childPrice) * $days * $validated['room_count'];

        // 3. 保存
        $reservationData = [
            'user_id'       => auth()->id(), // ゲストユーザーの場合は NULL
            'plan_id'       => $validated['plan_id'],
            'room_id'       => $validated['room_id'],
            'checkin_date'  => $checkinDate,
            'checkout_date' => $checkoutDate,
            'guest_count'   => $validated['adult_count'] + $validated['child_count'],
            'total_price'   => $totalPrice,
            'status'        => 'confirmed',
            'payment_method' => $validated['payment_method'],
        ];

        // ゲストユーザーの場合、個人情報も保存
        if (!auth()->check()) {
            $reservationData['guest_name'] = $validated['last_name'] . ' ' . $validated['first_name'];
            $reservationData['guest_name_kana'] = $validated['last_name_kana'] . ' ' . $validated['first_name_kana'];
            $reservationData['guest_tel'] = $validated['tel'];
            $reservationData['guest_email'] = $validated['email'];
            $reservationData['guest_zip_code'] = $validated['zip_code'];
            $reservationData['guest_address'] = $validated['address'];
            $reservationData['guest_building'] = $validated['building'] ?? '';
        }

        Reservation::create($reservationData);

        // 4. 部屋在庫を減らす
        DB::transaction(function () use ($request, $checkinDate, $checkoutDate) {
            // 宿泊期間（チェックアウト日は含まない）の各日付を取得
            $period = new \DatePeriod(
                new \DateTime($checkinDate),
                new \DateInterval('P1D'),
                new \DateTime($checkoutDate)
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

        return redirect()->route('reservations.thanks')->with('success', 'ご予約が完了いたしました。');

    }
}
