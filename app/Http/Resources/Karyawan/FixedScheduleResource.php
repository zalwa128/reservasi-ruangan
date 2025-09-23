<?php

namespace App\Http\Resources\Karyawan;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FixedScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'room_id'          => new RoomResource($this->whenLoaded('room_id')),
            'day_of_week'          => $this->hari,
            'start_time'   => Carbon::parse($this->start_time)->format('H:i'),
            'end_time' => Carbon::parse($this->end_time)->format('H:i'),
            'description'    => $this->keterangan,
        ];
    }
}
