<?php

namespace App\Services\Karyawan;

use App\Models\Reservation;
use App\Models\FixedSchedule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReservationService
{
    public function create(array $data)
    {
        $data['status'] = 'pending';

        // Ambil tanggal (fallback dari start_time kalau tidak ada 'tanggal')
        if (!isset($data['tanggal']) || empty($data['tanggal'])) {
            $date = Carbon::parse($data['start_time'])->format('Y-m-d');
        } else {
            $date = Carbon::parse($data['tanggal'])->format('Y-m-d');
        }

        // Gabungkan tanggal + jam
        $startTime = Carbon::parse($date . ' ' . $data['start_time']);
        $endTime   = Carbon::parse($date . ' ' . $data['end_time']);

        // Validasi waktu
        if ($startTime >= $endTime) {
            throw ValidationException::withMessages([
                'time' => 'Waktu mulai harus lebih awal dari waktu selesai.'
            ]);
        }

        // Simpan tanggal & waktu sebagai string H:i (sesuai varchar di DB)
        $data['tanggal']    = $date;
        $data['start_time'] = $startTime->format('H:i'); // hanya jam:menit
        $data['end_time']   = $endTime->format('H:i');

        // Isi day_of_week otomatis (0=Min, 6=Sabtu)
        $data['day_of_week'] = Carbon::parse($date)->dayOfWeek;

        // 🔍 Validasi bentrok dengan FixedSchedule
        $conflictFixed = FixedSchedule::where('room_id', $data['room_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime->format('H:i'), $endTime->format('H:i')])
                  ->orWhereBetween('end_time', [$startTime->format('H:i'), $endTime->format('H:i')])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime->format('H:i'))
                         ->where('end_time', '>=', $endTime->format('H:i'));
                  });
            })
            ->exists();

        if ($conflictFixed) {
            throw ValidationException::withMessages([
                'reservation' => 'Bentrok dengan jadwal tetap.'
            ]);
        }

        // 🔍 Validasi bentrok dengan reservasi lain
        $conflictReservation = Reservation::overlapping(
            $data['room_id'], $startTime, $endTime
        )->exists();

        if ($conflictReservation) {
            throw ValidationException::withMessages([
                'reservation' => 'Bentrok dengan reservasi lain.'
            ]);
        }

        return Reservation::create($data);
    }

    public function getUserReservations($userId)
    {
        return Reservation::with('room')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
