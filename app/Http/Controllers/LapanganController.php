<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use App\Models\Venue;
use App\Models\Facility;
use Illuminate\Http\Request;

class LapanganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lapangan = Lapangan::all();

        return view('admin.lapangan.index', [
            'lapangan' => $lapangan,
            'heading' => 'Management Lapangan'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.lapangan.create', [
            'venues' => Venue::all(),
            'facilities' => Facility::all(),
            'heading' => 'Tambah Lapangan',
            'title' => 'Tambah Lapangan'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required',
            'venue_id' => 'required',
            'jenis' => 'required',
            'lokasi' => 'required',
            'harga' => 'required',
            'rating' => 'nullable',
            'status' => 'required',
            'deskripsi' => 'nullable',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'foto_gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'fasilitas' => 'nullable|array',
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/lapangan'), $filename);
            $data['foto'] = $filename;
        }

        $gallery = [];
        if ($request->hasFile('foto_gallery')) {
            foreach ($request->file('foto_gallery') as $image) {
                $galleryName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/lapangan'), $galleryName);
                $gallery[] = $galleryName;
            }
        }

        $data['foto_gallery'] = $gallery;
        Lapangan::create($data);

        return redirect('/lapangan')->with('success', 'Data berhasil ditambah');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lapangan $lapangan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $lapangan = Lapangan::findOrFail($id);

        return view('admin.lapangan.edit', [
            'lapangan' => $lapangan,
            'venues' => Venue::all(),
            'facilities' => Facility::all(),
            'title' => 'Edit Lapangan',
            'heading' => 'Edit Lapangan'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $lapangan = Lapangan::findOrFail($id);

        $data = $request->validate([
            'nama' => 'required',
            'venue_id' => 'required',
            'jenis' => 'required',
            'lokasi' => 'required',
            'harga' => 'required',
            'rating' => 'nullable',
            'status' => 'required',
            'deskripsi' => 'nullable',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'foto_gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'fasilitas' => 'nullable|array',
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/lapangan'), $filename);
            $data['foto'] = $filename;
        }

        if ($request->hasFile('foto_gallery')) {
            $gallery = [];
            foreach ($request->file('foto_gallery') as $image) {
                $galleryName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/lapangan'), $galleryName);
                $gallery[] = $galleryName;
            }
            $data['foto_gallery'] = $gallery;
        }

        $lapangan->update($data);

        return redirect('/lapangan')->with('success', 'Data berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Lapangan::destroy($id);
        return redirect('/lapangan')->with('success', 'Data berhasil dihapus');
    }

    /*
    |--------------------------------------------------------------------------
    | API METHODS (Untuk Kebutuhan Flutter / Mobile)
    |--------------------------------------------------------------------------
    |*/

    public function apiIndex()
    {
        $lapangans = Lapangan::latest('created_at')->get();

        return response()->json([
            'status' => true,
            'message' => 'Daftar lapangan berhasil diambil',
            'data' => $lapangans
        ], 200);
    }

    public function apiShow($id)
    {
        $lapangan = Lapangan::findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Detail lapangan berhasil diambil',
            'data' => $lapangan
        ], 200);
    }

    public function getBookedSlots(Request $request)
    {
        $slots = \App\Models\Booking::select('lapangan_id', 'tanggal', 'start_time', 'end_time')
            ->whereIn('status', ['Lunas', 'Pending'])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Data slot terbooking berhasil diambil',
            'data' => $slots
        ], 200);
    }

    public function apiBookings(Request $request)
    {
        $bookings = \App\Models\Booking::with(['lapangan'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Riwayat booking user berhasil diambil',
            'data' => $bookings
        ], 200);
    }

    public function apiStoreBooking(Request $request)
    {
        // 1. Validasi Request disesuaikan dengan data yang dikirim Flutter & Kebutuhan DB
        $request->validate([
            'lapangan_id'    => 'required|integer',
            'payment_method' => 'nullable|string',
            'date'           => 'required|date',
            'start_time'     => 'required',
            'end_time'       => 'nullable|string',
            'total_price'    => 'required',
        ]);

        // Proteksi pencarian User ID (Gunakan token auth jika ada, jika tidak pakai userId manual)
        $userId = $request->user() ? $request->user()->id : ($request->userId ?? $request->user_id ?? 1);

        // 2. Simpan data booking awal ke database
        $booking = \App\Models\Booking::create([
            'user_id'        => $userId,
            'lapangan_id'    => $request->lapangan_id,
            'booking_date'   => $request->date,
            'start_time'     => $request->start_time,
            'end_time'       => $request->end_time ?? '',
            'total_price'    => $request->total_price,
            
            // ==================== DI SINI PERUBAHANNYA ====================
            // Kita langsung kunci statusnya menjadi 'Lunas' tanpa kompromi
            'status'         => 'Lunas', 
            // ==============================================================
            
            'payment_method' => $request->payment_method ?? 'Transfer Bank (VA)',
        ]);

        // Buat Order ID unik untuk dikirimkan ke Midtrans
        $orderId = 'BOOK-' . time() . '-' . $booking->id;

        // 3. Logika Midtrans jika metode pembayaran menggunakan online (bukan Tunai)
        if ($request->payment_method !== 'Tunai di Tempat') {

            // Set konfigurasi Midtrans langsung menggunakan config/services.php
            \Midtrans\Config::$serverKey    = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
            \Midtrans\Config::$isSanitized  = config('services.midtrans.is_sanitized');
            \Midtrans\Config::$is3ds        = config('services.midtrans.is_3ds');

            // Susun payload parameter transaksi untuk Midtrans
            $params = [
                'transaction_details' => [
                    'order_id'     => $orderId,
                    'gross_amount' => (int) $request->total_price,
                ],
                'item_details' => [
                    [
                        'id'       => $request->lapangan_id,
                        'price'    => (int) $request->total_price,
                        'quantity' => 1,
                        'name'     => 'Sewa Lapangan ID: ' . $request->lapangan_id,
                    ]
                ],
                'customer_details' => [
                    'first_name' => 'User ID: ' . $userId,
                ],
            ];

            try {
                // Request ke Midtrans menggunakan core method resmi SDK
                $response = \Midtrans\Snap::createTransaction($params);
                $redirectUrl = $response->redirect_url;

                return response()->json([
                    'status'         => true,
                    'message'        => 'Booking berhasil dibuat, silakan selesaikan pembayaran.',
                    'current_points' => ($request->user() ? $request->user()->points : 0) + 5,
                    'redirect_url'   => $redirectUrl,
                    'data'           => $booking
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Gagal menghubungkan ke Midtrans: ' . $e->getMessage()
                ], 500);
            }
        }

        // 4. Response jika user memilih pembayaran offline "Tunai di Tempat"
        return response()->json([
            'status'         => true,
            'message'        => 'Booking tunai berhasil dibuat.',
            'current_points' => ($request->user() ? $request->user()->points : 0) + 5,
            'data'           => $booking
        ], 201);
    }

    public function apiUpdateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $booking = \App\Models\Booking::findOrFail($id);
        $booking->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Status booking berhasil diperbarui',
            'data' => $booking
        ], 200);
    }

    /**
     * Webhook/Callback Otomatis dari Server Midtrans untuk update status Lunas
     */
    public function midtransCallback(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        $hashedKey = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashedKey !== $request->signature_key) {
            return response()->json(['message' => 'Invalid Signature'], 403);
        }

        $orderParts = explode('-', $request->order_id);
        $bookingId = end($orderParts);

        $booking = \App\Models\Booking::find($bookingId);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $transactionStatus = $request->transaction_status;

        if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {
            $booking->update(['status' => 'Lunas']);
        } elseif ($transactionStatus == 'pending') {
            $booking->update(['status' => 'Pending']);
        } elseif ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
            $booking->update(['status' => 'Expired']);
        }

        return response()->json(['message' => 'Callback processed successfully'], 200);
    }
}
