<?php

namespace App\Services\Admin;

use App\Models\Reservation;

class ReservationService
{
    public function getAll(array $filters = [])
    {
        $query = Reservation::with(['user', 'room'])->latest();

        // Filter tanggal
        if (!empty($filters['tanggal'])) {
            $query->whereDate('tanggal', $filters['tanggal']);
        }

        // Mapping hari Indonesia -> Inggris
        $dayMap = [
            'senin'  => 'monday',
            'selasa' => 'tuesday',
            'rabu'   => 'wednesday',
            'kamis'  => 'thursday',
            'jumat'  => 'friday',
            'sabtu'  => 'saturday',
            'minggu' => 'sunday',
        ];

        if (!empty($filters['day_of_week'])) {
            $dayInput = strtolower($filters['day_of_week']);
            if(isset($dayMap[$dayInput])){
                $query->where('day_of_week', $dayMap[$dayInput]);
            }
        }

        // Filter start_time & end_time
        if (!empty($filters['start_time'])) {
            $query->where('start_time', '>=', date('H:i:s', strtotime($filters['start_time'])));
        }
        if (!empty($filters['end_time'])) {
            $query->where('end_time', '<=', date('H:i:s', strtotime($filters['end_time'])));
        }

        return $query; // jangan paginate di sini
    }
}
