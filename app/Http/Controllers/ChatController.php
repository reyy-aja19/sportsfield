<?php

namespace App\Http\Controllers;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
use App\Models\Message; // FIX: Mengimpor Model Message agar tidak Undefined
use App\Models\OpenMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * AMBIL SEMUA RIWAYAT CHAT DI GRUP MATCH TERSEBUT
     */
    public function getMessages(Request $request, $matchId)
    {
        $user = $request->user();
        
        // FIX: Menggunakan query() agar Intelephense tidak memunculkan eror "Not enough arguments"
        /** @var \App\Models\OpenMatch|null $match */
        $match = OpenMatch::query()->find($matchId);
        
        if (!$match) {
            return response()->json([
                'status' => false, 
                'message' => 'Match tidak ditemukan'
            ], 404);
        }

        // Cek di tabel pivot match_user atau pemilik booking lapangan
        $isJoined = DB::table('match_user')
            ->where('open_match_id', $matchId)
            ->where('user_id', $user->id)
            ->exists();

        // Hubungan ke booking jika diperlukan pengaman ekstra
        $booking = $match->booking_id ? DB::table('bookings')->where('id', $match->booking_id)->first() : null;
        $isCreator = ($booking && $booking->user_id == $user->id);

        if (!$isJoined && !$isCreator) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal: Kamu harus bergabung ke Open Match ini terlebih dahulu untuk mengakses grup chat.'
            ], 403);
        }

        // Mengambil histori chat
        $messages = Message::query()
            ->with('user:id,name')
            ->where('open_match_id', $matchId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $messages
        ], 200);
    }

    /**
     * KIRIM CHAT BARU KE GRUP MATCH
     */
    public function sendMessage(Request $request, $matchId)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = $request->user();

        // Simpan pesan baru
        $message = Message::query()->create([
            'open_match_id' => $matchId,
            'user_id'       => $user->id,
            'message'       => $request->message
        ]);

        // Muat data relasi user pengirimnya
        $message->load('user:id,name');

        return response()->json([
            'status' => true,
            'message' => 'Pesan terkirim',
            'data' => $message
        ], 201);
    }
}