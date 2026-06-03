<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index($user_id)
{
    $notifications = collect();

    $notifications = $notifications->merge(
        Booking::where('user_id', $user_id)
            ->where('status', 'Lunas')
            ->latest()
            ->get()
            ->map(function ($booking) {
                return [
                    'title' => 'Booking Dikonfirmasi',
                    'message' => 'Booking lapangan berhasil dikonfirmasi',
                    'time' => $booking->updated_at->diffForHumans(),
                    'type' => 'booking',
                ];
            })
    );

    $notifications = $notifications->merge(
        Payment::where('user_id', $user_id)
            ->where('status', 'Lunas')
            ->latest()
            ->get()
            ->map(function ($payment) {
                return [
                    'title' => 'Pembayaran Berhasil',
                    'message' => 'Pembayaran telah diverifikasi admin',
                    'time' => $payment->updated_at->diffForHumans(),
                    'type' => 'payment',
                ];
            })
    );

    return response()->json([
        'success' => true,
        'data' => $notifications->values()
    ]);
}
}