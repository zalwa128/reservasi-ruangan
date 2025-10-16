<?php

namespace App\Http\Resources\Karyawan;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ReservationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'room'          => [
                'id'   => $this->room->id,
                'nama_ruangan' => $this->room->nama_ruangan,
            ],
            'tanggal'       => Carbon::parse($this->tanggal)->format('Y-m-d'),
            'day_of_week'          => Carbon::parse($this->tanggal)->locale('id')->dayName,
            'start_time'   => $this->start_time,
            'end_time' => $this->end_time,
            'status'        => $this->status,
            'reason' => $this->reason,
            'created_at'    => $this->created_at->toDateTimeString(),
        ];
    }
}
