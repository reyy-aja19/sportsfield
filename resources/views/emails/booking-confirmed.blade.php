<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>

<h2>Halo {{ $booking->user->name }}</h2>

<p>Pembayaran booking Anda berhasil dikonfirmasi.</p>

<hr>

<p><strong>Lapangan:</strong>
{{ $booking->lapangan->nama }}</p>

<p><strong>Tanggal:</strong>
{{ $booking->booking_date }}</p>

<p><strong>Jam:</strong>
{{ $booking->start_time }} -
{{ $booking->end_time }}</p>

<p><strong>Total:</strong>
Rp {{ number_format($booking->total_price,0,',','.') }}</p>

<p>Status:
<b>{{ $booking->status }}</b></p>

<hr>

<p>Terima kasih telah menggunakan Sports Field.</p>

</body>
</html>