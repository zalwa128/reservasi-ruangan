<?php

namespace App\Http\Resources\Karyawan;

use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'room'          => [
                'id'   => $this->room->id,
                'name' => $this->room->name,
            ],
            'start_time'   => $this->start_time,
            'end_time' => $this->end_time,
            'status'        => $this->status,
            'reason'    => $this->reason,
            'created_at'    => $this->created_at->toDateTimeString(),
        ];
    }
}
