<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Dibatalkan</title>
</head>
<body>
    <h2>Halo, {{ $reservation->user->name }} 👋</h2>

    <p>Reservasi Anda <strong>DIBATALKAN</strong> karena jadwal bentrok ⚠️</p>

    <ul>
        <li><strong>Ruangan:</strong> {{ $reservation->room->nama_ruangan }}</li>
        <li><strong>Tanggal:</strong> {{ $reservation->tanggal->format('d M Y') }} ({{ $reservation->day_of_week }})</li>
        <li><strong>Waktu:</strong> {{ substr($reservation->start_time,0,5) }} - {{ substr($reservation->start_time,0,5) }}</li>
        <li><strong>Reason:</strong> {{ $reservation->reason ?? '-' }}</li>
    </ul>

    <p>Silakan pilih jadwal lain agar tidak terjadi bentrok dengan reservasi yang sudah disetujui.</p>
</body>
</html>
