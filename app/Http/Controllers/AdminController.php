<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\Payment;
use App\Models\Redemption;
use App\Models\Review;
use App\Models\Reward;
use App\Models\User;
use App\Models\Venue;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    private function adminUser(Request $request): ?User
    {
        return User::find($request->session()->get('admin_user_id'));
    }

    private function publicUpload(Request $request, string $field, string $folder): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $filename = now()->format('YmdHis') . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $destination = public_path('uploads/' . $folder);

        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $file->move($destination, $filename);

        return 'uploads/' . $folder . '/' . $filename;
    }

    private function publicUploadMultiple(Request $request, string $field, string $folder): array
    {
        if (! $request->hasFile($field)) {
            return [];
        }

        $paths = [];
        foreach ((array) $request->file($field) as $file) {
            if (! $file) {
                continue;
            }

            $filename = now()->format('YmdHis') . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/' . $folder);

            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }

            $file->move($destination, $filename);
            $paths[] = 'uploads/' . $folder . '/' . $filename;
        }

        return $paths;
    }

    private function normalizeFacilities(?array $facilities): array
    {
        return collect($facilities ?? [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    private function deletePublicUpload(?string $path): void
    {
        if (! $path) {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function layoutData(Request $request, array $extra = []): array
    {
        $pendingReviewCount = Review::whereNull('reply_message')->count();
        $pendingPaymentCount = Payment::where('status', '!=', 'Lunas')->count();

        $notifications = collect()
            ->merge(Review::with(['user', 'lapangan'])
                ->whereNull('reply_message')
                ->latest()
                ->get()
                ->map(function ($review) {
                    return [
                        'type' => 'review',
                        'icon' => 'fa-regular fa-message',
                        'label' => 'Review belum terbaca',
                        'meta' => ($review->user?->name ?? 'User') . ' • ' . ($review->lapangan?->nama ?? 'Lapangan') . ' • rating ' . $review->rating . '/5',
                        'time' => optional($review->created_at)->diffForHumans(),
                        'sort_at' => optional($review->created_at)?->timestamp ?? 0,
                        'url' => route('admin.reviews'),
                    ];
                }))
            ->merge(Payment::with(['user', 'booking.lapangan'])
                ->where('status', '!=', 'Lunas')
                ->latest()
                ->get()
                ->map(function ($payment) {
                    return [
                        'type' => 'payment',
                        'icon' => 'fa-solid fa-wallet',
                        'label' => 'Pembayaran belum diverifikasi',
                        'meta' => ($payment->user?->name ?? 'User') . ' • ' . ($payment->booking?->lapangan?->nama ?? 'Lapangan'),
                        'time' => optional($payment->created_at)->diffForHumans(),
                        'sort_at' => optional($payment->created_at)?->timestamp ?? 0,
                        'url' => route('admin.payments'),
                    ];
                }))
            ->sortByDesc('sort_at')
            ->values();

        return array_merge([
            'adminUser' => $this->adminUser($request),
            'pendingReviewCount' => $pendingReviewCount,
            'pendingPaymentCount' => $pendingPaymentCount,
            'totalNotificationCount' => $pendingReviewCount + $pendingPaymentCount,
            'notifications' => $notifications,
        ], $extra);
    }

    public function dashboard(Request $request): View
{
    $driver = DB::connection()->getDriverName();

    $monthSql = $driver === 'sqlite'
        ? "strftime('%m', created_at)"
        : "MONTH(created_at)";

    $bookingMonthSql = $driver === 'sqlite'
        ? "strftime('%m', booking_date)"
        : "MONTH(booking_date)";

    $revenuePerMonth = Payment::selectRaw("
            {$monthSql} as month,
            SUM(amount) as total
        ")
        ->where('status', 'Lunas')
        ->groupBy('month')
        ->pluck('total', 'month');

    $bookingPerMonth = Booking::selectRaw("
            {$bookingMonthSql} as month,
            COUNT(*) as total
        ")
        ->groupBy('month')
        ->pluck('total', 'month');

    $userPerMonth = User::selectRaw("
            {$monthSql} as month,
            COUNT(*) as total
        ")
        ->where('role', 'user')
        ->groupBy('month')
        ->pluck('total', 'month');

    $months = ['01', '02', '03', '04', '05'];

    return view('admin.dashboard', $this->layoutData($request, [

        'totalVenue' => Venue::count(),

        'lapangan' => Lapangan::latest()
            ->take(6)
            ->get(),

        'stats' => [
            [
                'label' => 'Total Users',
                'value' => User::where('role', 'user')->count()
            ],
            [
                'label' => 'Total Lapangan',
                'value' => Lapangan::count()
            ],
            [
                'label' => 'Total Pendapatan',
                'value' => 'Rp ' . number_format(
                    (int) Payment::where('status', 'Lunas')->sum('amount'),
                    0,
                    ',',
                    '.'
                )
            ],
        ],

        'chartUsers' => array_map(
            fn($m) => (int) ($userPerMonth[$m] ?? 0),
            $months
        ),

        'chartLapangan' => array_map(
            fn($m) => (int) ($bookingPerMonth[$m] ?? 0),
            $months
        ),

        'chartRevenue' => array_map(
            fn($m) => (int) ($revenuePerMonth[$m] ?? 0),
            $months
        ),

        'userRatio' => [
            User::where('role', 'user')
                ->where('status', 'Aktif')
                ->count(),

            User::where('role', 'user')
                ->where('status', '!=', 'Aktif')
                ->count(),
        ],

    ]));
}

    public function users(Request $request): View
    {
        $users = User::withCount('bookings')->orderByRaw("role='admin' desc")->orderByDesc('id')->get();
        return view('admin.users', $this->layoutData($request, compact('users')));
    }

    public function userCreate(Request $request): View
    {
        return view('admin.user-form', $this->layoutData($request, [
            'mode' => 'create',
            'user' => new User(['role' => 'user', 'status' => 'Aktif']),
        ]));
    }

    public function userStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'points' => ['nullable', 'integer', 'min:0'],
            'role' => ['required', 'in:admin,user'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $data['password'] = bcrypt($data['password']);
        $data['points'] = $data['points'] ?? 0;

        User::create($data);
        return redirect()->route('admin.users')->with('success', 'User berhasil ditambahkan.');
    }

    public function userEdit(Request $request, User $user): View
    {
        return view('admin.user-form', $this->layoutData($request, [
            'mode' => 'edit',
            'user' => $user,
        ]));
    }

    public function userUpdate(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'points' => ['nullable', 'integer', 'min:0'],
            'role' => ['required', 'in:admin,user'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        $data['points'] = $data['points'] ?? 0;

        $user->update($data);
        return redirect()->route('admin.users')->with('success', 'User berhasil diperbarui.');
    }

    public function userToggle(User $user): RedirectResponse
    {
        $user->update(['status' => $user->status === 'Aktif' ? 'Nonaktif' : 'Aktif']);
        return back()->with('success', 'Status user berhasil diperbarui.');
    }

    public function userDelete(User $user): RedirectResponse
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Admin utama tidak dapat dihapus.');
        }
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    public function courts(Request $request): View
    {
        $courts = Lapangan::latest()->get();
        return view('admin.courts', $this->layoutData($request, compact('courts')));
    }

    public function courtCreate(Request $request): View
{
    $venues = Venue::where('status', 'Aktif')
        ->orderBy('name')
        ->get();

    $facilities = Facility::orderBy('name')->get();

    return view('admin.lapangan.create',
        $this->layoutData($request, [
            'venues' => $venues,
            'facilities' => $facilities,
            'title' => 'Tambah Lapangan',
            'heading' => 'Tambah Lapangan',
        ])
    );
}

    public function courtStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'venue_id' => ['required', 'exists:venues,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'status' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'foto_gallery' => ['nullable', 'array'],
            'foto_gallery.*' => ['image', 'max:4096'],
            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['nullable', 'string', 'max:80'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->publicUpload($request, 'foto', 'lapangan');
        }

        $data['foto_gallery'] = $this->publicUploadMultiple($request, 'foto_gallery', 'lapangan');
        $data['fasilitas'] = $this->normalizeFacilities($request->input('fasilitas', []));
        foreach ($data['fasilitas'] as $facilityName) {

    Facility::firstOrCreate([
        'name' => $facilityName
    ]);

}
        $data['rating'] = $data['rating'] ?? 4.5;
        Lapangan::create($data);
        return redirect()->route('admin.courts')->with('success', 'Lapangan berhasil ditambahkan.');
    }

    public function courtShow(Request $request, Lapangan $lapangan): View
{
    $lapangan->load([
        'venue',
        'bookings.user',
    ]);

    return view('admin.lapangan.show', $this->layoutData($request, [
        'lapangan' => $lapangan,
        'title' => 'Detail Lapangan',
        'heading' => 'Detail Lapangan',
    ]));
}

    public function courtEdit(Request $request, Lapangan $lapangan): View
{
    $venues = Venue::where('status', 'Aktif')
        ->orderBy('name')
        ->get();

    $facilities = Facility::orderBy('name')->get();

    return view('admin.lapangan.edit',
        $this->layoutData($request, [
            'lapangan' => $lapangan,
            'venues' => $venues,
            'facilities' => $facilities,
            'title' => 'Edit Lapangan',
            'heading' => 'Edit Lapangan',
        ])
    );
}

    public function courtUpdate(Request $request, Lapangan $lapangan): RedirectResponse
    {
        $data = $request->validate([
            'venue_id' => ['required', 'exists:venues,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'status' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'foto_gallery' => ['nullable', 'array'],
            'foto_gallery.*' => ['image', 'max:4096'],
            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['nullable', 'string', 'max:80'],
        ]);

        if ($request->hasFile('foto')) {
            $this->deletePublicUpload($lapangan->foto);
            $data['foto'] = $this->publicUpload($request, 'foto', 'lapangan');
        }

        $existingGallery = $lapangan->foto_gallery ?? [];
        $newGallery = $this->publicUploadMultiple($request, 'foto_gallery', 'lapangan');
        $data['foto_gallery'] = array_values(array_filter(array_merge($existingGallery, $newGallery)));
        $data['fasilitas'] = $this->normalizeFacilities($request->input('fasilitas', []));
        foreach ($data['fasilitas'] as $facilityName) {

    Facility::firstOrCreate([
        'name' => $facilityName
    ]);

}
        $lapangan->update($data);
        return redirect()->route('admin.courts')->with('success', 'Lapangan berhasil diperbarui.');
    }

    public function courtDelete(Lapangan $lapangan): RedirectResponse
    {
        $this->deletePublicUpload($lapangan->foto);
        foreach (($lapangan->foto_gallery ?? []) as $galleryPath) {
            $this->deletePublicUpload($galleryPath);
        }
        $lapangan->delete();
        return back()->with('success', 'Lapangan berhasil dihapus.');
    }

    public function bookings(Request $request): View
    {
        $bookings = Booking::with(['user', 'lapangan'])->latest()->get();
        $users = User::where('role', 'user')->where('status', 'Aktif')->orderBy('name')->get();
        $courts = Lapangan::orderBy('nama')->get();
        return view('admin.bookings', $this->layoutData($request, compact('bookings', 'users', 'courts')));
    }

    public function bookingStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'lapangan_id' => ['required', 'exists:lapangan,id'],
            'payment_method' => ['required', 'string'],
            'booking_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'hours' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string'],
        ]);
        $court = Lapangan::findOrFail($data['lapangan_id']);
        $data['total_price'] = $court->harga * (int) $data['hours'];
        $booking = Booking::create($data);
        Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'method' => $booking->payment_method,
            'amount' => $booking->total_price,
            'proof_image' => 'uploads/payments/sample-proof.svg',
            'status' => $booking->status === 'Lunas' ? 'Lunas' : 'Menunggu',
            'paid_at' => $booking->status === 'Lunas' ? now() : null,
        ]);
        return back()->with('success', 'Booking berhasil ditambahkan.');
    }

    public function bookingToggle(Booking $booking): RedirectResponse
    {
        $booking->status = $booking->status === 'Lunas' ? 'DP' : 'Lunas';
        $booking->save();
        if ($booking->payment) {
            $booking->payment->update([
                'status' => $booking->status === 'Lunas' ? 'Lunas' : 'Menunggu',
                'paid_at' => $booking->status === 'Lunas' ? now() : null,
            ]);
        }
        return back()->with('success', 'Status booking berhasil diperbarui.');
    }

    public function bookingDelete(Booking $booking): RedirectResponse
    {
        $booking->delete();
        return back()->with('success', 'Booking berhasil dihapus.');
    }

    public function openMatches(Request $request): View
    {
        $matches = OpenMatch::with(['booking.user', 'booking.lapangan'])->latest()->get();
        $bookings = Booking::with(['user', 'lapangan'])
            ->whereIn('status', ['Lunas', 'DP'])
            ->latest()
            ->get();

        return view('admin.open-matches', $this->layoutData($request, compact('matches', 'bookings')));
    }

    public function openMatchStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'jumlah_pemain' => ['required', 'integer', 'min:1', 'max:99'],
            'jumlah_bergabung' => ['nullable', 'integer', 'min:0', 'max:99'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Open,Penuh,Selesai,Dibatalkan'],
        ]);

        $data['jumlah_bergabung'] = $data['jumlah_bergabung'] ?? 0;
        OpenMatch::create($data);

        return back()->with('success', 'Open match berhasil ditambahkan.');
    }

    public function openMatchUpdate(Request $request, OpenMatch $openMatch): RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'jumlah_pemain' => ['required', 'integer', 'min:1', 'max:99'],
            'jumlah_bergabung' => ['nullable', 'integer', 'min:0', 'max:99'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Open,Penuh,Selesai,Dibatalkan'],
        ]);

        $data['jumlah_bergabung'] = $data['jumlah_bergabung'] ?? 0;
        $openMatch->update($data);

        return back()->with('success', 'Open match berhasil diperbarui.');
    }

    public function openMatchToggle(OpenMatch $openMatch): RedirectResponse
    {
        $next = match ($openMatch->status) {
            'Open' => 'Penuh',
            'Penuh' => 'Selesai',
            'Selesai' => 'Open',
            default => 'Open',
        };

        $openMatch->update(['status' => $next]);
        return back()->with('success', 'Status open match berhasil diperbarui.');
    }

    public function openMatchDelete(OpenMatch $openMatch): RedirectResponse
    {
        $openMatch->delete();
        return back()->with('success', 'Open match berhasil dihapus.');
    }

    public function reviews(Request $request): View
    {
        $reviews = Review::with(['user', 'lapangan'])->latest()->get();
        return view('admin.reviews', $this->layoutData($request, compact('reviews')));
    }

    public function reviewReply(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate(['reply_message' => ['required', 'string']]);
        $review->update($data);
        return back()->with('success', 'Balasan review berhasil disimpan.');
    }

    public function reviewToggle(Review $review): RedirectResponse
    {
        $review->update(['is_visible' => !$review->is_visible]);
        return back()->with('success', 'Status review berhasil diperbarui.');
    }

    public function reviewDelete(Review $review): RedirectResponse
    {
        $review->delete();
        return back()->with('success', 'Review berhasil dihapus.');
    }

    public function rewards(Request $request): View
    {
        $rewards = Reward::latest()->get();
        $redemptions = Redemption::with(['reward', 'user'])->latest()->get();
        return view('admin.rewards', $this->layoutData($request, compact('rewards', 'redemptions')));
    }

    public function rewardStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'numeric', 'min:0'],
            'badge' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = $this->publicUpload($request, 'image', 'rewards');
        }
        Reward::create($data);
        return back()->with('success', 'Reward berhasil ditambahkan.');
    }

    public function rewardEdit(Request $request, Reward $reward): View
    {
        return view('admin.reward-edit', $this->layoutData($request, compact('reward')));
    }

    public function rewardUpdate(Request $request, Reward $reward): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'numeric', 'min:0'],
            'badge' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $this->deletePublicUpload($reward->image);
            $data['image'] = $this->publicUpload($request, 'image', 'rewards');
        }

        $reward->update($data);
        return redirect()->route('admin.rewards')->with('success', 'Reward berhasil diperbarui.');
    }

    public function rewardToggle(Reward $reward): RedirectResponse
    {
        $reward->update(['status' => $reward->status === 'Aktif' ? 'Nonaktif' : 'Aktif']);
        return back()->with('success', 'Status reward berhasil diperbarui.');
    }

    public function rewardDelete(Reward $reward): RedirectResponse
    {
        $this->deletePublicUpload($reward->image);
        $reward->delete();
        return back()->with('success', 'Reward berhasil dihapus.');
    }

    public function payments(Request $request): View
    {
        $payments = Payment::with(['user', 'booking.lapangan'])->latest()->get();
        return view('admin.payments', $this->layoutData($request, compact('payments')));
    }

    public function paymentVerify(Payment $payment): RedirectResponse
    {
        $payment->update(['status' => 'Lunas', 'paid_at' => now()]);
        if ($payment->booking) {
            $payment->booking->update(['status' => 'Lunas']);
        }
        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reports(Request $request): View
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $type = $request->query('type', 'Semua Transaksi');

        $query = Payment::with(['user', 'booking.lapangan'])->latest();
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($type === 'Lunas') {
            $query->where('status', 'Lunas');
        }
        if ($type === 'Menunggu') {
            $query->where('status', 'Menunggu');
        }

        return view('admin.reports', $this->layoutData($request, [
            'reports' => $query->get(),
            'filters' => compact('from', 'to', 'type'),
        ]));
    }

    private function reportQuery(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $type = $request->query('type', 'Semua Transaksi');

        $query = Payment::with(['user', 'booking.lapangan'])->latest();

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($type === 'Lunas') {
            $query->where('status', 'Lunas');
        }
        if ($type === 'Menunggu') {
            $query->where('status', 'Menunggu');
        }

        return $query;
    }

    public function exportReportsCsv(Request $request)
    {
        $reports = $this->reportQuery($request)->get();
        $filename = 'laporan-transaksi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Tanggal', 'User', 'Email', 'Lapangan', 'Metode', 'Jumlah', 'Status']);

            foreach ($reports as $report) {
                fputcsv($handle, [
                    optional($report->created_at)->format('Y-m-d H:i'),
                    $report->user?->name ?? '-',
                    $report->user?->email ?? '-',
                    $report->booking?->lapangan?->nama ?? '-',
                    $report->method ?? '-',
                    (int) $report->amount,
                    $report->status ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportReportsExcel(Request $request)
    {
        $reports = $this->reportQuery($request)->get();
        $filename = 'laporan-transaksi-' . now()->format('Ymd-His') . '.xls';

        $html = '<table border="1">';
        $html .= '<thead><tr>';
        foreach (['Tanggal', 'User', 'Email', 'Lapangan', 'Metode', 'Jumlah', 'Status'] as $header) {
            $html .= '<th style="background:#0fa741;color:#ffffff;font-weight:bold;">' . e($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($reports as $report) {
            $html .= '<tr>';
            $html .= '<td>' . e(optional($report->created_at)->format('Y-m-d H:i')) . '</td>';
            $html .= '<td>' . e($report->user?->name ?? '-') . '</td>';
            $html .= '<td>' . e($report->user?->email ?? '-') . '</td>';
            $html .= '<td>' . e($report->booking?->lapangan?->nama ?? '-') . '</td>';
            $html .= '<td>' . e($report->method ?? '-') . '</td>';
            $html .= '<td>' . e((string) (int) $report->amount) . '</td>';
            $html .= '<td>' . e($report->status ?? '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function profile(Request $request): View
    {
        $profile = $this->adminUser($request);
        return view('admin.profile', $this->layoutData($request, compact('profile')));
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $admin = $this->adminUser($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $admin->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $this->deletePublicUpload($admin->profile_photo);
            $data['profile_photo'] = $this->publicUpload($request, 'profile_photo', 'profiles');
        }

        $admin->update($data);
        return back()->with('success', 'Profil admin berhasil diperbarui.');
    }

    public function logoutConfirm(Request $request): View
    {
        return view('admin.logout-confirm', $this->layoutData($request));
    }

    public function logoutSuccess(Request $request): View
    {
        return view('admin.logout-success', $this->layoutData($request));
    }
}
