<?php

namespace App\Http\Controllers;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
use App\Models\OpenMatch;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenMatchController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET ALL OPEN MATCHES
    |--------------------------------------------------------------------------
    |*/
    public function index()
    {
        $matches = OpenMatch::with('booking')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $matches
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE OPEN MATCH (Membuat Match Baru)
    |--------------------------------------------------------------------------
    |*/
    public function store(Request $request)
    {
        // 1. Validasi request dari Flutter
        $request->validate([
            'booking_id'    => 'required|integer',
            'title'         => 'required|string',
            'jenis'         => 'required|string',
            'tanggal'       => 'required|date',
            'jumlah_pemain' => 'required|integer',
        ]);

        // Ambil ID user dari auth token sanctum jika ada, atau fallback ke request parameter
        $userId = $request->user() ? $request->user()->id : ($request->userId ?? $request->user_id ?? 1);

        // 2. Cek kepemilikan booking dan status pembayarannya
        /** @var \App\Models\Booking|null $booking */
        $booking = Booking::query()
            ->where('id', $request->booking_id)
            ->where('user_id', $userId)
            ->first();

        // Skenario A: Jika data booking tidak ditemukan di DB
        if (!$booking) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal: Data booking lapangan tidak ditemukan.'
            ], 404);
        }

        // Skenario B: Jika booking ada tapi statusnya belum "Lunas"
        if (strtolower($booking->status) !== 'lunas') {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal: Kamu harus melunasi pembayaran booking lapangan terlebih dahulu sebelum membuat Open Match.'
            ], 400);
        }

        // 3. Eksekusi simpan jika kondisi di atas sudah terpenuhi (Lunas)
        $match = OpenMatch::query()->create([
            'booking_id'       => $request->booking_id,
            'title'            => $request->title,
            'jenis'            => $request->jenis,
            'tanggal'          => $request->tanggal,
            'start_time'       => $request->start_time,
            'end_time'         => $request->end_time,
            'jumlah_pemain'    => $request->jumlah_pemain,
            'jumlah_bergabung' => 1, // Pembuat match otomatis masuk hitungan pertama
            'deskripsi'        => $request->deskripsi,
            'status'           => 'Open',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Open match berhasil dibuat',
            'data' => $match
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | JOIN AN OPEN MATCH (Sudah Diperbaiki untuk Intelephense)
    |--------------------------------------------------------------------------
    |*/
    public function join(Request $request, $id)
    {
        // 1. Ambil ID user dari token Sanctum atau fallback parameter
        $userId = $request->user() ? $request->user()->id : ($request->userId ?? $request->user_id ?? 1);

        // 2. Cari data match berdasarkan ID
        /** @var \App\Models\OpenMatch|null $match */
        $match = OpenMatch::query()->find($id);

        if (!$match) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal: Match tidak ditemukan.'
            ], 404);
        }

        // 3. Cek jika user yang mau join adalah orang yang membuat match tersebut
        /** @var \App\Models\Booking|null $booking */
        $booking = Booking::query()->find($match->booking_id);
        if ($booking && $booking->user_id == $userId) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal join: Kamu adalah pembuat match ini.'
            ], 400);
        }

        // 4. Cek duplikasi join lewat tabel relasi/pivot
        $alreadyJoined = DB::table('match_user')
            ->where('open_match_id', $match->id)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyJoined) {
            return response()->json([
                'status'  => false,
                'message' => 'Kamu sudah bergabung ke dalam match ini sebelumnya.'
            ], 400);
        }

        // 5. Cek apakah kuota match sudah penuh sebelum menambahkan record baru
        if ($match->jumlah_bergabung >= $match->jumlah_pemain) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal join: Slot pertandingan Open Match ini sudah penuh.'
            ], 400);
        }

        // 6. SOLUSI FIX: Isolasi pembuatan waktu ke variabel mandiri di luar array DB
        $waktuSekarang = date('Y-m-d H:i:s');

        DB::table('match_user')->insert([
            'open_match_id' => $match->id,
            'user_id'       => $userId,
            'created_at'    => $waktuSekarang,
            'updated_at'    => $waktuSekarang,
        ]);

        // 7. Tambahkan jumlah pemain yang bergabung
        $match->jumlah_bergabung = $match->jumlah_bergabung + 1;

        // Jika kuota penuh setelah user ini masuk, ubah status match jadi Full
        if ($match->jumlah_bergabung >= $match->jumlah_pemain) {
            $match->status = 'Full';
        }

        $match->save();

        return response()->json([
            'status'  => true,
            'message' => 'Berhasil bergabung ke dalam match! 🔥',
            'data'    => $match
        ], 200);
    }
}