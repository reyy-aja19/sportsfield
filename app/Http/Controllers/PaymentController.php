<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\BookingConfirmedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = filter_var(config('services.midtrans.is_production', false), FILTER_VALIDATE_BOOLEAN);
        Config::$isSanitized = filter_var(config('services.midtrans.is_sanitized', true), FILTER_VALIDATE_BOOLEAN);
        Config::$is3ds = filter_var(config('services.midtrans.is_3ds', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function index()
    {
        $payments = Booking::with(['user', 'lapangan'])->latest()->get();
        return view('admin.payments', compact('payments'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => $request->status]);
        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui');
    }

    public function verify($id)
    {
        $booking = Booking::findOrFail($id);
        
        if (strtoupper($booking->status) !== 'LUNAS') {
            $booking->update(['status' => 'Lunas']);

            // Menggunakan User::find agar lebih aman dibanding relasi
            if (!empty($booking->user_id)) {
                $user = User::find($booking->user_id);
                if ($user) {
                    $user->increment('points', 5);
                    Log::info("Manual Verify: 5 points added to User ID: {$user->id}");
                } else {
                    Log::error("Manual Verify Gagal: User ID {$booking->user_id} tidak ditemukan.");
                }
            }
        }

        try {
            $user = User::find($booking->user_id);
            if ($user && $user->email) {
                Mail::to($user->email)->send(new BookingConfirmedMail($booking));
            }
        } catch (\Exception $e) {
            Log::error('Mail error via Manual Verification: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi & poin ditambahkan!');
    }

    public function apiAllBookings()
    {
        try {
            $bookings = Booking::with(['user', 'lapangan'])->latest()->get();
            return response()->json(['status' => true, 'data' => $bookings], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function createSnapToken(Request $request)
    {
        $bookingId = $request->input('booking_id');
        $booking = Booking::with(['user', 'lapangan'])->find($bookingId);

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking tidak ditemukan'], 404);
        }

        $orderId = 'BOOK-' . time() . '-' . $booking->id;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name ?? 'Customer',
                'email' => $booking->user->email ?? 'customer@mail.com',
            ],
            'item_details' => [
                [
                    'id' => $booking->lapangan_id,
                    'price' => (int) $booking->total_price,
                    'quantity' => 1,
                    'name' => $booking->lapangan->nama_lapangan ?? 'Sewa Lapangan',
                ]
            ]
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json([
                'status' => true,
                'snap_token' => $snapToken,
                'redirect_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/" . $snapToken
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $orderIdRaw = $request->input('order_id');
        $transactionStatus = $request->input('transaction_status');

        if (!$orderIdRaw) {
            return response()->json(['success' => true, 'message' => 'Notification bypass sukses.'], 200);
        }

        $parts = explode('-', $orderIdRaw);
        $cleanId = preg_replace('/[^0-9]/', '', end($parts));
        $booking = Booking::find($cleanId);

        if (!$booking || strtoupper($booking->status) === 'LUNAS') {
            return response()->json(['success' => true, 'message' => 'Sudah lunas atau tidak ditemukan.'], 200);
        }

        if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
            $booking->update(['status' => 'Lunas']);

            // Tambah Poin secara langsung menggunakan user_id dari booking
            if (!empty($booking->user_id)) {
                $user = User::find($booking->user_id);
                if ($user) {
                    $user->increment('points', 5);
                    Log::info("Webhook Poin: 5 points added to User ID: {$user->id}");
                }
            }

            try {
                $user = User::find($booking->user_id);
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new BookingConfirmedMail($booking));
                }
            } catch (\Exception $e) {
                Log::error('Gagal kirim email: ' . $e->getMessage());
            }
        } elseif ($transactionStatus == 'pending') {
            $booking->update(['status' => 'Pending']);
        } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'])) {
            $booking->update(['status' => 'Batal']);
        }

        return response()->json(['success' => true, 'message' => 'Status transaksi diproses.'], 200);
    }
}