<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room;
use App\Models\User;

class Reservations extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'tanggal',
        'start_time',
        'end_time',
        'status',
        'reason',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke room
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Cek reservasi aktif
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'approved']);
    }
}
