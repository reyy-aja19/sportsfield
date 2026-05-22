@extends('layouts.admin')

@section('content')

<div class="panel-card">

    <div class="panel-head">
        <h3>Semua Notifikasi</h3>
    </div>

    <div class="notification-list-full">

        @forelse($notifications as $notification)

            <a href="{{ $notification['url'] }}"
               class="notification-item">

                <div class="notification-icon">
                    <i class="{{ $notification['icon'] }}"></i>
                </div>

                <div class="notification-copy">
                    <strong>
                        {{ $notification['title'] }}
                    </strong>

                    <span>
                        {{ $notification['description'] }}
                    </span>
                </div>

                <small>
                    {{ $notification['time'] }}
                </small>

            </a>

        @empty

            <div class="notification-empty">
                Tidak ada notifikasi
            </div>

        @endforelse

    </div>

</div>

@endsection