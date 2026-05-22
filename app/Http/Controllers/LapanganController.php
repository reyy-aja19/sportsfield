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

        // upload foto utama
        if ($request->hasFile('foto')) {

            $file = $request->file('foto');

            $filename = time().'_'.$file->getClientOriginalName();

            $file->move(public_path('uploads/lapangan'), $filename);

            $data['foto'] = $filename;
        }

        // upload gallery
        $gallery = [];

        if ($request->hasFile('foto_gallery')) {

            foreach ($request->file('foto_gallery') as $image) {

                $galleryName = time().'_'.$image->getClientOriginalName();

                $image->move(public_path('uploads/lapangan'), $galleryName);

                $gallery[] = $galleryName;
            }
        }

        $data['foto_gallery'] = $gallery;

        Lapangan::create($data);

        return redirect('/lapangan')
            ->with('success', 'Data berhasil ditambah');
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

        // update foto utama
        if ($request->hasFile('foto')) {

            $file = $request->file('foto');

            $filename = time().'_'.$file->getClientOriginalName();

            $file->move(public_path('uploads/lapangan'), $filename);

            $data['foto'] = $filename;
        }

        // update gallery
        if ($request->hasFile('foto_gallery')) {

            $gallery = [];

            foreach ($request->file('foto_gallery') as $image) {

                $galleryName = time().'_'.$image->getClientOriginalName();

                $image->move(public_path('uploads/lapangan'), $galleryName);

                $gallery[] = $galleryName;
            }

            $data['foto_gallery'] = $gallery;
        }

        $lapangan->update($data);

        return redirect('/lapangan')
            ->with('success', 'Data berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Lapangan::destroy($id);

        return redirect('/lapangan')
            ->with('success', 'Data berhasil dihapus');
    }

    /*
    |--------------------------------------------------------------------------
    | API METHODS (Untuk Kebutuhan Flutter / Mobile)
    |--------------------------------------------------------------------------
    */

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
        $request->validate([
            'lapangan_id' => 'required|integer',
            'tanggal' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'total_harga' => 'required',
        ]);

        $booking = \App\Models\Booking::create([
            'user_id' => $request->user()->id,
            'lapangan_id' => $request->lapangan_id,
            'tanggal' => $request->tanggal,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_harga' => $request->total_harga,
            'status' => 'Pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Booking berhasil dibuat',
            'data' => $booking
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
}