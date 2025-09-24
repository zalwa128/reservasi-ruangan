<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Disetujui</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .email-header {
            background: #4f46e5; /* warna ungu elegan */
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .email-body {
            padding: 20px 25px;
        }
        .email-body h2 {
            margin-top: 0;
            color: #333;
        }
        .email-body ul {
            list-style: none;
            padding-left: 0;
        }
        .email-body li {
            margin-bottom: 8px;
        }
        .highlight {
            color: #4f46e5;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 13px;
            color: #777;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Reservasi Disetujui 🎉</h1>
        </div>
        <div class="email-body">
            <h2>Halo, {{ $reservation->user->name }} 👋</h2>

            <p>Reservasi Anda telah <span class="highlight">DISETUJUI</span> ✅</p>

            <ul>
                <li><strong>Ruangan:</strong> {{ $reservation->room->nama_ruangan }}</li>
                <li><strong>Tanggal:</strong> {{ $reservation->tanggal->format('d M Y') }} ({{ $reservation->day_of_week }})</li>
                <li><strong>Waktu:</strong> {{ substr($reservation->start_time,0,5) }} - {{ substr($reservation->end_time,0,5) }}</li>
                <li><strong>Alasan/Keperluan:</strong> {{ $reservation->reason ?? '-' }}</li>
            </ul>

            <p>Silakan gunakan ruangan sesuai jadwal. Terima kasih 🙏</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Sistem Reservasi Ruangan — Email otomatis, mohon tidak dibalas.
        </div>
    </div>
</body>
</html>
