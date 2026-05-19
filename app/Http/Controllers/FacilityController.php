<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index()
    {
        $facilities = Facility::latest()->get();

        return view('admin.facilities.index', [
            'facilities' => $facilities
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:facilities,name'
        ]);

        Facility::create([
            'name' => $request->name
        ]);

        return back()->with('success', 'Fasilitas berhasil ditambahkan');
    }

    public function destroy(Facility $facility)
    {
        $facility->delete();

        return back()->with('success', 'Fasilitas berhasil dihapus');
    }
}