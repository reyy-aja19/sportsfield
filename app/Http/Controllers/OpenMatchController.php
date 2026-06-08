<?php

namespace App\Http\Controllers;

use App\Models\OpenMatch;
use Illuminate\Http\Request;

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
    | STORE OPEN MATCH
    |--------------------------------------------------------------------------
    |*/

    public function store(Request $request)
    {
        // 1. Validasi request dari Flutter (booking_id wajib dikirim)
        $request->validate([
            'booking_id'    => 'required|integer',
            'title'         => 'required|string',
            'jenis'         => 'required|string',
            'tanggal'       => 'required|date',
            'jumlah_pemain' => 'required|integer',
        ]);

        // Ambil ID user dari auth token sanctum jika ada, atau fallback ke request parameter
        $userId = $request->user() ? $request->user()->id : ($request->userId ?? $request->user_id ?? 1);

        // 2. KUNCI ALUR: Cek kepemilikan booking dan status pembayarannya
        $booking = \App\Models\Booking::whereId($request->booking_id)
            ->where('user_id', $userId)
            ->first();

        // Skenario A: Jika data booking tidak ditemukan di DB
        if (!$booking) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal: Data booking lapangan tidak ditemukan.'
            ], 404);
        }

        // Skenario B: Jika booking ada tapi statusnya belum "Lunas" (misal: 'Pending' atau 'Expired')
        if (strtolower($booking->status) !== 'lunas') {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal: Kamu harus melunasi pembayaran booking lapangan terlebih dahulu sebelum membuat Open Match.'
            ], 400);
        }

        // 3. Eksekusi simpan jika kondisi di atas sudah terpenuhi (Lunas)
        $match = OpenMatch::create([
            'booking_id'       => $request->booking_id,
            'title'            => $request->title,
            'jenis'            => $request->jenis,
            'tanggal'          => $request->tanggal,
            'start_time'       => $request->start_time,
            'end_time'         => $request->end_time,
            'jumlah_pemain'    => $request->jumlah_pemain,
            'jumlah_bergabung' => 1,
            'deskripsi'        => $request->deskripsi,
            'status'           => 'Open',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Open match berhasil dibuat',
            'data' => $match
        ], 201);
    }
}