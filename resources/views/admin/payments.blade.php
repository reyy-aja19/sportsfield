@extends('layouts.admin', [
    'title' => 'Pembayaran',
    'heading' => 'Pembayaran'
])

@section('content')

<div class="data-table">

    <table>

        <thead>
            <tr>
                <th>No</th>
                <th>Nama User</th>
                <th>Lapangan</th>
                <th>Jadwal</th>
                <th>Metode</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>

        @forelse($payments as $i => $payment)

        <tr>

            <td>{{ $i + 1 }}</td>

            <td>
                {{ $payment->user?->name }}
            </td>

            <td>
                {{ $payment->booking?->lapangan?->nama }}
            </td>

            <td>
                {{ optional($payment->booking?->booking_date)->format('d M Y') }}
                <br>

                <small>
                    {{ $payment->booking?->start_time }}
                    -
                    {{ $payment->booking?->end_time }}
                </small>
            </td>

            <td>
                {{ $payment->method }}
            </td>

            <td>
                Rp {{ number_format($payment->amount, 0, ',', '.') }}
            </td>

            <td>

                @if($payment->status === 'Lunas')

                    <span class="status-active">
                        Lunas
                    </span>

                @elseif($payment->status === 'DP')

                    <span class="status-pending">
                        DP
                    </span>

                @elseif($payment->status === 'Menunggu Verifikasi')

                    <span class="status-pending">
                        Menunggu Verifikasi
                    </span>

                @else

                    <span class="status-inactive">
                        Pending
                    </span>

                @endif

            </td>


            <td>

                {{-- MIDTRANS --}}
                @if($payment->method === 'Midtrans')

                    <span class="status-active">
                        Otomatis
                    </span>

                {{-- BAYAR DITEMPAT --}}
                @elseif($payment->method === 'Bayar di Tempat')

                    @if($payment->status !== 'Lunas')

                        <form
                            method="POST"
                            action="{{ route('admin.payments.verify', $payment) }}">

                            @csrf

                            <button class="btn-ui btn-green">

                                Konfirmasi Bayar

                            </button>

                        </form>

                    @else

                        <span class="status-active">
                            Sudah Dibayar
                        </span>

                    @endif

                {{-- DP / TRANSFER MANUAL --}}
                @else

                    @if($payment->status !== 'Lunas')

                        <form
                            method="POST"
                            action="{{ route('admin.payments.verify', $payment) }}">

                            @csrf

                            <button class="btn-ui btn-green">

                                Verifikasi

                            </button>

                        </form>

                    @else

                        <span class="status-active">
                            Terverifikasi
                        </span>

                    @endif

                @endif

            </td>

        </tr>

        @empty

        <tr>

            <td colspan="9" style="text-align:center;">
                Belum ada data pembayaran.
            </td>

        </tr>

        @endforelse

        </tbody>

    </table>

</div>

@endsection