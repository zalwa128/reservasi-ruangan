<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Validation\ValidationException;

class RoomService
{
    public function getAll()
    {
        return Room::with(['reservations', 'fixedSchedules'])->get();
    }

    public function find($id)
    {
        return Room::with(['reservations.user', 'fixedSchedules'])->findOrFail($id);
    }

    public function create(array $data)
    {
        // $data harus berisi: nama_ruangan, capacity, deskripsi, status
        return Room::create($data);
    }

    public function update($id, array $data)
    {
        $room = Room::findOrFail($id);

        // update kolom: nama_ruangan, capacity, deskripsi, status
        $room->update($data);

        return $room;
    }

    public function delete($id)
    {
        $room = Room::findOrFail($id);

        // cek reservasi aktif 
        $activeReservation = $room->reservations()
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        // cek fixed schedule aktif
        $hasFixedSchedule = $room->fixedSchedules()->exists();

        if ($activeReservation || $hasFixedSchedule) {
            throw ValidationException::withMessages([
                'room' => 'Ruangan tidak bisa dihapus karena masih memiliki reservasi aktif atau jadwal tetap.'
            ]);
        }

        return $room->delete();
    }
}
