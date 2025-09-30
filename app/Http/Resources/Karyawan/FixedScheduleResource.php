<?php

namespace App\Http\Resources\Karyawan;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class FixedScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'room_name' => $this->room->nama_ruangan ?? null,
            'tanggal' => Carbon::parse($this->tanggal)->format('Y-m-d'),
            'day_of_week' => Carbon::parse($this->tanggal)->locale('id')->dayName,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'description' => $this->description,
        ];
    }
}
