<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reservasi Dibatalkan</title>
</head>
<body>
    <p>Halo {{ $reservation->user->name }},</p>

    <p>Reservasi Anda pada ruangan <strong>{{ $reservation->room->nama_ruangan }}</strong>
    tanggal <strong>{{ $reservation->tanggal->format('d M Y') }}</strong>
    pukul <strong>{{ $reservation->start_time }} - {{ $reservation->end_time }}</strong>
    telah <strong>{{ strtoupper($reservation->status) }}</strong>.</p>

    <p>Alasan: {{ $reservation->reason ?? 'Bentrok dengan jadwal tetap (Fixed Schedule).' }}</p>

    <p>Terima kasih.</p>
</body>
</html>
