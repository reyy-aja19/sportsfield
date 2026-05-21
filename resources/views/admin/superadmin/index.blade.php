@extends('layouts.admin')

@section('content')

<div class="panel-card">
    <h2>Management Admin</h2>

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