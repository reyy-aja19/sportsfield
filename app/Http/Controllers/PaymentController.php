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
        // Membaca konfigurasi dari config/services.php (Aman dari config:cache & VS Code Warning hilang)
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

        // Format order_id: IDDATABASE-TIMESTAMP (Supaya Midtrans tidak duplicate order ID)
        $orderId = $booking->id . '-' . time();

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

        // Proteksi jika hanya berupa tes ping dari dashboard Midtrans
        if (!$orderIdRaw) {
            return response()->json(['status' => true, 'message' => 'Reset Webhook Berhasil!'], 200);
        }

        Log::info('--- MIDTRANS WEBHOOK INCOMING ---');
        Log::info("Order ID Mentah: $orderIdRaw | Status Transaksi: $transactionStatus | Tipe: $paymentType");

        // Memisahkan ID database dengan tanda '-' timestamp
        $cleanId = preg_replace('/[^0-9]/', '', explode('-', $orderIdRaw)[0]);
        $booking = Booking::with(['user', 'lapangan'])->find($cleanId);

        if (!$booking) {
            Log::error("Data Booking dengan ID $cleanId tidak ditemukan di database.");
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // KONDISI UTAMA: JIKA PEMBAYARAN FULL BERHASIL -> LANGSUNG SET KE LUNAS
        if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
            
            $booking->update([
                'status' => 'Lunas'
            ]);

            Log::info("Booking ID $cleanId diproses otomatis dan BERHASIL LUNAS.");

            // Kirim email konfirmasi invoice otomatis ke customer
            try {
                if ($booking->user?->email) {
                    Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
                }
            } catch (\Exception $e) {
                Log::error('Gagal kirim email invoice: ' . $e->getMessage());
            }

        } elseif ($transactionStatus == 'pending') {
            // Jika user baru membuka opsi pembayaran (belum melakukan bayar/transfer)
            $booking->update([
                'status' => 'Pending'
            ]);
            Log::info("Booking ID $cleanId menunggu pembayaran dari user.");

        } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'])) {
            // Jika pembayaran hangus atau dibatalkan oleh user
            $booking->update([
                'status' => 'Batal'
            ]);
            Log::info("Booking ID $cleanId otomatis diubah menjadi Batal.");
        }

        return response()->json(['success' => true, 'message' => 'Webhook Midtrans berhasil diproses'], 200);
    }
}