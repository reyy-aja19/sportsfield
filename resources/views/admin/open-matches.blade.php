@extends('layouts.admin', ['title' => 'Open Match', 'heading' => 'Open Match'])

@section('content')
<div class="openmatch-admin-shell">

    <!-- SEARCH + FILTER -->
    <div class="openmatch-filter-row">
        <input id="searchInput" type="text" placeholder="Cari match" class="input-ui" style="flex:1; margin-right:8px;"/>
        <select id="jenisFilter" class="mini-select">
            <option value="">Semua cabang</option>
            <option>Futsal</option>
            <option>Badminton</option>
            <option>Basket</option>
            <option>Voli</option>
        </select>
        <select id="statusFilter" class="mini-select">
            <option value="">Semua status</option>
            <option>Open</option>
            <option>Penuh</option>
            <option>Selesai</option>
            <option>Dibatalkan</option>
        </select>
    </div>

    <!-- FORM CREATE OPEN MATCH -->
    <form id="openMatchForm" class="surface compact-form openmatch-form-mobile" action="{{ route('admin.openmatches.store') }}" method="POST">
        @csrf
        <h3>Buat Open Match Anda</h3>
        <div class="grid-4">
            <div class="form-group"><label>Judul Match</label><input class="input-ui" name="title" required></div>
            <div class="form-group"><label>Cabang Olahraga</label><select class="select-ui" name="jenis" required>
                <option>Futsal</option><option>Badminton</option><option>Basket</option><option>Voli</option>
            </select></div>
            <div class="form-group"><label>Jam Mulai</label><input class="input-ui" type="time" name="start_time"></div>
            <div class="form-group"><label>Jam Selesai</label><input class="input-ui" type="time" name="end_time"></div>
        </div>
        <div class="grid-4">
            <div class="form-group"><label>Tanggal</label><input class="input-ui" type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="form-group"><label>Slot Dibutuhkan</label><input class="input-ui" type="number" name="jumlah_pemain" min="1" required></div>
            <div class="form-group"><label>Sudah Join</label><input class="input-ui" type="number" name="jumlah_bergabung" min="0"></div>
            <div class="form-group"><label>Status</label><select class="select-ui" name="status" required>
                <option>Open</option><option>Penuh</option><option>Selesai</option><option>Dibatalkan</option>
            </select></div>
        </div>
        <div class="form-group"><label>Booking Terkait</label>
            <select class="select-ui" name="booking_id">
                <option value="">Tanpa booking terkait</option>
                @foreach($bookings as $booking)
                    <option value="{{ $booking->id }}">
                        {{ $booking->user?->name }} • {{ $booking->lapangan?->nama }} • {{ optional($booking->booking_date)->format('d M Y') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group"><label>Deskripsi Tambahan</label><textarea class="textarea-ui" name="deskripsi"></textarea></div>
        <div style="display:flex; justify-content:flex-end;"><button class="btn-ui btn-green">Publikasikan</button></div>
    </form>

    <!-- GRID OPEN MATCH -->
    <div class="openmatch-grid mt-16">
        @forelse($matches as $match)
            @php
                $percent = $match->jumlah_pemain > 0 ? min(100, round(($match->jumlah_bergabung / $match->jumlah_pemain) * 100)) : 0;
                $statusClass = strtolower(str_replace(' ', '-', $match->status));
            @endphp
            <div class="openmatch-card" data-jenis="{{ strtolower($match->jenis) }}" data-status="{{ strtolower($match->status) }}" data-title="{{ strtolower($match->title) }}">
                <div class="openmatch-top">
                    <h3>{{ $match->title }}</h3>
                    <span class="openmatch-status status-{{ $statusClass }}">{{ $match->status }}</span>
                </div>
                <div class="openmatch-meta">
                    <div>{{ optional($match->tanggal)->format('d M Y') }}</div>
                    <div>{{ $match->start_time ?? '-' }} - {{ $match->end_time ?? '-' }}</div>
                    <div>{{ $match->booking?->lapangan?->nama ?? 'Belum terkait booking' }}</div>
                </div>
                <div class="openmatch-progress">
                    <span>{{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }} pemain ({{ $percent }}%)</span>
                    <div class="progress-bar"><span style="width: {{ $percent }}%"></span></div>
                </div>
            </div>
        @empty
            <div class="center-box">Belum ada data open match.</div>
        @endforelse
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const jenisFilter = document.getElementById('jenisFilter');
    const statusFilter = document.getElementById('statusFilter');
    const cards = document.querySelectorAll('.openmatch-card');

    function filterCards() {
        const keyword = searchInput.value.toLowerCase().trim();
        const jenis = jenisFilter.value.toLowerCase().trim();
        const status = statusFilter.value.toLowerCase().trim();

        cards.forEach(card => {
            const title = card.dataset.title;
            const cardJenis = card.dataset.jenis;
            const cardStatus = card.dataset.status;

            let show = true;
            if (keyword && !title.includes(keyword)) show = false;
            if (jenis && cardJenis !== jenis) show = false;
            if (status && cardStatus !== status) show = false;

            card.style.display = show ? 'block' : 'none';
        });
    }

    searchInput.addEventListener('input', filterCards);
    jenisFilter.addEventListener('change', filterCards);
    statusFilter.addEventListener('change', filterCards);
});
</script>
@endsection