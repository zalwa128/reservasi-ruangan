<?php

namespace App\Services;

use App\Models\FixedSchedule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class FixedScheduleService
{
    public function getAll()
    {
        return FixedSchedule::with(['room','user'])->get();
    }

    public function find($id)
    {
        return FixedSchedule::with(['room','user'])->findOrFail($id);
    }

    public function create(array $data)
    {
        $data['user_id'] = Auth::id();

        // Cek konflik jadwal
        $conflict = FixedSchedule::where('room_id', $data['room_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                  ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                  ->orWhere(function ($q2) use ($data) {
                      $q2->where('start_time', '<=', $data['start_time'])
                         ->where('end_time', '>=', $data['end_time']);
                  });
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'schedule' => 'Jadwal tetap bentrok dengan jadwal lain pada ruangan ini.'
            ]);
        }

        return FixedSchedule::create($data);
    }

    public function update($id, array $data)
    {
        $schedule = FixedSchedule::findOrFail($id);

        // Cek konflik jadwal saat update
        $conflict = FixedSchedule::where('room_id', $data['room_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('id', '!=', $id)
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                  ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                  ->orWhere(function ($q2) use ($data) {
                      $q2->where('start_time', '<=', $data['start_time'])
                         ->where('end_time', '>=', $data['end_time']);
                  });
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'schedule' => 'Perubahan bentrok dengan jadwal lain pada ruangan ini.'
            ]);
        }

        $schedule->update($data);
        return $schedule;
    }

    public function delete($id)
    {
        $schedule = FixedSchedule::findOrFail($id);
        $schedule->delete();
    }
}
