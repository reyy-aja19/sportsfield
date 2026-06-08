<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use App\Models\Venue;
use App\Models\Facility;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
        $request->validate([
            'lapangan_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        $bookings = \App\Models\Booking::where('lapangan_id', $request->lapangan_id)
            ->where('booking_date', $request->date)
            ->whereIn('status', [
                'Pending',
                'Lunas',
                'DP'
            ])
            ->get();

        $bookedSlots = [];

        foreach ($bookings as $booking) {
            $start = (int) explode(':', $booking->start_time)[0];
            $end = (int) explode(':', $booking->end_time)[0];

            for ($i = $start; $i < $end; $i++) {
                $bookedSlots[] = sprintf("%02d:00", $i);
            }
        }

        return response()->json([
            'status' => true,
            'data' => array_values(array_unique($bookedSlots))
        ]);
    }

    public function apiBookings(Request $request)
    {
        $bookings = \App\Models\Booking::with('lapangan')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        foreach ($bookings as $booking) {
            if (!empty($booking->end_time)) {
                
                // Menggunakan Carbon murni instansiasi baru agar tidak rancu dengan filesystem PHP
                $bookingDateStr = (string) $booking->booking_date;
                /** @var Carbon $carbonObj */
                $carbonObj = Carbon::parse($bookingDateStr);
                
                // Baris 191 & 286 Teratasi di sini
                $bookingEnd = $carbonObj->copy()->setTimeFromTimeString($booking->end_time);

                if (
                    now()->greaterThan($bookingEnd)
                    &&
                    !in_array($booking->status, [
                        'Selesai',
                        'Batal',
                        'Expired'
                    ])
                ) {
                    $booking->status = 'Selesai';
                    $booking->save();
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Riwayat booking user berhasil diambil',
            'data' => $bookings
        ]);
    }

    public function apiStoreBooking(Request $request)
    {
        $request->validate([
            'lapangan_id'    => 'required|integer',
            'payment_method' => 'nullable|string',
            'date'           => 'required|date',
            'start_time'     => 'required',
            'end_time'       => 'nullable|string',
            'total_price'    => 'required',
        ]);

        $userId = $request->user()
            ? $request->user()->id
            : ($request->userId ?? 1);

        $total = (int) $request->total_price;
        $dpAmount = $total * 0.5;

        // STATUS LOGIC
        $paymentMethod = $request->payment_method;
        $paidAmount = 0;
        $remainingAmount = $total;
        $status = 'Pending';

        if ($paymentMethod === 'Midtrans Full') {
            $paidAmount = $total;
            $remainingAmount = 0;
        }

        $existing = \App\Models\Booking::where('lapangan_id', $request->lapangan_id)
            ->where('booking_date', $request->date)
            ->whereNotIn('status', ['Batal', 'Expired'])
            ->where(function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>', $request->start_time);
                });
            })
            ->exists();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'Jam sudah dibooking user lain'
            ], 409);
        }

        // CREATE BOOKING
        $booking = \App\Models\Booking::create([
            'user_id'         => $userId,
            'lapangan_id'     => $request->lapangan_id,
            'booking_date'    => $request->date,
            'start_time'      => $request->start_time,
            'end_time'        => $request->end_time ?? $request->start_time,
            'total_price'     => $total,
            'paid_amount'     => $paidAmount,
            'remaining_amount'=> $remainingAmount,
            'status'          => $status,
            'payment_method'  => $request->payment_method,
        ]);

        $orderId = 'BOOK-' . time() . '-' . $booking->id;

        // MIDTRANS ONLY (DP atau FULL)
        if ($request->payment_method === 'Midtrans Full' || $request->payment_method === 'DP') {

            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
            \Midtrans\Config::$isSanitized = config('services.midtrans.is_sanitized');
            \Midtrans\Config::$is3ds = config('services.midtrans.is_3ds');

            $grossAmount = $request->payment_method === 'DP' ? $dpAmount : $total;

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $grossAmount,
                ],
            ];

            $response = \Midtrans\Snap::createTransaction($params);

            return response()->json([
                'status' => true,
                'message' => $request->payment_method === 'DP' ? 'DP 50% berhasil dibuat' : 'Pembayaran penuh dibuat',
                'redirect_url' => $response->redirect_url,
                'data' => $booking
            ]);
        }

        // TUNAI / OFFLINE
        return response()->json([
            'status' => true,
            'message' => 'Booking tunai berhasil dibuat (menunggu admin)',
            'data' => $booking
        ]);
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

        // Baris 390 Teratasi: Beritahu eksplisit bahwa ini adalah Builder Query Eloquent Model
        $booking = \App\Models\Booking::query()->where('id', $bookingId)->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $transactionStatus = $request->transaction_status;

        // ================= SUCCESS =================
        if ($transactionStatus == 'settlement' || $transactionStatus == 'capture') {

            // Baris 428 Teratasi: Menggunakan array offset notation [$key] untuk mematikan pengecekan magic method oleh Intelephense
            if ($booking['status'] != 'Lunas') {
                $totalPrice = (int) $booking['total_price'];

                // FULL PAYMENT
                if ($booking['payment_method'] == 'Midtrans Full') {
                    $booking->update([
                        'status' => 'Lunas',
                        'paid_amount' => $totalPrice,
                        'remaining_amount' => 0,
                        'is_paid_off' => true
                    ]);
                }

                // DP PAYMENT
                if ($booking['payment_method'] == 'DP') {
                    $booking->update([
                        'status' => 'DP',
                        'paid_amount' => (int)($totalPrice * 0.5),
                        'remaining_amount' => (int)($totalPrice * 0.5),
                    ]);
                }

                // POINT SYSTEM
                $booking->refresh();

                if (!$booking['point_given'] && $booking['status'] === 'Lunas') {
                    $user = \App\Models\User::query()->where('id', $booking['user_id'])->first();
                    if ($user) {
                        $user->increment('points', 5);
                    }
                    $booking->update(['point_given' => true]);
                }
            }
        }
        // ================= PENDING =================
        elseif ($transactionStatus == 'pending') {
            $booking->update(['status' => 'Pending']);
        }
        // ================= FAILED =================
        elseif ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
            $booking->update(['status' => 'Expired']);
        }

        return response()->json(['message' => 'Callback processed successfully'], 200);
    }
}