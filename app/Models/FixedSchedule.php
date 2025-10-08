<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room;
use App\Models\User;

class FixedSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'day_of_week',      // ✅ Hanya pakai hari
        'start_time',
        'end_time',
        'description',
        'status',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time'   => 'string',
    ];

    /**
     * Relasi ke User (creator/admin)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Room
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Scope untuk cek jadwal overlapping di satu ruangan
     */
    public function scopeOverlapping($query, $roomId, $mulai, $selesai)
    {
        return $query->where('room_id', $roomId)
            ->whereIn('status', ['pending','approved'])
            ->where(function ($q) use ($mulai, $selesai) {
                $q->whereBetween('start_time', [$mulai, $selesai])
                  ->orWhereBetween('end_time', [$mulai, $selesai])
                  ->orWhere(function ($q2) use ($mulai, $selesai) {
                      $q2->where('start_time', '<=', $mulai)
                         ->where('end_time', '>=', $selesai);
                  });
            });
    }
}
