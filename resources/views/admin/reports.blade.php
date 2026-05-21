@extends('layouts.admin', ['title' => 'Export Laporan', 'heading' => 'Export Laporan'])

@section('content')

<div class="surface">

        <div style="display:flex; gap:10px; flex-wrap:wrap;">

            <a
                class="btn-ui btn-green anim-click"
                href="{{ route('admin.reports.export.csv', request()->query()) }}">

                <i class="fa-solid fa-file-csv"></i>
                Export CSV
            </a>

            <a
                class="btn-ui btn-green anim-click"
                href="{{ route('admin.reports.export.excel', request()->query()) }}">

                <i class="fa-solid fa-file-excel"></i>
                Export Excel
            </a>

        </div>

    </div>

    {{-- FILTER --}}
    <form method="GET" action="{{ route('admin.reports') }}">

        <div class="grid-3" style="margin-bottom:20px;">

            <div class="form-group">
                <label>Dari Tanggal</label>

                <input
                    class="input-ui"
                    type="date"
                    name="from"
                    value="{{ $filters['from'] }}">
            </div>

            <div class="form-group">
                <label>Sampai Tanggal</label>

                <input
                    class="input-ui"
                    type="date"
                    name="to"
                    value="{{ $filters['to'] }}">
            </div>

            <div class="form-group">
                <label>Status Transaksi</label>

                <select class="select-ui" name="type">

                    @foreach(['Semua Transaksi', 'Lunas', 'Menunggu', 'DP'] as $type)

                        <option
                            value="{{ $type }}"
                            {{ $filters['type'] === $type ? 'selected' : '' }}>

                            {{ $type }}

                        </option>

                    @endforeach

                </select>
            </div>

        </div>

        {{-- ACTION BUTTON --}}
        <div style="
            display:flex;
            justify-content:flex-end;
            gap:10px;
            flex-wrap:wrap;
            margin-bottom:20px;
        ">

            <a
                class="btn-ui btn-gray"
                href="{{ route('admin.reports') }}">

                Reset
            </a>

            <button class="btn-ui btn-green">

                <i class="fa-solid fa-filter"></i>
                Terapkan Filter

            </button>

        </div>

    </form>

    {{-- TABLE --}}
    <div class="data-table">

        <table>

            <thead>

                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>Lapangan</th>
                    <th>Metode</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>

            </thead>

            <tbody>

                @forelse($reports as $i => $report)

                <tr>

                    <td>
                        {{ $i + 1 }}
                    </td>

                    <td>
                        {{ optional($report->created_at)->format('d M Y H:i') }}
                    </td>

                    <td>
                        {{ $report->user?->name ?? '-' }}
                    </td>

                    <td>
                        {{ $report->booking?->lapangan?->nama ?? '-' }}
                    </td>

                    <td>
                        {{ $report->method ?? '-' }}
                    </td>

                    <td>
                        Rp {{ number_format($report->amount, 0, ',', '.') }}
                    </td>

                    <td>

                        @if($report->status === 'Lunas')

                            <span class="status-active">
                                Lunas
                            </span>

                        @elseif($report->status === 'DP')

                            <span class="status-pending">
                                DP
                            </span>

                        @else

                            <span class="status-inactive">
                                Menunggu
                            </span>

                        @endif

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="7" style="
                        text-align:center;
                        padding:30px;
                        color:#667085;
                    ">

                        Belum ada data laporan.

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection