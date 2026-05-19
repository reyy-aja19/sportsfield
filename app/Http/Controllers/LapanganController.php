<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use App\Models\Venue;
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
}