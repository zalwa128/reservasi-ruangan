<?php

namespace App\Http\Resources\Karyawan;

use Illuminate\Http\Resources\Json\JsonResource;

class FixedScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'room_name' => $this->room->nama_ruangan ?? null,
            'day_of_week' => $this->day_of_week,    // langsung dari DB
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'description' => $this->description,
        ];
    }
}
