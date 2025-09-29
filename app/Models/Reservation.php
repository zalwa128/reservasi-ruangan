<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Room;
use Carbon\Carbon;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'tanggal',
        'day_of_week',           // ✅ hari disimpan text: "Senin".."Minggu"
        'start_time',
        'end_time',
        'reason',
        'status',
    ];

    protected $casts = [
        'tanggal'       => 'date:Y-m-d', // ✅ cast jadi tanggal saja
        'start_time'   => 'string',     // ✅ simpan sebagai string (format H:i)
        'end_time' => 'string',     // ✅ simpan sebagai string (format H:i)
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function setTanggalAttribute($value)
    {
        $this->attributes['tanggal'] = $value;

        $carbon = Carbon::parse($value)->locale('id');
        $this->attributes['day_of_week'] = ucfirst($carbon->dayName); 
    }

    /**
     * Scope untuk mencari overlapping reservation
     */
    public function scopeOverlapping($query, $roomId, $mulai, $selesai)
{
    $mulai   = Carbon::parse($mulai)->format('H:i');
    $selesai = Carbon::parse($selesai)->format('H:i');

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
