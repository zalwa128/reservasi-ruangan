<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Validation\ValidationException;

class RoomService
{
    /**
     * Ambil semua ruangan dengan filter
     */
    public function getAll(array $filters = [])
    {
        $query = Room::with(['reservations.user', 'fixedSchedules']);

        if (!empty($filters['nama_ruangan'])) {
            $query->where('nama_ruangan', 'like', '%' . $filters['nama_ruangan'] . '%');
        }

        if (!empty($filters['capacity'])) {
            $query->where('capacity', $filters['capacity']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->orderBy('id', 'asc');

        return $query; // return query, jangan get(), biar bisa paginate
    }

    /**
     * Cari ruangan berdasarkan ID
     */
    public function find($id)
    {
        return Room::with(['reservations.user', 'fixedSchedules'])->find($id); // find() bukan findOrFail
    }

    /**
     * Buat ruangan baru
     */
    public function create(array $data)
    {
        return Room::create($data);
    }

    /**
     * Update ruangan
     */
    public function update($id, array $data)
    {
        $room = Room::find($id);
        if (!$room) return null;

        $room->update($data);
        return $room;
    }

    /**
     * Hapus ruangan
     */
    public function delete($id)
    {
        $room = Room::find($id);
        if (!$room) return null;

        $activeReservation = $room->reservations()
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        $hasFixedSchedule = $room->fixedSchedules()->exists();

        if ($activeReservation || $hasFixedSchedule) {
            throw ValidationException::withMessages([
                'room' => 'Ruangan tidak bisa dihapus karena masih memiliki reservasi aktif atau jadwal tetap.'
            ]);
        }

        return $room->delete();
    }
}
