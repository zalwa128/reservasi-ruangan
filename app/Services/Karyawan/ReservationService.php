<?php

namespace App\Services\Karyawan;

use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

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
        return Reservation::with('room')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return Reservation::create([
                'user_id'     => $data['user_id'],
                'room_id'     => $data['room_id'],
                'tanggal'     => $data['tanggal'],
                'day_of_week' => strtolower($data['day_of_week']),
                'start_time'  => $data['start_time'],
                'end_time'    => $data['end_time'],
                'reason'      => $data['reason'] ?? '-',
                'status'      => 'pending',
            ]);
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

            return $reservation;
        });
    }
}
