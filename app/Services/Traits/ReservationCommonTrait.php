<?php

namespace App\Services\Traits;

use App\Models\Reservation;

trait ReservationCommonTrait
{
    
    public function getAll()
    {
        return Reservation::with(['user', 'room'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getById(int $id)
    {
        return Reservation::with(['user', 'room'])->findOrFail($id);
    }
}
