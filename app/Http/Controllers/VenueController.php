<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    private function layoutData(Request $request, array $extra = []): array
{
    $adminUser = \App\Models\User::find(
        $request->session()->get('admin_user_id')
    );

    $pendingReviewCount = \App\Models\Review::whereNull('reply_message')->count();

    $pendingPaymentCount = \App\Models\Payment::where('status', '!=', 'Lunas')->count();

    $notifications = collect()

        ->merge(
            \App\Models\Review::with(['user', 'lapangan'])
                ->whereNull('reply_message')
                ->latest()
                ->get()
                ->map(function ($review) {
                    return [
                        'type' => 'review',
                        'icon' => 'fa-regular fa-message',
                        'label' => 'Review belum terbaca',
                        'meta' =>
                            ($review->user?->name ?? 'User') .
                            ' • ' .
                            ($review->lapangan?->nama ?? 'Lapangan') .
                            ' • rating ' .
                            $review->rating . '/5',

                        'time' => optional($review->created_at)->diffForHumans(),

                        'sort_at' =>
                            optional($review->created_at)?->timestamp ?? 0,

                        'url' => route('admin.reviews'),
                    ];
                })
        )

        ->merge(
            \App\Models\Payment::with(['user', 'booking.lapangan'])
                ->where('status', '!=', 'Lunas')
                ->latest()
                ->get()
                ->map(function ($payment) {
                    return [
                        'type' => 'payment',
                        'icon' => 'fa-solid fa-wallet',
                        'label' => 'Pembayaran belum diverifikasi',

                        'meta' =>
                            ($payment->user?->name ?? 'User') .
                            ' • ' .
                            ($payment->booking?->lapangan?->nama ?? 'Lapangan'),

                        'time' => optional($payment->created_at)->diffForHumans(),

                        'sort_at' =>
                            optional($payment->created_at)?->timestamp ?? 0,

                        'url' => route('admin.payments'),
                    ];
                })
        )

        ->sortByDesc('sort_at')
        ->values();

    return array_merge([
        'adminUser' => $adminUser,
        'pendingReviewCount' => $pendingReviewCount,
        'pendingPaymentCount' => $pendingPaymentCount,
        'totalNotificationCount' =>
            $pendingReviewCount + $pendingPaymentCount,
        'notifications' => $notifications,
    ], $extra);
}

    public function index(Request $request)
{
   $user = \App\Models\User::find(
    $request->session()->get('admin_user_id')
);

if ($user->role === 'superadmin') {

    $venues = Venue::latest()->get();

} else {

    $venues = Venue::where('user_id', $user->id)
        ->latest()
        ->get();
}

    return view(
        'admin.venue.index',
        $this->layoutData($request, [
            'venues' => $venues,
            'heading' => 'Management Venue',
            'title' => 'Management Venue'
        ])
    );
}

    public function create(Request $request)
{
    return view(
        'admin.venue.create',
        $this->layoutData($request, [
            'heading' => 'Tambah Venue',
            'title' => 'Tambah Venue'
        ])
    );
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

    $data['user_id'] =
    $request->session()->get('admin_user_id');

    $data['approval_status'] = 'Pending';

    Venue::create($data);

    return redirect()
        ->route('admin.venue.index')
        ->with('success', 'Venue berhasil ditambahkan');
}

    public function edit(Request $request, Venue $venue)
{
    return view(
        'admin.venue.edit',
        $this->layoutData($request, [
            'venue' => $venue,
            'heading' => 'Edit Venue',
            'title' => 'Edit Venue'
        ])
    );
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

   public function show(Request $request, Venue $venue)
{
    return view(
        'admin.venue.show',
        $this->layoutData($request, [
            'venue' => $venue,
            'heading' => 'Detail Venue',
            'title' => 'Detail Venue'
        ])
    );
}

public function approve(Venue $venue)
{
    $venue->update([
        'approval_status' => 'Approved'
    ]);

    // ubah user jadi admin
    if ($venue->user) {

        $venue->user->update([
            'role' => 'admin'
        ]);

    }

    return back()->with(
        'success',
        'Venue berhasil diapprove'
    );
}

public function reject(Venue $venue)
{
    $venue->update([
        'approval_status' => 'Rejected'
    ]);

    return back()->with(
        'success',
        'Venue berhasil ditolak'
    );
}
}