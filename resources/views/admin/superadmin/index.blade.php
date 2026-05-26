@extends('layouts.admin')

@section('content')

{{-- REQUEST ADMIN --}}
<div class="panel-card">

    <h3 style="margin-bottom:20px;">
        Request Admin
    </h3>

    <div class="data-table">

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                @forelse($requests as $i => $request)

                <tr>

                    <td>{{ $i + 1 }}</td>

                    <td>
                        {{ $request->user?->name }}
                    </td>

                    <td>
                        {{ $request->user?->email }}
                    </td>

                    <td>
                        <span class="status-pending">
                            {{ $request->status }}
                        </span>
                    </td>

                    <td>

                        <form
                            method="POST"
                            action="{{ route('admin.approve.admin', $request) }}">

                            @csrf

                            <button class="btn-ui btn-green">

                                Approve

                            </button>

                        </form>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="5" style="text-align:center;">
                        Belum ada request admin.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

{{-- DATA ADMIN --}}
<div class="panel-card" style="margin-top:24px;">

    <h3 style="margin-bottom:20px;">
        Daftar Admin
    </h3>

    <div class="data-table">

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No HP</th>
                </tr>
            </thead>

            <tbody>

                @foreach($admins as $i => $admin)

                <tr>

                    <td>{{ $i + 1 }}</td>
                    <td>{{ $admin->name }}</td>
                    <td>{{ $admin->email }}</td>
                    <td>{{ $admin->phone }}</td>

                </tr>

                @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection