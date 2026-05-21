@extends('layouts.admin', ['title' => 'Dashboard Admin', 'heading' => 'Dashboard'])

@section('content')

{{-- HERO --}}
<div class="dashboard-hero surface dashboard-hero-clean">
    <div>
        <span class="eyebrow">
            <i class="fa-solid fa-wave-square"></i>
            Realtime Overview
        </span>

        <h2>Sports Field Rental Admin</h2>

        <p style="margin-top:10px;color:#5e7767;font-size:14px;">
            Monitor user, venue, booking, revenue, dan aktivitas sistem secara realtime.
        </p>
    </div>

    <div class="btn-row wrap">
        <a href="{{ route('admin.reports') }}" class="btn-ui btn-green anim-click">
            <i class="fa-regular fa-file-lines"></i>
            Export Laporan
        </a>

        <a href="{{ route('admin.venue.index') }}" class="btn-ui btn-gray anim-click">
            <i class="fa-solid fa-building"></i>
            Kelola Venue
        </a>
    </div>
</div>

{{-- STATS --}}
<div class="stats-grid">

    @foreach($stats as $stat)
        <div class="stat-card anim-click">
            <div class="stat-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>

            <div class="label">
                {{ $stat['label'] }}
            </div>

            <div class="value">
                {{ $stat['value'] }}
            </div>
        </div>
    @endforeach

    {{-- TOTAL VENUE --}}
    <div class="stat-card anim-click">
        <div class="stat-icon">
            <i class="fa-solid fa-building"></i>
        </div>

        <div class="label">
            Total Venue
        </div>

        <div class="value">
            {{ $totalVenue ?? 0 }}
        </div>
    </div>

</div>

{{-- VENUE PREVIEW --}}
<div class="surface" style="margin-bottom:20px;">

    <div class="section-actions">
        <div>
            <h3 style="margin:0;font-size:22px;color:#0b3d20;">
                Lapangan Terbaru
            </h3>

            <p style="margin-top:6px;color:#5e7767;font-size:13px;">
                lapangan terbaru yang ditambahkan admin.
            </p>
        </div>
    </div>

    <div class="panel-grid">

        @forelse($lapangan as $item)

            <div class="court-card court-card-modern anim-click">

{{-- IMAGE --}}
<div class="court-slider">

    @php
        $gallery = collect($item->foto_gallery ?? []);

        if ($item->foto) {
            $gallery->prepend($item->foto);
        }

        $gallery = $gallery->unique()->values();
    @endphp

    @if($gallery->count())

        <img
            src="{{ asset($gallery[0]) }}"
            class="court-image active"
            alt="{{ $item->nama }}"
        >

    @else

        <div class="court-empty-icon">
            <i class="fa-solid fa-image"></i>
        </div>

    @endif

</div>

{{-- CONTENT --}}
<div class="court-copy">

    <div class="court-top">

        <div>

            <h3>
                {{ $item->nama }}
            </h3>

            <div class="court-meta">

                <span>
                    <i class="fa-solid fa-location-dot"></i>
                    {{ $item->lokasi ?? 'Lokasi belum tersedia' }}
                </span>

                <span>
                    <i class="fa-solid fa-futbol"></i>
                    {{ $item->jenis ?? 'Sport Venue' }}
                </span>

            </div>

        </div>

        <div class="court-actions">

            <a
               href="{{ route('admin.courts.edit', $item->id) }}"
                class="btn-ui warning btn-edit-icon"
            >
                <i class="fa-solid fa-pen"></i>
            </a>

        </div>

    </div>
