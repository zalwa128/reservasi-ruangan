<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Room;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'tanggal',         // simpan tanggal reservasi
        'day_of_week',
        'start_time',   // simpan sebagai string (format H:i)
        'end_time',     // simpan sebagai string (format H:i)
        'status',
        'reason',       // alasan reservasi
    ];

    protected $casts = [
        'tanggal'       => 'date:Y-m-d',
        'start_time' => 'string',
        'end_time'   => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Scope untuk mencari overlapping reservation
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
