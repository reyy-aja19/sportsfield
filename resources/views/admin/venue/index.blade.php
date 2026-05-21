@extends('layouts.admin')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <a href="{{ route('admin.venue.create') }}" class="btn-ui success">
        <i class="fa-solid fa-plus"></i>
        Tambah Venue
    </a>

</div>

<div class="panel-grid">

    @forelse($venues as $v)

    <div class="court-card">

        <div class="court-slider">

         @if($v->photo)
    <img
        src="{{ asset($v->photo) }}"
        alt="{{ $v->name }}"
        class="court-image"
        loading="lazy"
    >
@else
    <img
        src="https://via.placeholder.com/400x250?text=Venue"
        alt="No Image"
        class="court-image"
    >
@endif

        </div>

        <div class="court-copy">

            <div class="court-top">

                <div>

                    <h3>{{ $v->name }}</h3>

                    <div class="court-meta">

                        <span>
                            <i class="fa-solid fa-location-dot"></i>
                            {{ $v->city }}
                        </span>

                        <span>
                            Status: {{ $v->status }}
                        </span>

                    </div>

                </div>

                <div class="court-actions">

    <a href="{{ route('admin.venue.show', $v->id) }}"
       class="btn-ui success">

        <i class="fa-solid fa-eye"></i>
        Detail

    </a>

    <a
        href="{{ route('admin.venue.edit', $v->id) }}"
        class="btn-ui warning"
    >
        <i class="fa-solid fa-pen"></i>
        Edit
    </a>

    <form
        action="{{ route('admin.venue.destroy', $v->id) }}"
        method="POST"
        onsubmit="return confirm('Hapus venue ini?')"
    >

        @csrf
        @method('DELETE')

        <button type="submit" class="btn-ui danger">
            <i class="fa-solid fa-trash"></i>
            Hapus
        </button>

    </form>

</div>

            </div>

            <p class="court-description">
                {{ $v->description ?? 'Tidak ada deskripsi venue.' }}
            </p>

            <div class="court-tags">

                @if($v->phone)
                    <span>
                        <i class="fa-solid fa-phone"></i>
                        {{ $v->phone }}
                    </span>
                @endif

                @if($v->email)
                    <span>
                        <i class="fa-solid fa-envelope"></i>
                        {{ $v->email }}
                    </span>
                @endif

                @if($v->address)
                    <span>
                        <i class="fa-solid fa-map-location-dot"></i>
                        {{ $v->address }}
                    </span>
                @endif

            </div>

        </div>

    </div>

    @empty

    <div class="center-box">
        <h3>Belum ada venue</h3>
    </div>

    @endforelse

</div>

@endsection