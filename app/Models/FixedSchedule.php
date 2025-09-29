<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room;
use App\Models\User;
use App\Models\Reservation;

class FixedSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'day_of_week',    // hari disimpan text: "monday".."sunday"
        'start_time',     // format H:i:s
        'end_time',       // format H:i:s
        'description',
        'status',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time'   => 'string',
    ];

    // Relasi ke Room
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Relasi ke User (creator/admin)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi reservation bentrok
    public function overlappingReservations()
    {
        return Reservation::where('room_id', $this->room_id)
            ->where('day_of_week', $this->day_of_week)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) {
                $q->whereBetween('start_time', [$this->start_time, $this->end_time])
                  ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                  ->orWhere(function ($q2) {
                      $q2->where('start_time', '<=', $this->start_time)
                         ->where('end_time', '>=', $this->end_time);
                  });
            })
            ->get();
    }
}
