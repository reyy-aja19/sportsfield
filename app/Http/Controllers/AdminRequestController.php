<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminRequest;

class AdminRequestController extends Controller
{
    public function store(Request $request)
    {
        // Ambil data input baik dari body POST ataupun query parameter jika terkena redirect GET
        $inputData = $request->isMethod('get') ? $request->all() : $request->json()->all();

        // Validasi data secara manual agar tidak crash saat membaca array dinamis
        if (!isset($inputData['user_id']) || !isset($inputData['venue_name']) || !isset($inputData['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak lengkap. Pastikan Nama Venue, Nomor HP, dan Alasan telah diisi.'
            ], 400);
        }

        // Simpan data ke database menggunakan Eloquent
        AdminRequest::create([
            'user_id'    => $inputData['user_id'],
            'venue_name' => $inputData['venue_name'],
            'phone'      => $inputData['phone'],
            'reason'     => $inputData['reason'] ?? '-',
            'status'     => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dikirim'
        ], 200);
    }
}