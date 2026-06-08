<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Mail\BookingConfirmedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index()
    {
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
        $booking = \App\Models\Booking::with(['user', 'lapangan'])->findOrFail($id);

        $booking->update([
            'status' => 'Lunas'
        ]);

        try {
            if ($booking->user?->email) {
                Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
            }
        } catch (\Exception $e) {
            Log::error('Mail error: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi!');
    }

    public function paymentSuccess(Request $request)
    {
        // 1. PENGAMAN FITUR PING / TEST NOTIFICATION URL MIDTRANS
        if (!$request->has('order_id')) {
            return response()->json([
                'status' => true,
                'message' => 'Endpoint Sportsfield siap menerima webhook!'
            ], 200);
        }

        // 2. AMBIL DATA DARI WEBHOOK MIDTRANS
        $orderId = $request->input('order_id');
        $transactionStatus = $request->input('transaction_status');

        // Cari data booking berdasarkan order_id (atau ID utama kamu yang didaftarkan ke Midtrans)
        // Catatan: Jika order_id di Midtrans berupa string custom, sesuaikan pencariannya (misal: where('id', $orderId))
        $booking = Booking::with(['user', 'lapangan'])->where('id', $orderId)->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Data booking tidak ditemukan untuk Order ID: ' . $orderId
            ], 404);
        }

        // 3. LOGIC UPDATE STATUS OTOMATIS BERDASARKAN MIDTRANS
        if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
            $booking->update([
                'status' => 'Lunas' // Otomatis terbaca di tab "Aktif" Flutter kamu!
            ]);

            // Kirim notifikasi email konfirmasi ke customer
            try {
                if ($booking->user?->email) {
                    Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
                }
            } catch (\Exception $e) {
                Log::error('Mail error via Midtrans Webhook: ' . $e->getMessage());
            }
        } elseif ($transactionStatus == 'expire' || $transactionStatus == 'cancel' || $transactionStatus == 'deny') {
            $booking->update([
                'status' => 'Batal'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status transaksi ' . $orderId . ' berhasil diproses sebagai ' . $transactionStatus
        ], 200);
    }
}