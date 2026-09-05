<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReservationPaymentIntentController;
use App\Http\Controllers\ReservationQuoteController;
use App\Http\Controllers\ReservationStayCalendarController;
use App\Http\Controllers\RoomPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('top');

Route::get('/rooms', [RoomPageController::class, 'index'])->name('rooms');
Route::get('/rooms/{room}', [RoomPageController::class, 'show'])->name('rooms.show');

Route::post('/reservations/quote', [ReservationQuoteController::class, '__invoke'])->name('reservations.quote');
Route::get('/reservations/stay-calendar', ReservationStayCalendarController::class)->name('reservations.stay-calendar');
Route::get('/reservations/pricing-meta', [ReservationQuoteController::class, 'meta'])->name('reservations.pricing-meta');
Route::post('/reservations/payment-intent', ReservationPaymentIntentController::class)->name('reservations.payment-intent');

Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
Route::match(['get', 'post'], '/reservations/details', [ReservationController::class, 'details'])->name('reservations.details');
Route::match(['get', 'post'], '/reservations/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
Route::post('/reservations', [ReservationController::class, 'store'])
    ->middleware('throttle:reservations')
    ->name('reservations.store');
Route::get('/reservations/thanks', [ReservationController::class, 'thanks'])->name('reservations.thanks');
Route::middleware('auth')->group(function () {
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
});

Route::get('/onsen', [ContentController::class, 'onsen'])->name('onsen');
Route::get('/food', [ContentController::class, 'food'])->name('food');
Route::get('/sightseeing', [ContentController::class, 'sightseeing'])->name('sightseeing');
Route::get('/news', [ContentController::class, 'news'])->name('news');
Route::get('/news/{news}', [ContentController::class, 'newsShow'])->name('news.show');
Route::get('/access', [ContentController::class, 'access'])->name('access');
Route::get('/company', [ContentController::class, 'company'])->name('company');
Route::get('/faq', [ContentController::class, 'faq'])->name('faq');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])
    ->middleware('throttle:contact')
    ->name('contact.send');
Route::get('/contact/thanks', [ContactController::class, 'thanks'])->name('contact.thanks');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
