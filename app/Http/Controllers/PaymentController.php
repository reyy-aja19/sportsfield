<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Mail\BookingConfirmedMail;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    public function index()
    {
        // Kita ambil data Booking, bukan Payment, karena data mobile masuk ke sini
        $payments = \App\Models\Booking::with(['user', 'lapangan'])->latest()->get();

        return view('admin.payments', compact('payments'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update([
            'status' => $request->status
        ]);

        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui');
    }

    public function apiAllBookings()
    {
        try {
            $bookings = \App\Models\Booking::with(['user', 'lapangan'])->latest()->get();

            return response()->json([
                'status' => true,
                'data' => $bookings
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

   public function verify($id)
{
    $booking = \App\Models\Booking::with([
        'user',
        'lapangan'
    ])->findOrFail($id);

    $booking->update([
        'status' => 'Lunas'
    ]);

    // kirim email
    try {

        if ($booking->user?->email) {

            Mail::to($booking->user->email)
                ->send(
                    new BookingConfirmedMail($booking)
                );
        }

    } catch (\Exception $e) {

        \Log::error(
            'Mail error: ' . $e->getMessage()
        );
    }

    return redirect()
        ->back()
        ->with(
            'success',
            'Pembayaran berhasil diverifikasi!'
        );
}

   public function paymentSuccess(Request $request)
{
    $booking = Booking::with([
        'user',
        'lapangan'
    ])->findOrFail($request->booking_id);

    $booking->update([
        'status' => 'Lunas'
    ]);

    try {

        if ($booking->user?->email) {

            Mail::to($booking->user->email)
                ->send(
                    new BookingConfirmedMail($booking)
                );
        }

    } catch (\Exception $e) {

        \Log::error(
            'Mail error: ' . $e->getMessage()
        );
    }

    return response()->json([
        'success' => true,
        'message' => 'Pembayaran berhasil'
    ]);
}
}
