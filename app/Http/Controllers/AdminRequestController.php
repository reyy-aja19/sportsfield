<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminRequest;

class AdminRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'venue_name' => 'required',
            'phone' => 'required',
            'reason' => 'required',
        ]);

        AdminRequest::create([
            'user_id' => $request->user_id,
            'venue_name' => $request->venue_name,
            'phone' => $request->phone,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dikirim'
        ]);
    }
}