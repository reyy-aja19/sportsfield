<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

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
        $booking = \App\Models\Booking::findOrFail($id);

        $booking->update([
            'status' => 'Lunas' // Sesuaikan dengan enum status lu
        ]);

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi!');
    }
}
