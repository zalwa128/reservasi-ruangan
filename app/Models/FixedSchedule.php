<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'day_of_week',
        'start_time',
        'end_time',
        'description',
    ];

    /**
     * Relasi ke Room (satu jadwal tetap dimiliki oleh satu ruangan).
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Relasi ke Reservation (satu jadwal bisa punya banyak reservasi terkait).
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
