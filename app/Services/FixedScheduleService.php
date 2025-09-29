<?php

namespace App\Services;

use App\Models\FixedSchedule;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationRejectedByFixedScheduleMail;

class FixedScheduleService
{
    public function getAll()
    {
        return FixedSchedule::with(['room'])->latest()->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // FixedSchedule dibuat tanpa user_id
            $fixedSchedule = FixedSchedule::create($data);

            // Cari reservation bentrok
            $this->handleConflicts($fixedSchedule);

            return $fixedSchedule;
        });
    }

    public function update(FixedSchedule $fixedSchedule, array $data)
    {
        return DB::transaction(function () use ($fixedSchedule, $data) {
            $fixedSchedule->update($data);

            // Cari reservation bentrok
            $this->handleConflicts($fixedSchedule);

            return $fixedSchedule;
        });
    }

    public function delete(FixedSchedule $fixedSchedule)
    {
        return $fixedSchedule->delete();
    }

    private function handleConflicts(FixedSchedule $fixedSchedule)
    {
        $conflictReservations = Reservation::where('room_id', $fixedSchedule->room_id)
            ->where('tanggal', $fixedSchedule->tanggal)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($fixedSchedule) {
                $q->whereBetween('start_time', [$fixedSchedule->start_time, $fixedSchedule->end_time])
                  ->orWhereBetween('end_time', [$fixedSchedule->start_time, $fixedSchedule->end_time])
                  ->orWhere(function ($q2) use ($fixedSchedule) {
                      $q2->where('start_time', '<=', $fixedSchedule->start_time)
                         ->where('end_time', '>=', $fixedSchedule->end_time);
                  });
            })
            ->with('user')
            ->get();

        foreach ($conflictReservations as $reservation) {
            $reservation->update([
                'status' => 'rejected',
                'reason' => 'Ditolak otomatis karena bentrok dengan Fixed Schedule.'
            ]);

            if ($reservation->user && $reservation->user->email) {
                Mail::to($reservation->user->email)
                    ->send(new ReservationRejectedByFixedScheduleMail($reservation));
            }
        }
    }
}
