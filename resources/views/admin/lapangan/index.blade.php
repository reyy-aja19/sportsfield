@extends('layouts.admin')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>Data Lapangan</h2>

    <a href="/lapangan/create" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i>
        Tambah Lapangan
    </a>

</div>

<div class="panel-grid">

@foreach($lapangan as $l)

@php
    $gallery = collect($l->foto_gallery ?? []);

    if ($l->foto) {
        $gallery->prepend($l->foto);
    }

    $gallery = $gallery->unique()->values();
@endphp

<div class="court-card">

    {{-- SLIDER --}}
    <div class="court-slider">

        @if($gallery->count())

            @foreach($gallery as $index => $photo)

                <img
                    src="{{ asset($photo) }}"
                    class="court-image {{ $index == 0 ? 'active' : '' }}"
                    alt="{{ $l->nama }}"
                >

            @endforeach

            @if($gallery->count() > 1)

                <button class="slider-btn prev">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <button class="slider-btn next">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <div class="slider-count">
                    1 / {{ $gallery->count() }}
                </div>

            @endif

        @else

            <img
                src="https://via.placeholder.com/400x250?text=Lapangan"
                class="court-image active"
            >

        @endif

    </div>

    {{-- CONTENT --}}
    <div class="court-copy">

        <div class="court-top">

            <div>

                <h3>{{ $l->nama }}</h3>

                <div class="court-meta">

                    <span>
                        <i class="fa-solid fa-futbol"></i>
                        {{ $l->jenis }}
                    </span>

                    <span>
                        <i class="fa-solid fa-location-dot"></i>
                        {{ $l->venue->nama ?? '-' }}
                    </span>

                </div>

            </div>

           <div class="court-actions">

    <a href="{{ route('admin.courts.edit', $l->id) }}"
       class="btn-ui warning">
        <i class="fa-solid fa-pen"></i>
        Edit
    </a>

    <form action="{{ route('admin.courts.delete', $l->id) }}"
          method="POST">

        @csrf
        @method('DELETE')

        <button class="btn-ui danger">
            <i class="fa-solid fa-trash"></i>
            Hapus
        </button>

    </form>

</div>

        </div>

        <div class="court-description">

            Lapangan {{ $l->nama }}
            tersedia untuk booking harian dengan fasilitas modern
            dan kondisi lapangan yang terawat.

        </div>

        <div class="court-tags">

            <span>
                <i class="fa-solid fa-money-bill-wave"></i>
                Rp {{ number_format($l->harga,0,',','.') }}
            </span>

            <span>
                <i class="fa-solid fa-clock"></i>
                Booking Available
            </span>

            <span>
                <i class="fa-solid fa-star"></i>
                Premium Venue
            </span>

        </div>

    </div>

</div>

@endforeach

</div>

@endsection