```


                    <div class="court-description">
                        {{ Str::limit($item->deskripsi ?? 'Tidak ada deskripsi lapangan.', 120) }}
                    </div>

                    <div class="court-tags">

                        <span>
                            <i class="fa-solid fa-money-bill"></i>
                            Rp {{ number_format($item->harga ?? 0,0,',','.') }}
                        </span>

                        <span>
                            <i class="fa-solid fa-clock"></i>
                            {{ $item->jam_buka ?? '08:00' }} -
                            {{ $item->jam_tutup ?? '22:00' }}
                        </span>

                    </div>

                </div>

            </div>

        @empty

            <div class="center-box highlight">
                <h2 style="font-size:28px;">
                    Belum Ada Lapangan
                </h2>

                <p style="color:#5e7767;">
                    Tambahkan Lapangan pertama untuk mulai menerima booking.
                </p>

                <a href="{{ route('admin.courts.create') }}" class="btn-ui btn-green">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Lapangan
                </a>
            </div>

        @endforelse

    </div>
</div>

{{-- CHARTS --}}
<div class="card-grid-2 dashboard-charts">

    <div class="panel-card highlight anim-click">
        <div class="panel-title">
            <i class="fa-solid fa-users"></i>
            Grafik Data User
        </div>

        <div class="chart-wrap">
            <canvas id="chartUsers"></canvas>
        </div>
    </div>

    <div class="panel-card anim-click">
        <div class="panel-title">
            <i class="fa-solid fa-calendar-check"></i>
            Grafik Penyewaan Lapangan
        </div>

        <div class="chart-wrap">
            <canvas id="chartCourt"></canvas>
        </div>
    </div>

    <div class="panel-card anim-click">
        <div class="panel-title">
            <i class="fa-solid fa-circle-half-stroke"></i>
            Status User
        </div>

        <div class="chart-wrap">
            <canvas id="chartPie"></canvas>
        </div>
    </div>

    <div class="panel-card anim-click">
        <div class="panel-title">
            <i class="fa-solid fa-money-bill-trend-up"></i>
            Grafik Pendapatan
        </div>

        <div class="chart-wrap">
            <canvas id="chartRevenue"></canvas>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>

const labels = ['Jan','Feb','Mar','Apr','May'];

const green = '#10b04a';
const greenDark = '#0a7f34';

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,

    interaction: {
        mode: 'index',
        intersect: false
    },

    plugins: {
        legend: {
            display: false
        },

        tooltip: {
            backgroundColor: 'rgba(8,74,31,.92)',
            padding: 12,
            cornerRadius: 12,
            displayColors: false
        }
    },

    scales: {
        x: {
            grid: {
                display: false
            },

            ticks: {
                color: '#6b806f'
            }
        },

        y: {
            beginAtZero: true,

            grid: {
                color: 'rgba(16,98,47,.10)'
            },

            ticks: {
                color: '#6b806f',
                precision: 0
            }
        }
    }
};

const pieOptions = {
    responsive: true,
    maintainAspectRatio: false,

    plugins: {
        legend: {
            position: 'bottom',

            labels: {
                usePointStyle: true,
                boxWidth: 8
            }
        },

        tooltip: {
            backgroundColor: 'rgba(8,74,31,.92)',
            padding: 12,
            cornerRadius: 12
        }
    }
};

function makeGradient(ctx) {

    const gradient = ctx.createLinearGradient(0, 0, 0, 260);

    gradient.addColorStop(0, 'rgba(16,176,74,.28)');
    gradient.addColorStop(1, 'rgba(16,176,74,.02)');

    return gradient;
}

function renderLineChart(id, data) {

    const canvas = document.getElementById(id);

    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    new Chart(canvas, {

        type: 'line',

        data: {
            labels,

            datasets: [{
                data,

                borderColor: green,
                backgroundColor: makeGradient(ctx),

                fill: true,

                borderWidth: 3,

                pointRadius: 4,
                pointHoverRadius: 7,

                pointBackgroundColor: '#ffffff',

                pointBorderColor: greenDark,
                pointBorderWidth: 2,

                tension: .38
            }]
        },

        options: lineOptions
    });
}

renderLineChart('chartUsers', @json($chartUsers));
renderLineChart('chartCourt', @json($chartLapangan));
renderLineChart('chartRevenue', @json($chartRevenue));

new Chart(document.getElementById('chartPie'), {

    type: 'doughnut',

    data: {

        labels: [
            'User Aktif',
            'User Nonaktif'
        ],

        datasets: [{
            data: @json($userRatio),

            backgroundColor: [
                '#10b04a',
                '#d1d5db'
            ],

            borderColor: '#ffffff',
            borderWidth: 5,
            hoverOffset: 8
        }]
    },

    options: pieOptions
});

</script>
@endpush