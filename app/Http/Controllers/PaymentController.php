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
        // Membaca konfigurasi dari config/services.php
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
     * API FLUTTER: Pembuat Snap Token (Dipanggil sebelum user masuk ke halaman Midtrans Snap WebView)
     */
    public function createSnapToken(Request $request)
    {
        $bookingId = $request->input('booking_id');
        $booking = Booking::with(['user', 'lapangan'])->find($bookingId);

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking tidak ditemukan'], 404);
        }

        // Format order_id disamakan dengan kebutuhan Flutter kamu
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
     * WEBHOOK MIDTRANS: Penerima laporan otomatis dari Midtrans (Ubah Status ke Lunas)
     */
    public function paymentSuccess(Request $request)
    {
        $orderIdRaw = $request->input('order_id');
        $transactionStatus = $request->input('transaction_status');
        $paymentType = $request->input('payment_type');

        // 1. Proteksi awal jika data order_id kosong
        if (!$orderIdRaw) {
            return response()->json(['success' => true, 'message' => 'Notification bypass sukses.'], 200);
        }

        Log::info('--- MIDTRANS WEBHOOK INCOMING ---');
        Log::info("Order ID Mentah: $orderIdRaw | Status Transaksi: $transactionStatus | Tipe: $paymentType");

        // 2. CEK DATA SIMULASI/TESTING: Jika ini merupakan request uji coba dari sistem internal Midtrans Dashboard
        if (str_contains($orderIdRaw, 'payment_notif_test')) {
            Log::info('Deteksi Otomatis: Request ini adalah testing/ping dari sistem Midtrans. Bypass Aktif.');
            return response()->json([
                'success' => true,
                'message' => 'Ping webhook berhasil diterima dengan baik!'
            ], 200);
        }

        // 3. PROSES DATA TRANSAKSI REAL (Dari Aplikasi Flutter)
        // Memotong format "BOOK-TIMESTAMP-ID" (Contoh: BOOK-1780962219-90)
        $parts = explode('-', $orderIdRaw);
        
        // Mengambil elemen paling terakhir dari hasil potongan array (yaitu ID database asli)
        $lastPart = end($parts);
        $cleanId = preg_replace('/[^0-9]/', '', $lastPart);
        
        if (empty($cleanId)) {
            Log::error("Gagal mengekstrak ID valid dari Order ID: $orderIdRaw");
            return response()->json(['success' => false, 'message' => 'Format Order ID salah'], 400);
        }

        $booking = Booking::with(['user', 'lapangan'])->find($cleanId);

        if (!$booking) {
            Log::error("Data Booking dengan ID $cleanId tidak ditemukan di database.");
            // Tetap berikan respon 200 jika data tidak ada di DB uji coba agar Midtrans menghentikan perulangan kirim
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan namun webhook dicatat'], 200);
        }

        Log::info('Data Booking Real Ditemukan. Nama User: ' . ($booking->user->name ?? 'Guest') . ' | Status Saat Ini: ' . $booking->status);

        // KONDISI UTAMA: JIKA PEMBAYARAN FULL BERHASIL -> LANGSUNG SET KE LUNAS
        if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
            
            $booking->update([
                'status' => 'Lunas'
            ]);

            Log::info("Booking ID $cleanId untuk user " . ($booking->user->name ?? '') . " BERHASIL DIUBAH MENJADI LUNAS.");

            // Kirim email konfirmasi invoice otomatis ke customer
            try {
                if ($booking->user?->email) {
                    Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
                }
            } catch (\Exception $e) {
                Log::error('Gagal kirim email invoice: ' . $e->getMessage());
            }

        } elseif ($transactionStatus == 'pending') {
            $booking->update([
                'status' => 'Pending'
            ]);
            Log::info("Booking ID $cleanId menunggu pembayaran dari user.");

        } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'])) {
            $booking->update([
                'status' => 'Batal'
            ]);
            Log::info("Booking ID $cleanId otomatis diubah menjadi Batal.");
        }

        return response()->json([
            'success' => true, 
            'message' => 'Status transaksi ' . $booking->id . ' untuk ' . ($booking->user->name ?? 'User') . ' berhasil diproses sebagai ' . $transactionStatus
        ], 200);
    }
}