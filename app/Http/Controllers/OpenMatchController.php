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
    */

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
    */

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'jenis' => 'required|string',
            'tanggal' => 'required|date',
            'jumlah_pemain' => 'required|integer',
        ]);

        $match = OpenMatch::create([
            'booking_id' => $request->booking_id,
            'title' => $request->title,
            'jenis' => $request->jenis,
            'tanggal' => $request->tanggal,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'jumlah_pemain' => $request->jumlah_pemain,
            'jumlah_bergabung' => 1,
            'deskripsi' => $request->deskripsi,
            'status' => 'Open',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Open match berhasil dibuat',
            'data' => $match
        ], 201);
    }
}