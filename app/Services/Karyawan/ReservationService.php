<?php

namespace App\Services\Karyawan;

use App\Models\Reservation;
use App\Models\ReservationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReservationService
{
    public function getAll(int $userId, array $filters = [])
    {
        $query = Reservation::with('room')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['tanggal'])) $query->whereDate('tanggal', $filters['tanggal']);
        if (!empty($filters['day_of_week'])) $query->where('day_of_week', strtolower($filters['day_of_week']));
        if (!empty($filters['start_time'])) $query->whereTime('start_time', '>=', $filters['start_time']);
        if (!empty($filters['end_time'])) $query->whereTime('end_time', '<=', $filters['end_time']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);

        return $query;
    }

    public function getById(int $id)
    {
        return Reservation::with('room')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $date = Carbon::parse($data['tanggal'])->format('Y-m-d');
            $start = Carbon::parse($date . ' ' . $data['start_time']);
            $end = Carbon::parse($date . ' ' . $data['end_time']);

            // 🛑 Tidak boleh booking di waktu yang sudah lewat
            if ($start->lt(now())) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Tidak bisa membuat reservasi di waktu yang sudah lewat.'
                ]);
            }

            // 🛑 Maksimal H-30 dari tanggal meeting
            if ($start->gt(now()->addDays(30))) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Reservasi hanya bisa dilakukan maksimal H-30 sebelum tanggal meeting.'
                ]);
            }

            // 🛑 Validasi start < end
            if ($start->greaterThanOrEqualTo($end)) {
                throw ValidationException::withMessages([
                    'waktu' => 'Waktu mulai harus lebih awal dari waktu selesai.'
                ]);
            }

            // 🛑 Validasi durasi maksimal 3 jam (180 menit)
            $duration = $start->diffInMinutes($end, false);
            if ($duration > 180) {
                throw ValidationException::withMessages([
                    'durasi' => "Durasi meeting maksimal 3 jam. Anda input: {$duration} menit."
                ]);
            }

            // Normalisasi data
            $data['tanggal'] = $date;
            $data['start_time'] = $start->format('H:i');
            $data['end_time'] = $end->format('H:i');
            $data['day_of_week'] = strtolower($start->locale('id')->dayName);

            // ✅ Simpan reservasi
            $reservation = Reservation::create([
                'user_id'     => $data['user_id'],
                'room_id'     => $data['room_id'],
                'tanggal'     => $data['tanggal'],
                'day_of_week' => $data['day_of_week'],
                'start_time'  => $data['start_time'],
                'end_time'    => $data['end_time'],
                'reason'      => $data['reason'] ?? '-',
                'status'      => 'pending',
            ]);

            // 🧾 Tambahkan log
            ReservationLog::create([
                'reservation_id' => $reservation->id,
                'user_id'        => $reservation->user_id,
                'action'         => 'created',
                'details'        => 'Reservasi baru dibuat oleh user ID ' . $reservation->user_id,
                'created_at'     => now(),
            ]);

            return $reservation;
        });
    }

    public function cancel(int $id, int $userId, string $reason)
    {
        return DB::transaction(function () use ($id, $userId, $reason) {
            $reservation = Reservation::where('id', $id)
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->firstOrFail();

            $reservation->update([
                'status'     => 'canceled',
                'reason'     => $reason,
                'deleted_at' => now(),
            ]);

            ReservationLog::create([
                'reservation_id' => $reservation->id,
                'user_id'        => $userId,
                'action'         => 'canceled',
                'details'        => 'Reservasi dibatalkan dengan alasan: ' . $reason,
                'created_at'     => now(),
            ]);

            return $reservation;
        });
    }

    public function approve(int $id, int $adminId)
    {
        return DB::transaction(function () use ($id, $adminId) {
            $reservation = Reservation::where('id', $id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            $reservation->update(['status' => 'approved']);

            ReservationLog::create([
                'reservation_id' => $reservation->id,
                'user_id'        => $adminId,
                'action'         => 'approved',
                'details'        => 'Reservasi disetujui oleh admin ID ' . $adminId,
                'created_at'     => now(),
            ]);

            return $reservation;
        });
    }

    public function reject(int $id, int $adminId, string $reason)
    {
        return DB::transaction(function () use ($id, $adminId, $reason) {
            $reservation = Reservation::where('id', $id)
                ->whereNull('deleted_at')
                ->firstOrFail();

            $reservation->update(['status' => 'rejected', 'reason' => $reason]);

            ReservationLog::create([
                'reservation_id' => $reservation->id,
                'user_id'        => $adminId,
                'action'         => 'rejected',
                'details'        => 'Reservasi ditolak oleh admin ID ' . $adminId . ' dengan alasan: ' . $reason,
                'created_at'     => now(),
            ]);

            return $reservation;
        });
    }
}
