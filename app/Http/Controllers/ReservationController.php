<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Plan;
use App\Models\PricingCancelRule;
use App\Models\PricingOptionFee;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomInventory;
use App\Services\IntegrationWebhookDispatcher;
use App\Services\ReservationCancellationService;
use App\Services\ReservationPricingService;
use App\Services\SharedReservationOccupancyService;
use App\Services\StripePaymentService;
use App\Support\PlanChoiceOptions;
use App\Support\PersonName;
use App\Support\ReservationPaymentPayload;
use App\Support\UserIntegrationPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Throwable;

class ReservationController extends Controller
{
    public function create(Request $request)
    {
        $checkin = $request->query('checkin');
        $checkout = $request->query('checkout');
        $roomCount = $request->query('room_count', 1);

        $rooms = Room::with(['plans'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($room) use ($checkin, $checkout) {
                $minStock = null;

                if ($checkin && $checkout) {
                    $from = Carbon::parse($checkin)->startOfDay();
                    $to = Carbon::parse($checkout)->startOfDay();

                    if ($to->lte($from)) {
                        $minStock = 0;
                    } else {
                        $lastNight = $to->copy()->subDay()->toDateString();
                        $nights = $from->diffInDays($to);

                        $byDate = RoomInventory::query()
                            ->where('room_id', $room->id)
                            ->onDateRange($from->toDateString(), $lastNight)
                            ->get()
                            ->groupBy(fn (RoomInventory $inventory) => $inventory->dateString())
                            ->map(fn ($rows) => (int) $rows->min('remains'));

                        $minStock = $byDate->count() < $nights ? 0 : (int) $byDate->min();
                    }
                }

                $room->current_inventory = $minStock;

                return $room;
            });

        return Inertia::render('Reservations/Create', [
            'rooms' => $rooms,
            'selectedRoomId' => $request->query('room_id'),
            'searchParams' => [
                'checkin' => $checkin ?: '',
                'checkout' => $checkout ?: '',
                'adults' => (int) $request->query('adults', 2),
                'children' => (int) $request->query('children', 0),
                'room_count' => (int) $roomCount,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $reservations = Reservation::with(['plan', 'room'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (Reservation $reservation) {
                $reservation->setAttribute(
                    'can_cancel_without_fee',
                    $reservation->status !== 'cancelled'
                        && PricingCancelRule::allowsFreeCancellation($reservation->checkin_date),
                );

                return $reservation;
            });

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
            'cancelPolicy' => PricingCancelRule::activeDisplayTexts(),
        ]);
    }

    public function cancel(Request $request, Reservation $reservation, ReservationCancellationService $canceller)
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        try {
            $canceller->cancelByGuest($reservation);
        } catch (Throwable $e) {
            return redirect()
                ->route('reservations.index')
                ->withErrors(['cancel' => $e->getMessage()]);
        }

        return redirect()
            ->route('reservations.index')
            ->with('success', 'ご予約をキャンセルしました。');
    }

    public function details(Request $request)
    {
        $input = $request->except(['_token']);

        if (blank($input['plan_id'] ?? null) || blank($input['room_id'] ?? null)) {
            $saved = $request->session()->get('reservation_confirm');
            if (is_array($saved)) {
                $input = $saved;
            }
        }

        if ($request->user()) {
            foreach (PersonName::guestFieldsFromUser($request->user()) as $key => $value) {
                if (blank($input[$key] ?? null) && $value !== '') {
                    $input[$key] = $value;
                }
            }
        }

        return Inertia::render('Reservations/Details', [
            'input' => $input,
            'room' => Room::find($input['room_id'] ?? null),
            'plan' => Plan::find($input['plan_id'] ?? null),
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
            if ($request->user()) {
                foreach (PersonName::guestFieldsFromUser($request->user()) as $key => $value) {
                    if (blank($input[$key] ?? null) && $value !== '') {
                        $input[$key] = $value;
                    }
                }
            }
            $request->session()->put('reservation_confirm', $input);
        } else {
            $input = $request->session()->get('reservation_confirm');

            if (! is_array($input) || blank($input['plan_id'] ?? null) || blank($input['room_id'] ?? null)) {
                return redirect()
                    ->route('reservations.create')
                    ->withErrors(['reservation' => '予約内容の確認セッションが切れました。最初からやり直してください。']);
            }

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
            'stripeConfigured' => app(StripePaymentService::class)->isConfigured(),
        ]);
    }

    public function store(StoreReservationRequest $request, ReservationPricingService $pricing, StripePaymentService $stripe)
    {
        $validated = $request->validated();

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
                Log::warning('Reservation payment authorization failed.', [
                    'plan_id' => $validated['plan_id'],
                    'room_id' => $validated['room_id'],
                    'exception' => $e::class,
                ]);

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
            'guest_name' => trim(($validated['last_name'] ?? '').' '.($validated['first_name'] ?? ''))
                ?: (auth()->user()?->name),
            'guest_name_kana' => trim(($validated['last_name_kana'] ?? '').' '.($validated['first_name_kana'] ?? ''))
                ?: (auth()->user()?->name_kana),
            'guest_tel' => $validated['tel'] ?? null,
            'guest_email' => $validated['email'] ?? auth()->user()?->email,
            'guest_zip_code' => $validated['zip_code'] ?? auth()->user()?->zip_code,
            'guest_address' => $validated['address'] ?? auth()->user()?->address,
            'guest_building' => $validated['building'] ?? '',
        ];

        if (Schema::hasColumn('reservations', 'source')) {
            $reservationData['source'] = 'kuturogi';
        }

        if (Schema::hasColumn('reservations', 'stay_status')) {
            $reservationData['stay_status'] = 'reserved';
        }

        if (Schema::hasTable('customers') && ! empty($reservationData['guest_email'])) {
            $reservationData['customer_id'] = DB::table('customers')
                ->where('email', $reservationData['guest_email'])
                ->value('id');
        }

        try {
            $reservation = DB::transaction(function () use ($checkinDate, $checkoutDate, $reservationData) {
                $occupancy = app(SharedReservationOccupancyService::class);
                $period = new \DatePeriod(
                    new \DateTime($checkinDate),
                    new \DateInterval('P1D'),
                    new \DateTime($checkoutDate)
                );

                foreach ($period as $date) {
                    $formattedDate = $date->format('Y-m-d');

                    $inventory = RoomInventory::where('room_id', $reservationData['room_id'])
                        ->onDate($formattedDate)
                        ->lockForUpdate()
                        ->first();

                    if (! $inventory || $inventory->remains < $reservationData['room_count']) {
                        throw new \Exception($formattedDate.'は満室です。');
                    }

                    if (! $occupancy->enabled()) {
                        $inventory->decrement('remains', $reservationData['room_count']);
                    }
                }

                $created = Reservation::create($reservationData);

                if (Schema::hasColumn('reservations', 'kuturogi_reservation_id')) {
                    $created->update(['kuturogi_reservation_id' => $created->id]);
                }

                if ($occupancy->enabled()) {
                    $occupancy->assign($created);
                }

                return $created->fresh();
            });
        } catch (Throwable $e) {
            Log::warning('Reservation store failed.', [
                'room_id' => $reservationData['room_id'] ?? null,
                'plan_id' => $reservationData['plan_id'] ?? null,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('reservations.confirm')
                ->withErrors(['dates' => $e->getMessage()]);
        }

        if ($reservation->user_id && $request->user()) {
            app(IntegrationWebhookDispatcher::class)->dispatch(
                'user.registered',
                UserIntegrationPayload::from($request->user()),
            );
        }

        app(IntegrationWebhookDispatcher::class)->dispatch('reservation.created', [
            'id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'room_id' => $reservation->room_id,
            'plan_id' => $reservation->plan_id,
            'checkin_date' => $reservation->checkin_date?->toDateString(),
            'checkout_date' => $reservation->checkout_date?->toDateString(),
            'guest_count' => $reservation->guest_count,
            'room_count' => $roomCount,
            'adult_count' => $validated['adult_count'],
            'child_count' => $validated['child_count'],
            'total_price' => $reservation->total_price,
            'status' => $reservation->status,
            'guest_name' => $reservation->guest_name,
            'guest_name_kana' => $reservation->guest_name_kana,
            'guest_tel' => $reservation->guest_tel,
            'guest_email' => $reservation->guest_email,
            'selected_choices' => $reservation->selected_choices,
            'selected_option_fees' => $reservation->selected_option_fees,
            'representatives' => $representatives,
            ...ReservationPaymentPayload::from($reservation),
        ]);

        $request->session()->forget('reservation_confirm');

        return redirect()->route('reservations.thanks')->with('success', 'ご予約が完了いたしました。');
    }

    public function thanks()
    {
        return Inertia::render('Reservations/Thanks');
    }
}
