@extends('layouts.admin')

@section('content')

<div class="panel-card">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Data Lapangan</h2>

        <a href="/lapangan/create" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Lapangan
        </a>
    </div>

    <table class="table table-bordered table-hover data-table">

        <thead class="table-dark">
            <tr>
                <th>Nama</th>
                <th>Jenis</th>
                <th>Harga</th>
                <th width="180">Aksi</th>
            </tr>
        </thead>

        <tbody>
        @foreach($lapangan as $l)
        <tr data-search-item>

            <td>{{ $l->nama }}</td>

            <td>{{ $l->jenis }}</td>

            <td>
                Rp {{ number_format($l->harga, 0, ',', '.') }}
            </td>

            <td>
                <a href="/lapangan/{{ $l->id_lapangan }}/edit"
                   class="btn btn-warning btn-sm">
                    Edit
                </a>

                <form action="/lapangan/{{ $l->id_lapangan }}"
                      method="POST"
                      style="display:inline">

                    @csrf
                    @method('DELETE')

                    <button class="btn btn-danger btn-sm">
                        Hapus
                    </button>
                </form>
            </td>

        </tr>
        @endforeach

        </tbody>

    </table>

</div>

@endsection