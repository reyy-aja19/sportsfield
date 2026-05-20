@extends('layouts.admin', ['title' => 'Open Match', 'heading' => 'Open Match'])

@section('content')
<div class="openmatch-admin-shell">

    <div class="openmatch-filter-row">
       <div class="mini-search">
    <i class="fa-solid fa-magnifying-glass" id="searchBtn"></i>

    <input
        data-local-filter
        type="text"
        placeholder="Cari match">
</div>
        <select class="mini-select" data-local-filter-select="jenis"><option value="">Semua cabang</option><option>Futsal</option><option>Badminton</option><option>Basket</option><option>Voli</option></select>
        <select class="mini-select" data-local-filter-select="status"><option value="">Semua status</option><option>Open</option><option>Penuh</option><option>Selesai</option><option>Dibatalkan</option></select>
    </div>

    <form id="openMatchForm" class="surface compact-form openmatch-form-mobile" action="{{ route('admin.openmatches.store') }}" method="POST">
        @csrf
        <h3>Buat Open Match Anda</h3>
        <div class="openmatch-form-section">Detail Pertandingan Dasar</div>
        <div class="grid-4">
            <div class="form-group"><label>Judul Match</label><input class="input-ui" name="title" placeholder="Contoh: Tim kaciw" required></div>
            <div class="form-group"><label>Cabang Olahraga</label><select class="select-ui" name="jenis" required><option>Futsal</option><option>Badminton</option><option>Basket</option><option>Voli</option></select></div>
            <div class="form-group"><label>Jam Mulai</label><input class="input-ui" type="time" name="start_time" value="19:00"></div>
            <div class="form-group"><label>Jam Selesai</label><input class="input-ui" type="time" name="end_time" value="20:00"></div>
        </div>
        <div class="grid-4">
            <div class="form-group"><label>Tanggal</label><input class="input-ui" type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="form-group"><label>Slot Dibutuhkan</label><input class="input-ui" type="number" name="jumlah_pemain" value="5" min="1" max="99" required></div>
            <div class="form-group"><label>Sudah Join</label><input class="input-ui" type="number" name="jumlah_bergabung" value="0" min="0" max="99"></div>
            <div class="form-group"><label>Status</label><select class="select-ui" name="status" required><option>Open</option><option>Penuh</option><option>Selesai</option><option>Dibatalkan</option></select></div>
        </div>
        <div class="form-group"><label>Booking Terkait</label><select class="select-ui" name="booking_id"><option value="">Tanpa booking terkait</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}">{{ $booking->user?->name }} • {{ $booking->lapangan?->nama }} • {{ optional($booking->booking_date)->format('d M Y') }} {{ $booking->start_time }}-{{ $booking->end_time }}</option>@endforeach</select></div>
        <div class="form-group"><label>Deskripsi Tambahan</label><textarea class="textarea-ui" name="deskripsi" placeholder="Ketikkan detail lawan, level, aturan main, atau kebutuhan tambahan."></textarea></div>
        <div style="display:flex; justify-content:flex-end;"><button class="btn-ui btn-green"><i class="fa-solid fa-paper-plane"></i> Publikasikan</button></div>
    </form>

    <div class="openmatch-grid mt-16">
        @forelse($matches as $match)
            @php
                $percent = $match->jumlah_pemain > 0 ? min(100, round(($match->jumlah_bergabung / $match->jumlah_pemain) * 100)) : 0;
                $statusClass = strtolower(str_replace(' ', '-', $match->status));
            @endphp
<div class="openmatch-card"
     data-search-item
     data-jenis="{{ strtolower(trim($match->jenis)) }}"
     data-status="{{ strtolower(trim($match->status)) }}">
    <div class="openmatch-top">
        <div class="match-icon">
            <i class="fa-solid fa-people-group"></i>
        </div>

        <div class="openmatch-info">
            <h3>{{ $match->title }}</h3>
            <p>{{ $match->jenis }}</p>
        </div>

        <span class="openmatch-status status-{{ $statusClass }}">
            {{ $match->status }}
        </span>
    </div>

    <div class="openmatch-meta">
        <div>
            <i class="fa-regular fa-calendar"></i>
            {{ optional($match->tanggal)->format('d M Y') }}
        </div>

        <div>
            <i class="fa-regular fa-clock"></i>
            {{ $match->start_time ?? '-' }} - {{ $match->end_time ?? '-' }}
        </div>

        <div>
            <i class="fa-solid fa-location-dot"></i>
            {{ $match->booking?->lapangan?->nama ?? 'Belum terkait booking' }}
        </div>
    </div>

    <div class="openmatch-progress">
        <div class="progress-text">
            <span>{{ $match->jumlah_bergabung }}/{{ $match->jumlah_pemain }} pemain</span>
            <strong>{{ $percent }}%</strong>
        </div>

        <div class="progress-bar">
            <span style="width: {{ $percent }}%"></span>
        </div>
    </div>

    @if($match->deskripsi)
        <div class="openmatch-desc">
            {{ $match->deskripsi }}
        </div>
    @endif

    <details class="openmatch-edit">
        <summary>Edit Match</summary>

        <form action="{{ route('admin.openmatches.update', $match) }}" method="POST" class="edit-form">
            @csrf
            @method('PUT')

            <div class="grid-2">
                <div class="form-group">
                    <label>Judul</label>
                    <input class="input-ui" name="title" value="{{ $match->title }}">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select class="select-ui" name="status">
                        @foreach(['Open','Penuh','Selesai','Dibatalkan'] as $status)
                            <option value="{{ $status }}" {{ $match->status == $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="btn-row">
                <button class="btn-ui btn-green">
                    Simpan
                </button>
            </div>
        </form>
    </details>

    <div class="btn-row openmatch-actions">
        <form method="POST" action="{{ route('admin.openmatches.toggle', $match) }}">
            @csrf
            <button class="btn-ui btn-gray">
                Ubah Status
            </button>
        </form>

        <form method="POST"
              action="{{ route('admin.openmatches.delete', $match) }}"
              onsubmit="return confirm('Hapus open match ini?')">
            @csrf
            @method('DELETE')

            <button class="btn-ui btn-red">
                Hapus
            </button>
        </form>
    </div>

</div>
        @empty
            <div class="center-box">Belum ada data open match.</div>
        @endforelse
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const searchInput = document.querySelector('[data-local-filter]');
    const jenisSelect = document.querySelector('[data-local-filter-select="jenis"]');
    const statusSelect = document.querySelector('[data-local-filter-select="status"]');
    const searchBtn = document.getElementById('searchBtn');

    const cards = document.querySelectorAll('[data-search-item]');

    function runFilter() {

    const keyword = searchInput.value.toLowerCase().trim();
    const jenis = jenisSelect.value.toLowerCase().trim();
    const status = statusSelect.value.toLowerCase().trim();

    const sport = card.dataset.jenis;
    const cardStatus = card.dataset.status;

    cards.forEach(card => {

        const title =
            card.querySelector('h3')?.innerText.toLowerCase() || '';

        const sport =
            (card.dataset.jenis || '').toLowerCase().trim();

        const cardStatus =
            (card.dataset.status || '').toLowerCase().trim();

        let show = true;

        // SEARCH
        if (keyword && !title.includes(keyword)) {
            show = false;
        }

        // FILTER CABANG
        if (jenis !== '' && sport !== jenis) {
            show = false;
        }

        // FILTER STATUS
        if (status !== '' && cardStatus !== status) {
            show = false;
        }

        card.style.display = show ? 'block' : 'none';

    });
}

    // ketik langsung filter
    searchInput.addEventListener('input', runFilter);

    // tekan enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            runFilter();
        }
    });

    // klik icon search
    searchBtn.addEventListener('click', runFilter);

    // filter select
    jenisSelect.addEventListener('change', runFilter);
    statusSelect.addEventListener('change', runFilter);

});
</script>
@endsection
