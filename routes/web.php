<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ReservationController;

Route::get('/', function () {
    return Inertia::render('Top', [
        // 最新の3件を取得
        'latestNews' => App\Models\News::latest('published_at')->take(3)->get()
    ]);
})->name('top');

// 1. お部屋一覧ページ (これが抜けているか、URLが間違っている可能性があります)
Route::get('/rooms', function () {
    return Inertia::render('Rooms/Index', [
        'rooms' => App\Models\Room::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
    ]);
})->name('rooms');

// 2. お部屋詳細ページ
Route::get('/rooms/{room}', function (App\Models\Room $room) {
    abort_unless($room->is_active, 404);

    return Inertia::render('Rooms/Show', [
        // plans も一緒に読み込む設定
        'room' => $room->load('plans')
    ]);
})->name('rooms.show');

Route::post('/reservations/quote', [\App\Http\Controllers\ReservationQuoteController::class, '__invoke'])->name('reservations.quote');
Route::get('/reservations/pricing-meta', [\App\Http\Controllers\ReservationQuoteController::class, 'meta'])->name('reservations.pricing-meta');
Route::post('/reservations/payment-intent', [\App\Http\Controllers\ReservationPaymentIntentController::class, '__invoke'])->name('reservations.payment-intent');

// ① プラン選択画面（既存）
Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
// ② 予約内容詳細画面（プラン詳細や人数入力・内訳）
Route::match(['get', 'post'], '/reservations/details', [ReservationController::class, 'details'])->name('reservations.details');
// ③ 最終確認画面（GET: 再表示 / Stripe return_url 用。POST: Details からの遷移）
Route::match(['get', 'post'], '/reservations/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
// ④ 予約実行 ＆ 完了画面表示
Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
Route::middleware('auth')->group(function () {
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
});
Route::get('/reservations/thanks', function() {
    return Inertia::render('Reservations/Thanks');
})->name('reservations.thanks');

Route::get('/onsen', function () { return Inertia::render('Onsen'); })->name('onsen');
Route::get('/food', function () { return Inertia::render('Food'); })->name('food');
Route::get('/sightseeing', function () { return Inertia::render('Sightseeing'); })->name('sightseeing');
Route::get('/news', function () { 
    return Inertia::render('News/Index', [
        'news' => App\Models\News::latest('published_at')->get()
    ]); 
})->name('news');
Route::get('/access', function () { return Inertia::render('Access'); })->name('access');
Route::get('/company', function () { return Inertia::render('Company'); })->name('company');
Route::get('/faq', function () { return Inertia::render('Faq'); })->name('faq');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', 'App\Http\Controllers\ContactController@send')->name('contact.send');

Route::middleware('auth')->group(function () {
    // 会員情報の確認・変更画面（GET）
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    // 会員情報の更新処理（PATCH または PUT）
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
});

require __DIR__.'/auth.php';
