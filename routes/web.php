<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ContactController;
use Illuminate\Http\Request;
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
        'rooms' => App\Models\Room::all()
    ]);
})->name('rooms');

// 2. お部屋詳細ページ
Route::get('/rooms/{room}', function (App\Models\Room $room) {
    return Inertia::render('Rooms/Show', [
        // plans も一緒に読み込む設定
        'room' => $room->load('plans')
    ]);
})->name('rooms.show');

// ① プラン選択画面（既存）
Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
// ② 予約内容詳細画面（プラン詳細や人数入力・内訳）
Route::post('/reservations/details', [ReservationController::class, 'details'])->name('reservations.details');
// ③ 最終確認画面
Route::post('/reservations/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
// ④ 予約実行 ＆ 完了画面表示
Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
Route::get('/reservations/thanks', function() {
    return Inertia::render('Reservations/Thanks');
})->name('reservations.thanks');

Route::get('/onsen', function () { return Inertia::render('Onsen'); })->name('onsen');
Route::get('/food', function () { return Inertia::render('Food'); })->name('food');
Route::get('/sightseeing', function () { return Inertia::render('Sightseeing'); })->name('sightseeing');
Route::get('/news', function () { return Inertia::render('News/Index'); })->name('news');
Route::get('/access', function () { return Inertia::render('Access'); })->name('access');
Route::get('/company', function () { return Inertia::render('Company'); })->name('company');
Route::get('/faq', function () { return Inertia::render('Faq'); })->name('faq');

// 19行目〜20行目をこれに書き換えてください
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', 'App\Http\Controllers\ContactController@send')->name('contact.send');

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';
