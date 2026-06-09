<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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

    /**
     * TAMPILAN WEB ADMIN: Menampilkan data pembayaran di dashboard web
     */
    public function index()
    {
        $payments = Booking::with(['user', 'lapangan'])->latest()->get();
        return view('admin.payments', compact('payments'));
    }

    /**
     * WEB ADMIN: Update status manual jika admin ingin mengubah dari dashboard
     */
    public function updateStatus(Request $request, int $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => $request->status]);
        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui');
    }

    /**
     * WEB ADMIN: Tombol verifikasi cepat di Web Admin
     */
    public function verify($id)
    {
        $booking = Booking::with(['user', 'lapangan'])->findOrFail($id);
        $booking->update(['status' => 'Lunas']);

        try {
            if ($booking->user?->email) {
                Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
            }
        } catch (\Exception $e) {
            Log::error('Mail error via Manual Verification: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi!');
    }

    /**
     * API FLUTTER: Digunakan oleh Flutter Admin / History
     */
    public function apiAllBookings()
    {
        try {
            $bookings = Booking::with(['user', 'lapangan'])->latest()->get();
            return response()->json(['status' => true, 'data' => $bookings], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API FLUTTER: Pembuat Snap Token
     */
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

    /**
     * WEBHOOK MIDTRANS: Penerima laporan otomatis dari Midtrans
     */
    public function paymentSuccess(Request $request)
    {
        $orderIdRaw = $request->input('order_id');
        $transactionStatus = $request->input('transaction_status');
        $paymentType = $request->input('payment_type');

        if (!$orderIdRaw) {
            return response()->json(['success' => true, 'message' => 'Notification bypass sukses.'], 200);
        }

        Log::info('--- MIDTRANS WEBHOOK INCOMING ---');
        Log::info("Order ID Mentah: $orderIdRaw | Status Transaksi: $transactionStatus | Tipe: $paymentType");

        if (str_contains($orderIdRaw, 'payment_notif_test')) {
            Log::info('Deteksi Otomatis: Request ini adalah testing/ping dari sistem Midtrans. Bypass Aktif.');
            return response()->json(['success' => true, 'message' => 'Ping webhook berhasil!'], 200);
        }

        // AMBIL ID MENGGUNAKAN INDEKS MANUAL (Aman dari bug Docker)
        $parts = explode('-', $orderIdRaw);
        $cleanId = '';
        
        if (count($parts) > 0) {
            $lastPart = $parts[count($parts) - 1]; 
            $cleanId = preg_replace('/[^0-9]/', '', $lastPart);
        }
        
        if (empty($cleanId)) {
            Log::error("Gagal mengekstrak ID valid dari Order ID: $orderIdRaw");
            return response()->json(['success' => false, 'message' => 'Format Order ID salah'], 400);
        }

        $booking = Booking::with(['user', 'lapangan'])->find($cleanId);

        if (!$booking) {
            Log::error("Data Booking dengan ID $cleanId tidak ditemukan.");
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 200);
        }

        // SYSTEM LOCK: Jika status sudah Lunas, kunci data dari overwrite webhook pending susulan
        if (strtoupper($booking->status) === 'LUNAS') {
            Log::info("Booking ID $cleanId sudah berstatus LUNAS. Mengabaikan status susulan: $transactionStatus");
            return response()->json(['success' => true, 'message' => 'Status sudah lunas, perubahan diabaikan.'], 200);
        }

        // PROSES PERUBAHAN STATUS DATA
        if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
            $booking->update(['status' => 'Lunas']);
            Log::info("Booking ID $cleanId BERHASIL DIUBAH MENJADI LUNAS.");

            try {
                if ($booking->user?->email) {
                    Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
                }
            } catch (\Exception $e) {
                Log::error('Gagal kirim email invoice: ' . $e->getMessage());
            }

        } elseif ($transactionStatus == 'pending') {
            $booking->update(['status' => 'Pending']);
            Log::info("Booking ID $cleanId menunggu pembayaran.");

        } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'])) {
            $booking->update(['status' => 'Batal']);
            Log::info("Booking ID $cleanId diubah menjadi Batal.");
        }

        return response()->json(['success' => true, 'message' => 'Status transaksi diproses.'], 200);
    }
}