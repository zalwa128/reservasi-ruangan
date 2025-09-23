<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_ruangan',
        'capacity',
        'deskripsi',
        'status',
    ];

    // Relasi ke Reservations
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Relasi ke FixedSchedules
    public function fixedSchedules()
    {
        return $this->hasMany(FixedSchedule::class);
    }

    // Semua user yang pernah booking ruangan ini
    public function users()
    {
        return $this->belongsToMany(User::class, 'reservations')
                    ->withPivot(['tanggal', 'start_time', 'end_time', 'status'])
                    ->withTimestamps();
    }

    /**
     * Accessor status_aktual (real-time).
     * Akan bernilai "aktif" jika ada reservasi approved
     * di tanggal & jam sekarang.
     */
    public function getStatusAktualAttribute()
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $timeNow = $now->format('H:i:s');

        $adaReservasi = $this->reservations()
            ->where('tanggal', $today)
            ->where('status', 'approved')
            ->where('start_time', '<=', $timeNow)
            ->where('end_time', '>=', $timeNow)
            ->exists();

        return $adaReservasi ? 'aktif' : 'non-aktif';
    }
}
