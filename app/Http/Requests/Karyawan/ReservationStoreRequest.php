<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Foundation\Http\FormRequest;

class ReservationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'       => 'required|exists:rooms,id',
            'tanggal'       => 'required|date|after_or_equal:today',
            'day_of_week' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time'   => 'required|time_format:H:i',
            'end_time' => 'required|time_format:H:i|after:waktu_mulai',
            'reason'    => 'nullable|string|max:255',
        ];
    }
}
