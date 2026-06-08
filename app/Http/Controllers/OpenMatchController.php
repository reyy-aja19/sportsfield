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
        $request->validate([
            'booking_id'    => 'required|integer',
            'title'         => 'required|string',
            'jenis'         => 'required|string',
            'tanggal'       => 'required|date',
            'jumlah_pemain' => 'required|integer',
        ]);

        // Cek Auth User secara ketat
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal: Sesi login kamu telah berakhir.'
            ], 401);
        }

        $userId = $user->id;

        /** @var \App\Models\Booking|null $booking */
        $booking = Booking::query()
            ->where('id', $request->booking_id)
            ->where('user_id', $userId)
            ->first();

        if (!$booking) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal: Data booking lapangan tidak ditemukan.'
            ], 404);
        }

        if (strtolower($booking->status) !== 'lunas') {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal: Kamu harus melunasi pembayaran booking lapangan terlebih dahulu sebelum membuat Open Match.'
            ], 400);
        }

        $match = OpenMatch::query()->create([
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

    /*
    |--------------------------------------------------------------------------
    | JOIN AN OPEN MATCH 
    |--------------------------------------------------------------------------
    |*/
    public function join(Request $request, $id)
    {
        // Cek Auth User secara ketat tanpa fallback nilai default
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal: Kamu harus login terlebih dahulu.'
            ], 401);
        }

        $userId = $user->id;

        /** @var \App\Models\OpenMatch|null $match */
        $match = OpenMatch::query()->find($id);

        if (!$match) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal: Match tidak ditemukan.'
            ], 404);
        }

        /** @var \App\Models\Booking|null $booking */
        $booking = Booking::query()->find($match->booking_id);
        if ($booking && $booking->user_id == $userId) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal join: Kamu adalah pembuat match ini.'
            ], 400);
        }

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

        if ($match->jumlah_bergabung >= $match->jumlah_pemain) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal join: Slot pertandingan Open Match ini sudah penuh.'
            ], 400);
        }

        $waktuSekarang = date('Y-m-d H:i:s');

        DB::table('match_user')->insert([
            'open_match_id' => $match->id,
            'user_id'       => $userId,
            'created_at'    => $waktuSekarang,
            'updated_at'    => $waktuSekarang,
        ]);

        $match->jumlah_bergabung = $match->jumlah_bergabung + 1;

        if ($match->jumlah_bergabung >= $match->jumlah_pemain) {
            $match->status = 'Full';
        }

        $match->save();

        return response()->json([
            'status'  => true,
            'message' => 'Berhasil bergabung ke dalam match! 🔥',
            'data'   => $match
        ], 200);
    }
}