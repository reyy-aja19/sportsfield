<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index()
    {
        $venues = Venue::latest()->get();

        return view('admin.venue.index', [
            'venues' => $venues,
            'heading' => 'Management Venue',
            'title' => 'Management Venue'
        ]);
    }

    public function create()
    {
        return view('admin.venue.create', [
            'heading' => 'Tambah Venue',
            'title' => 'Tambah Venue'
        ]);
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'name' => 'required',
        'city' => 'nullable',
        'address' => 'nullable',
        'phone' => 'nullable',
        'email' => 'nullable|email',
        'description' => 'nullable',
        'status' => 'required',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp',

        // maps
        'google_maps' => 'nullable|url',
        'map_embed' => 'nullable|string'
    ]);

    if ($request->hasFile('photo')) {

        $file = $request->file('photo');

        $filename = time().'_'.$file->getClientOriginalName();

        $file->move(public_path('uploads/venue'), $filename);

        $data['photo'] = 'uploads/venue/'.$filename;
    }

    Venue::create($data);

    return redirect()
        ->route('admin.venue.index')
        ->with('success', 'Venue berhasil ditambahkan');
}

    public function edit(Venue $venue)
    {
        return view('admin.venue.edit', [
            'venue' => $venue,
            'heading' => 'Edit Venue',
            'title' => 'Edit Venue'
        ]);
    }

    public function update(Request $request, Venue $venue)
{
    $data = $request->validate([
        'name' => 'required',
        'city' => 'nullable',
        'address' => 'nullable',
        'phone' => 'nullable',
        'email' => 'nullable|email',
        'description' => 'nullable',
        'status' => 'required',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp',

        // maps
        'google_maps' => 'nullable|url',
        'map_embed' => 'nullable|string'
    ]);

    if ($request->hasFile('photo')) {

        $file = $request->file('photo');

        $filename = time().'_'.$file->getClientOriginalName();

        $file->move(public_path('uploads/venue'), $filename);

        $data['photo'] = 'uploads/venue/'.$filename;
    }

    $venue->update($data);

    return redirect()
        ->route('admin.venue.index')
        ->with('success', 'Venue berhasil diupdate');
}

    public function destroy(Venue $venue)
    {
        $venue->delete();

        return redirect()->route('admin.venue.index')
            ->with('success', 'Venue berhasil dihapus');
    }

    public function show(Venue $venue)
{
    return view('admin.venue.show', [
        'venue' => $venue,
        'heading' => 'Detail Venue',
        'title' => 'Detail Venue'
    ]);
}
}