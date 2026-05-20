@extends('layouts.admin')

@section('content')

<div class="surface venue-detail-card">

    <div class="venue-detail-image">
        <img src="{{ asset($venue->photo) }}"
             alt="{{ $venue->name }}">
    </div>

    <div class="venue-detail-content">

        <h1 class="venue-detail-title">
            {{ $venue->name }}
        </h1>

        <div class="venue-detail-meta">
            <span>📍 {{ $venue->city }}</span>
            <span>📞 {{ $venue->phone }}</span>
            <span>📧 {{ $venue->email }}</span>
            <span>✅ {{ $venue->status }}</span>
        </div>

        <div class="venue-detail-description">
            <p>{{ $venue->address }}</p>

            <br>

            <p>{{ $venue->description }}</p>
        </div>

        <div class="venue-action-row">

            @if($venue->google_maps)

            <a href="{{ $venue->google_maps }}"
               target="_blank"
               class="btn-ui btn-green">

                📍 Buka Google Maps

            </a>

            @endif

        </div>

        @if($venue->map_embed)

        <div class="map-wrapper">

            {!! $venue->map_embed !!}

        </div>

        @endif

    </div>

</div>

@endsection