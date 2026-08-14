<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PricingCancelRule;
use App\Models\PricingOptionFee;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomInventory;
use App\Services\IntegrationWebhookDispatcher;
use App\Services\ReservationPricingService;
use App\Services\StripePaymentService;
use App\Support\PlanChoiceOptions;
use App\Support\ReservationPaymentPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class ReservationController extends Controller
{
    public function create(Request $request)
    {
        $checkin = $request->query('checkin');
        $checkout = $request->query('checkout');
        $roomCount = $request->query('room_count', 1);

        $rooms = Room::with(['plans'])->orderBy('sort_order')->orderBy('id')->get()->map(function ($room) use ($checkin, $checkout) {
            $minStock = null;

            if ($checkin && $checkout) {
                $lastNight = date('Y-m-d', strtotime($checkout.' -1 day'));

                $inventories = RoomInventory::where('room_id', $room->id)
                    ->whereBetween('date', [$checkin, $lastNight])
                    ->pluck('remains');

                $nights = (strtotime($checkout) - strtotime($checkin)) / 86400;

                if ($inventories->count() < $nights) {
                    $minStock = 0;
                } else {
                    $minStock = $inventories->min();
                }
            }

            $room->current_inventory = $minStock;

            return $room;
        });

        return Inertia::render('Reservations/Create', [
            'rooms' => $rooms,
            'selectedRoomId' => $request->query('room_id'),
            'searchParams' => [
                'checkin' => $checkin,
                'checkout' => $checkout,
                'room_count' => (int) $roomCount,
            ],
        ]);
    }

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

    public function details(Request $request)
    {
        return Inertia::render('Reservations/Details', [
            'input' => $request->all(),
            'room' => Room::find($request->room_id),
            'plan' => Plan::find($request->plan_id),
            'optionFees' => PricingOptionFee::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'price', 'description']),
            'cancelPolicy' => PricingCancelRule::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (PricingCancelRule $rule): string => $rule->displayText())
                ->values(),
        ]);
    }

    public function confirm(Request $request, ReservationPricingService $pricing)
    {
        if ($request->isMethod('post')) {
            $input = $request->except(['_token']);
            $request->session()->put('reservation_confirm', $input);
        } else {
            $input = $request->session()->get('reservation_confirm');

            if (! is_array($input) || blank($input['plan_id'] ?? null) || blank($input['room_id'] ?? null)) {
                return redirect()
                    ->route('reservations.create')
                    ->withErrors(['reservation' => '予約内容の確認セッションが切れました。最初からやり直してください。']);
            }

            // Stripe return_url のクエリを入力へ引き継ぎ（与信済み PI の再開用）
            foreach (['payment_intent', 'payment_intent_client_secret', 'redirect_status'] as $key) {
                if ($request->filled($key)) {
                    $input[$key] = $request->query($key);
                }
            }
        }

        $plan = Plan::findOrFail($input['plan_id']);
        $room = Room::findOrFail($input['room_id']);
        $checkinDate = $input['checkin_date'] ?? $input['check_in_date'] ?? null;
        $checkoutDate = $input['checkout_date'] ?? $input['check_out_date'] ?? null;

        $quote = $pricing->calculate(
            $room,
            $plan,
            (string) $checkinDate,
            (string) $checkoutDate,
            (int) ($input['adult_count'] ?? 0),
            (int) ($input['child_count'] ?? 0),
            (int) ($input['room_count'] ?? 1),
            $input['selected_option_ids'] ?? [],
        );

        return Inertia::render('Reservations/Confirm', [
            'input' => $input,
            'room' => $room,
            'plan' => $plan,
            'quote' => $quote,
            'totalPrice' => $quote['total'],
            'pricePerPersonPerNight' => $quote['base_per_person_per_night'],
            'nights' => $quote['nights_count'],
            'cancelPolicy' => $quote['cancel_policy'],
            'stripeKey' => config('services.stripe.key'),
            'stripeConfigured' => filled(config('services.stripe.key')) && filled(config('services.stripe.secret')),
        ]);
    }

    public function store(Request $request, ReservationPricingService $pricing, StripePaymentService $stripe)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'room_id' => 'required|exists:rooms,id',
            'checkin_date' => 'nullable|date',
            'check_in_date' => 'nullable|date',
            'checkout_date' => 'nullable|date',
            'check_out_date' => 'nullable|date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'required|integer|min:0',
            'room_count' => 'required|integer|min:1',
            'last_name' => 'required|string',
            'first_name' => 'required|string',
            'last_name_kana' => 'required|string',
            'first_name_kana' => 'required|string',
            'tel' => 'required|string',
            'email' => 'required|email',
            'zip_code' => 'required|string',
            'address' => 'required|string',
            'building' => 'nullable|string',
            'payment_method' => 'required|in:local,credit',
            'payment_intent_id' => 'nullable|string',
            'selected_choices' => 'nullable|array',
            'selected_choices.*' => 'nullable|string|max:255',
            'selected_option_ids' => 'nullable|array',
            'selected_option_ids.*' => 'integer',
            'representatives' => 'nullable|array',
            'representatives.*' => 'nullable|string|max:255',
        ], [
            'plan_id.required' => 'プランを選択してください',
            'room_id.required' => 'お部屋を選択してください',
        ]);

        $roomCount = max(1, (int) $validated['room_count']);
        $primaryName = trim(($validated['last_name'] ?? '').' '.($validated['first_name'] ?? ''));
        $representatives = [];
        for ($i = 0; $i < $roomCount; $i++) {
            $name = trim((string) ($validated['representatives'][$i] ?? ''));
            if ($name === '' && $i === 0) {
                $name = $primaryName;
            }
            if ($name === '') {
                return redirect()->back()->withErrors([
                    "representatives.$i" => ($i + 1).'室目の代表者名を入力してください',
                ])->withInput();
            }
            $representatives[] = $name;
        }

        $plan = Plan::findOrFail($validated['plan_id']);
        $room = Room::findOrFail($validated['room_id']);

        $selectedChoices = PlanChoiceOptions::validateSelections(
            $plan->choice_options,
            $validated['selected_choices'] ?? []
        );

        $checkinDate = $validated['checkin_date'] ?? $validated['check_in_date'] ?? null;
        $checkoutDate = $validated['checkout_date'] ?? $validated['check_out_date'] ?? null;

        if (! $checkinDate || ! $checkoutDate) {
            return redirect()->back()->withErrors(['dates' => 'チェックイン日とチェックアウト日を入力してください']);
        }

        $quote = $pricing->calculate(
            $room,
            $plan,
            (string) $checkinDate,
            (string) $checkoutDate,
            (int) $validated['adult_count'],
            (int) $validated['child_count'],
            $roomCount,
            $validated['selected_option_ids'] ?? [],
        );

        $paymentFields = [
            'payment_status' => Reservation::PAYMENT_UNPAID,
            'stripe_payment_intent_id' => null,
            'stripe_latest_charge_id' => null,
            'authorized_at' => null,
            'paid_at' => null,
        ];

        if ($validated['payment_method'] === 'credit') {
            if (blank($validated['payment_intent_id'] ?? null)) {
                return redirect()
                    ->route('reservations.confirm')
                    ->withErrors([
                        'payment_intent_id' => 'クレジットカードの与信が完了していません。',
                    ]);
            }

            try {
                $intent = $stripe->assertAuthorizedForAmount(
                    (string) $validated['payment_intent_id'],
                    (int) $quote['total'],
                );
            } catch (Throwable $e) {
                return redirect()
                    ->route('reservations.confirm')
                    ->withErrors([
                        'payment_intent_id' => $e->getMessage(),
                    ]);
            }

            $paymentFields = [
                'payment_status' => Reservation::PAYMENT_AUTHORIZED,
                'stripe_payment_intent_id' => $intent->id,
                'stripe_latest_charge_id' => StripePaymentService::chargeIdFromIntent($intent),
                'authorized_at' => now(),
                'paid_at' => null,
            ];
        }

        $reservationData = [
            'user_id' => auth()->id(),
            'plan_id' => $validated['plan_id'],
            'room_id' => $validated['room_id'],
            'checkin_date' => $checkinDate,
            'checkout_date' => $checkoutDate,
            'guest_count' => $validated['adult_count'] + $validated['child_count'],
            'room_count' => $roomCount,
            'adult_count' => $validated['adult_count'],
            'child_count' => $validated['child_count'],
            'total_price' => $quote['total'],
            'status' => 'confirmed',
            'payment_method' => $validated['payment_method'],
            ...$paymentFields,
            'selected_choices' => $selectedChoices !== [] ? $selectedChoices : null,
            'selected_option_fees' => $quote['selected_options'] !== [] ? $quote['selected_options'] : null,
        ];

        if (! auth()->check()) {
            $reservationData['guest_name'] = $validated['last_name'].' '.$validated['first_name'];
            $reservationData['guest_name_kana'] = $validated['last_name_kana'].' '.$validated['first_name_kana'];
            $reservationData['guest_tel'] = $validated['tel'];
            $reservationData['guest_email'] = $validated['email'];
            $reservationData['guest_zip_code'] = $validated['zip_code'];
            $reservationData['guest_address'] = $validated['address'];
            $reservationData['guest_building'] = $validated['building'] ?? '';
        }

        try {
            $reservation = DB::transaction(function () use ($checkinDate, $checkoutDate, $reservationData) {
                $period = new \DatePeriod(
                    new \DateTime($checkinDate),
                    new \DateInterval('P1D'),
                    new \DateTime($checkoutDate)
                );

                foreach ($period as $date) {
                    $formattedDate = $date->format('Y-m-d');

                    $inventory = RoomInventory::where('room_id', $reservationData['room_id'])
                        ->where('date', $formattedDate)
                        ->lockForUpdate()
                        ->first();

                    if (! $inventory || $inventory->remains < $reservationData['room_count']) {
                        throw new \Exception($formattedDate.'は満室です。');
                    }

                    $inventory->decrement('remains', $reservationData['room_count']);
                }

                return Reservation::create($reservationData);
            });
        } catch (Throwable $e) {
            return redirect()
                ->route('reservations.confirm')
                ->withErrors(['dates' => $e->getMessage()]);
        }

        $webhookGuestName = $reservation->guest_name
            ?: ($validated['last_name'].' '.$validated['first_name']);

        app(IntegrationWebhookDispatcher::class)->dispatch('reservation.created', [
            'id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'room_id' => $reservation->room_id,
            'plan_id' => $reservation->plan_id,
            'checkin_date' => $reservation->checkin_date,
            'checkout_date' => $reservation->checkout_date,
            'guest_count' => $reservation->guest_count,
            'room_count' => $roomCount,
            'adult_count' => $validated['adult_count'],
            'child_count' => $validated['child_count'],
            'total_price' => $reservation->total_price,
            'status' => $reservation->status,
            'guest_name' => $webhookGuestName,
            'guest_name_kana' => $reservation->guest_name_kana
                ?: (($validated['last_name_kana'] ?? '').' '.($validated['first_name_kana'] ?? '')),
            'guest_tel' => $reservation->guest_tel ?: ($validated['tel'] ?? null),
            'guest_email' => $reservation->guest_email ?: ($validated['email'] ?? null),
            'selected_choices' => $reservation->selected_choices,
            'selected_option_fees' => $reservation->selected_option_fees,
            'representatives' => $representatives,
            ...ReservationPaymentPayload::from($reservation),
        ]);

        $request->session()->forget('reservation_confirm');

        return redirect()->route('reservations.thanks')->with('success', 'ご予約が完了いたしました。');
    }
}
