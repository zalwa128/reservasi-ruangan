<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationLog extends Model
{
    protected $table = 'reservation_logs';

    protected $fillable = [
        'reservation_id',
        'user_id',
        'action',
        'details', // ✅ sinkron dengan DB
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
