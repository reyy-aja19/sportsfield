<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Mail\BookingConfirmedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Menampilkan data pembayaran di halaman Admin Web
     */
    public function index()
    {
        $payments = Booking::with(['user', 'lapangan'])->latest()->get();
        return view('admin.payments', compact('payments'));
    }

    /**
     * Update status manual dari dashboard admin web
     */
    public function updateStatus(Request $request, int $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update([
            'status' => $request->status
        ]);

        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui');
    }

    /**
     * API untuk mengambil semua data booking (Digunakan oleh Flutter/Admin)
     */
    public function apiAllBookings()
    {
        try {
            $bookings = Booking::with(['user', 'lapangan'])->latest()->get();

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

    /**
     * Verifikasi pembayaran manual oleh admin (Tombol di Web Admin)
     */
    public function verify($id)
    {
        $booking = Booking::with(['user', 'lapangan'])->findOrFail($id);

        $booking->update([
            'status' => 'Lunas'
        ]);

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
     * WEBHOOK/CALLBACK dari Midtrans (Otomatis mengubah status ke 'Lunas' saat user selesai bayar)
     */
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
        $orderIdRaw = $request->input('order_id');
        $transactionStatus = $request->input('transaction_status');

        // [LOGGING] Mencatat data yang masuk dari Midtrans ke file storage/logs/laravel.log untuk pelacakan jika pending terus
        Log::info('--- MIDTRANS WEBHOOK INCOMING ---');
        Log::info('Order ID Mentah dari Midtrans: ' . $orderIdRaw);
        Log::info('Status Transaksi dari Midtrans: ' . $transactionStatus);

        // 3. STRATEGI PENYELARASAN ID DATABASE
        // Jika order_id berbentuk string seperti "BOOK-1", "1-timestamp", atau teks gabungan lainnya,
        // baris di bawah ini membuang semua karakter huruf dan menyisakan angka murninya saja (yaitu ID "1")
        $cleanId = preg_replace('/[^0-9]/', '', explode('-', $orderIdRaw)[0]); 

        // Cari data booking berdasarkan ID yang telah dibersihkan
        $booking = Booking::with(['user', 'lapangan'])->where('id', $cleanId)->first();

        if (!$booking) {
            Log::error('Webhook Gagal: Data booking tidak ditemukan di database untuk ID: ' . $cleanId);
            return response()->json([
                'success' => false,
                'message' => 'Data booking tidak ditemukan untuk Order ID: ' . $cleanId
            ], 404);
        }

        Log::info('Data Booking Ditemukan. Status saat ini di database: ' . $booking->status);

        // 4. LOGIKA EKSEKUSI PERUBAHAN STATUS DATABASE BERDASARKAN MIDTRANS
        if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
            
            // Set ke 'Lunas' agar sinkron dengan visual di web admin dan filter .toLowerCase() di Flutter
            $booking->update([
                'status' => 'Lunas' 
            ]);

            Log::info('Sukses Update Status! Booking ID ' . $cleanId . ' sekarang berstatus: Lunas');

            // Kirim notifikasi invoice email ke pelanggan secara otomatis
            try {
                if ($booking->user?->email) {
                    Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
                    Log::info('Email konfirmasi invoice sukses dikirim ke: ' . $booking->user->email);
                }
            } catch (\Exception $e) {
                Log::error('Gagal mengirim email konfirmasi via Webhook: ' . $e->getMessage());
            }

        } elseif ($transactionStatus == 'expire' || $transactionStatus == 'cancel' || $transactionStatus == 'deny') {
            
            $booking->update([
                'status' => 'Batal'
            ]);
            Log::info('Booking ID ' . $cleanId . ' otomatis diubah menjadi Batal oleh sistem.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Status transaksi ' . $orderIdRaw . ' berhasil diproses sebagai ' . $transactionStatus
        ], 200);
    }
}