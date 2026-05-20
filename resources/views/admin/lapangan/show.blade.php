@extends('layouts.admin')

@section('content')

@php
    $gallery = collect($lapangan->foto_gallery ?? []);

    if ($lapangan->foto) {
        $gallery->prepend($lapangan->foto);
    }

    $gallery = $gallery->unique()->values();
@endphp

<div class="court-show-wrapper">

    {{-- SLIDER --}}
    <div class="show-slider-card">

        <div class="court-slider" data-slider>

            @foreach($gallery as $index => $photo)

                <img
                    src="{{ asset($photo) }}"
                    class="court-image slide-photo {{ $index == 0 ? 'active' : '' }}"
                    alt="{{ $lapangan->nama }}"
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

        </div>

    </div>

    {{-- DETAIL --}}
    <div class="surface show-detail-card">

        <div class="show-header">

            <div>
                <h1>{{ $lapangan->nama }}</h1>

                <div class="court-meta">

                    <span>
                        <i class="fa-solid fa-futbol"></i>
                        {{ $lapangan->jenis }}
                    </span>

                    <span>
                        <i class="fa-solid fa-location-dot"></i>
                        {{ $lapangan->venue->city ?? '-' }}
                    </span>

                    <span>
                        <i class="fa-solid fa-star"></i>
                        {{ $lapangan->rating ?? '0' }}/5
                    </span>

                    <span>
                        <i class="fa-solid fa-circle-info"></i>
                        {{ $lapangan->status }}
                    </span>

                </div>

            </div>

            <a href="{{ route('admin.courts.edit', $lapangan->id) }}"
               class="btn-ui btn-yellow">

                <i class="fa-solid fa-pen"></i>
                Edit

            </a>

        </div>

        {{-- HARGA --}}
        <div class="show-price">

            Rp {{ number_format($lapangan->harga,0,',','.') }}/jam

        </div>

        {{-- DESKRIPSI --}}
        <div class="show-description">

            {{ $lapangan->deskripsi }}

        </div>

        {{-- FASILITAS --}}
        @if($lapangan->fasilitas)

        <div class="facility-list">

            @foreach($lapangan->fasilitas as $fasilitas)

                <span class="facility-chip">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ $fasilitas }}
                </span>

            @endforeach

        </div>

        @endif

        {{-- VENUE --}}
        <div class="venue-box">

            <h3>
                <i class="fa-solid fa-building"></i>
                Informasi Venue
            </h3>

            <div class="venue-info">

                <p>
                    <strong>Venue:</strong>
                    {{ $lapangan->venue->name ?? '-' }}
                </p>

                <p>
                    <strong>Kota:</strong>
                    {{ $lapangan->venue->city ?? '-' }}
                </p>

                <p>
                    <strong>Alamat:</strong>
                    {{ $lapangan->venue->address ?? '-' }}
                </p>

                <p>
                    <strong>No HP:</strong>
                    {{ $lapangan->venue->phone ?? '-' }}
                </p>

            </div>

            @if($lapangan->venue?->google_maps)

                <a href="{{ $lapangan->venue->google_maps }}"
                   target="_blank"
                   class="btn-ui btn-green">

                    <i class="fa-solid fa-map-location-dot"></i>
                    Buka Google Maps

                </a>

            @endif

        </div>

        {{-- MAP --}}
        @if($lapangan->venue?->map_embed)

        <div class="map-wrapper">

            {!! $lapangan->venue->map_embed !!}

        </div>

        @endif

    </div>

</div>

@endsection