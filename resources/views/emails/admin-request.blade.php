<!DOCTYPE html>

<html>
<head>
    <meta charset="UTF-8">
</head>
<body>

<h2>Permintaan Admin Venue</h2>

<p>
    Ada user yang ingin menjadi admin venue.
</p>

<hr>

<p>
    <strong>Nama:</strong>
    {{ $adminRequest->user->name }}
</p>

<p>
    <strong>Email:</strong>
    {{ $adminRequest->user->email }}
</p>

<p>
    <strong>No HP:</strong>
    {{ $adminRequest->user->phone }}
</p>

<hr>

<p>
    Silakan login ke dashboard super admin
    untuk melakukan verifikasi.
</p>

</body>
</html>
