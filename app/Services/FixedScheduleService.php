<?php

namespace App\Services;

use App\Models\FixedSchedule;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\ReservationRejectedByFixedScheduleMail;

class FixedScheduleService
{
    public function getFiltered(array $filters)
    {
        $query = FixedSchedule::with(['room', 'user']);

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }
        if (!empty($filters['day_of_week'])) {
            $query->where('day_of_week', strtolower($filters['day_of_week']));
        }
        if (!empty($filters['start_time'])) {
            $query->where('start_time', '>=', $filters['start_time']);
        }
        if (!empty($filters['end_time'])) {
            $query->where('end_time', '<=', $filters['end_time']);
        }

        // 🔹 Urut berdasarkan ID ASC
        $query->orderBy('id', 'asc');

        // 🔹 Pagination
        $perPage = $filters['per_page'] ?? 10;
        $data = $query->paginate($perPage);

        // 🔹 Format output dengan meta pagination
        return [
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ],
        ];
    }

    public function getById($id)
    {
        $schedule = FixedSchedule::with(['room', 'user'])->find($id);
        if (!$schedule) {
            throw new \Exception("FixedSchedule ID {$id} tidak ditemukan.");
        }
        return $schedule;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['user_id'] = Auth::guard('api')->id();
            if (!empty($data['day_of_week'])) {
                $data['day_of_week'] = strtolower($data['day_of_week']);
            }

            $durasi = $this->calculateDuration($data['start_time'], $data['end_time']);
            if ($durasi > 180) {
                throw new \Exception("Durasi Fixed Schedule maksimal 3 jam. Anda input {$durasi} menit.");
            }

            $fixedSchedule = FixedSchedule::create($data);
            $this->autoRejectConflicts($fixedSchedule);
            return $fixedSchedule;
        });
    }

    public function updateById($id, array $data)
    {
        $schedule = $this->getById($id);
        return $this->update($schedule, $data);
    }

    public function update(FixedSchedule $fixedSchedule, array $data)
    {
        return DB::transaction(function () use ($fixedSchedule, $data) {
            $data['user_id'] = Auth::guard('api')->id();
            if (!empty($data['day_of_week'])) {
                $data['day_of_week'] = strtolower($data['day_of_week']);
            }

            $durasi = $this->calculateDuration($data['start_time'], $data['end_time']);
            if ($durasi > 180) {
                throw new \Exception("Durasi Fixed Schedule maksimal 3 jam. Anda input {$durasi} menit.");
            }

            $fixedSchedule->update($data);
            $this->autoRejectConflicts($fixedSchedule);
            return $fixedSchedule;
        });
    }

    public function deleteById($id)
    {
        $schedule = $this->getById($id);
        return $this->delete($schedule);
    }

    public function delete(FixedSchedule $fixedSchedule)
    {
        return $fixedSchedule->delete();
    }

    private function calculateDuration($start, $end)
    {
        [$h1, $m1] = explode(':', $start);
        [$h2, $m2] = explode(':', $end);
        $mulai = $h1 * 60 + $m1;
        $selesai = $h2 * 60 + $m2;
        return $selesai - $mulai;
    }

    private function autoRejectConflicts(FixedSchedule $fixedSchedule)
    {
        $conflictReservations = Reservation::where('room_id', $fixedSchedule->room_id)
            ->where('day_of_week', $fixedSchedule->day_of_week)
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('deleted_at')
            ->where(function ($q) use ($fixedSchedule) {
                $q->whereBetween('start_time', [$fixedSchedule->start_time, $fixedSchedule->end_time])
                  ->orWhereBetween('end_time', [$fixedSchedule->start_time, $fixedSchedule->end_time])
                  ->orWhere(function ($q2) use ($fixedSchedule) {
                      $q2->where('start_time', '<=', $fixedSchedule->start_time)
                         ->where('end_time', '>=', $fixedSchedule->end_time);
                  });
            })
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
