<?php

namespace App\Services\Admin;

use App\Models\Reservation;

class ReservationService
{
    public function getAll(array $filters = [])
    {
        $query = Reservation::with(['user', 'room'])->whereNull('deleted_at');

        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['tanggal'])) $query->whereDate('tanggal', $filters['tanggal']);
        if (!empty($filters['day_of_week'])) $query->where('day_of_week', strtolower($filters['day_of_week']));
        if (!empty($filters['start_time'])) $query->whereTime('start_time', '>=', $filters['start_time']);
        if (!empty($filters['end_time'])) $query->whereTime('end_time', '<=', $filters['end_time']);

        return $query->orderBy('id', 'asc');
    }

    public function getById($id)
    {
        return Reservation::with(['user', 'room'])->whereNull('deleted_at')->findOrFail($id);
    }

    public function approve($id)
    {
        $reservation = $this->getById($id);
        $reservation->update(['status' => 'approved', 'admin_notified_at' => now()]);
        return $reservation;
    }

    public function reject($id, $reason)
    {
        $reservation = $this->getById($id);
        $reservation->update(['status' => 'rejected', 'reason' => $reason, 'admin_notified_at' => now()]);
        return $reservation;
    }

    public function delete($id)
    {
        $reservation = $this->getById($id);
        $reservation->delete();
    }
}
