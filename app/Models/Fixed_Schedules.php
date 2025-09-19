<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room;

class FixedSchedules extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'day_of_week', 
        'start_time',
        'end_time',
        'description',
    ];

    // Relasi ke room
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
