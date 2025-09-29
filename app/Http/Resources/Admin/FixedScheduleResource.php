<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class FixedScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'room' => [
                'id' => $this->room->id ?? null,
                'name' => $this->room->nama_ruangan ?? null,
            ],
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'description' => $this->description,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

            // menampilkan daftar user dari reservation yang bentrok
            'conflict_reservations' => ($this->reservations ?? collect())->map(function($reservation) {
                return [
                    'id' => $reservation->id,
                    'user_name' => $reservation->user->name ?? 'Unknown',
                    'user_email' => $reservation->user->email ?? null,
                    'status' => $reservation->status,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'reason' => $reservation->reason,
                ];
            }),
        ];
    }
}
