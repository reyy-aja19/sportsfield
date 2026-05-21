@extends('layouts.admin', ['title' => 'Pembayaran', 'heading' => 'Pembayaran'])

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
                <th>Bukti</th>
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

                    @else
                        <span class="status-inactive">
                            Menunggu
                        </span>
                    @endif

                </td>

                <td>

                    @if($payment->proof_image)

                        <a
                            href="{{ asset($payment->proof_image) }}"
                            target="_blank"
                            class="btn-ui btn-gray">
                            Lihat
                        </a>

                    @else

                        <span class="muted">
                            Tidak ada
                        </span>

                    @endif

                </td>

                <td>

                    @if(
                        $payment->status !== 'Lunas'
                        &&
                        $payment->method !== 'Bayar di Tempat'
                    )

                        <form
                            method="POST"
                            action="{{ route('admin.payments.verify', $payment) }}">

                            @csrf

                            <button class="btn-ui btn-green">
                                Verifikasi
                            </button>

                        </form>

                    @elseif($payment->method === 'Bayar di Tempat')

                        <span class="status-pending">
                            Bayar di Tempat
                        </span>

                    @else

                        <span class="status-active">
                            Terverifikasi
                        </span>

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