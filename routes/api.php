<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LapanganController;
use App\Http\Controllers\OpenMatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\NotificationController;

// PUBLIC
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'loginApi']);

Route::get('/fields', [LapanganController::class, 'apiIndex']);
Route::get('/fields/{id}', [LapanganController::class, 'apiShow']);

Route::get('/booked-slots', [LapanganController::class, 'getBookedSlots']);

Route::get('/admin/all-bookings', [PaymentController::class, 'apiAllBookings']);

// MIDTRANS SUCCESS
Route::post(
    '/payment-success',
    [PaymentController::class, 'paymentSuccess']
);

Route::get('/notifications/{user_id}', [NotificationController::class, 'index']);
// PRIVATE
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/rewards', [RewardController::class, 'apiIndex']);
    Route::post('/rewards/redeem', [RewardController::class, 'apiRedeem']);

    Route::get('/bookings', [LapanganController::class, 'apiBookings']);

    Route::post('/booking', [LapanganController::class, 'apiStoreBooking']);

    Route::post(
        '/booking/{id}/update-status',
        [LapanganController::class, 'apiUpdateStatus']
    );

    Route::get('/matches', [OpenMatchController::class, 'index']);

    Route::post('/matches', [OpenMatchController::class, 'store']);

    Route::post('/matches/{id}/join', [OpenMatchController::class, 'join']);
});