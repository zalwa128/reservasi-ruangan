<?php

namespace App\Services\Karyawan;

use App\Models\Reservation;
use App\Models\FixedSchedule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Services\Traits\ReservationCommonTrait;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationRejectedByFixedScheduleMail;

class ReservationService
{
    use ReservationCommonTrait;

    public function create(array $data)
    {
        $data['status'] = 'pending';

        $tanggal = Carbon::parse($data['tanggal'])->format('Y-m-d');
        $mulai   = Carbon::parse($tanggal . ' ' . $data['start_time']);
        $selesai = Carbon::parse($tanggal . ' ' . $data['end_time']);

        // Tidak boleh booking di waktu yang sudah lewat
        if ($mulai->lt(now())) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tidak bisa membuat reservasi di waktu yang sudah lewat.'
            ]);
        }

        // Maksimal H-30
        if ($mulai->gt(now()->addDays(30))) {
            throw ValidationException::withMessages([
                'tanggal' => 'Reservasi hanya bisa dilakukan maksimal H-30 sebelum tanggal meeting.'
            ]);
        }

        // Validasi waktu mulai < waktu selesai
        if ($mulai->greaterThanOrEqualTo($selesai)) {
            throw ValidationException::withMessages([
                'waktu' => 'Waktu mulai harus lebih awal dari waktu selesai.'
            ]);
        }

        // Validasi durasi maksimal 3 jam (180 menit)
        $durasi = $mulai->diffInMinutes($selesai, false);
        if ($durasi > 180) {
            throw ValidationException::withMessages([
                'durasi' => "Durasi meeting maksimal 3 jam. Anda input: {$durasi} menit."
            ]);
        }

        // Normalisasi data setelah validasi
        $data['tanggal']     = $tanggal;
        $data['start_time']  = $mulai->format('H:i');
        $data['end_time']    = $selesai->format('H:i');
        $data['day_of_week'] = Carbon::parse($tanggal)->locale('id')->dayName;

        // ✅ Cek bentrok dengan Fixed Schedule berdasarkan tanggal
        $conflictFixed = FixedSchedule::where('room_id', $data['room_id'])
            ->where('tanggal', $tanggal) // pake tanggal, bukan day_of_week
            ->where(function ($q) use ($mulai, $selesai) {
                $q->whereBetween('start_time', [$mulai->format('H:i'), $selesai->format('H:i')])
                  ->orWhereBetween('end_time', [$mulai->format('H:i'), $selesai->format('H:i')])
                  ->orWhere(function ($q2) use ($mulai, $selesai) {
                      $q2->where('start_time', '<=', $mulai->format('H:i'))
                         ->where('end_time', '>=', $selesai->format('H:i'));
                  });
            })
            ->exists();

        if ($conflictFixed) {
            $data['status'] = 'rejected';
            $data['reason'] = 'Ditolak otomatis karena bentrok dengan Fixed Schedule.';

            $reservation = Reservation::create($data);

            // Kirim email ke user
            if ($reservation->user && $reservation->user->email) {
                Mail::to($reservation->user->email)
                    ->send(new ReservationRejectedByFixedScheduleMail($reservation));
            }

            return $reservation;
        }

        // Cek bentrok dengan reservasi user sendiri
        $conflictReservations = Reservation::overlapping(
            $data['room_id'], $mulai, $selesai
        )
        ->whereDate('tanggal', $tanggal)
        ->whereIn('status', ['pending', 'approved'])
        ->where('user_id', $data['user_id'])
        ->get();

        if ($conflictReservations->count() > 0) {
            $data['status'] = 'rejected';
            $data['reason'] = 'Ditolak otomatis karena user sudah punya reservasi pada waktu ini.';
        }

        return Reservation::create($data);
    }

    public function getUserReservations(int $userId)
    {
        return Reservation::with('room')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserReservationById(int $userId, int $id)
    {
        $reservation = Reservation::with(['user', 'room'])
            ->where('user_id', $userId)
            ->find($id);

        if (! $reservation) {
            abort(403, 'Anda tidak punya akses untuk melihat reservasi ini.');
        }

        return $reservation;
    }

    public function cancel(int $reservationId, int $userId, string $reason)
    {
        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->firstOrFail();

        $reservation->update([
            'status' => 'rejected', // cancel → reject
            'reason' => $reason,
        ]);

        return $reservation;
    }
}
