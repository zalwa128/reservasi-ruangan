<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Foundation\Http\FormRequest;

class ReservationCancelRequest extends FormRequest
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
            'start_time'   => 'required|time_format:H:i',
            'end_time' => 'required|time_format:H:i|after:start_time',
            'reason'        => 'nullable|string|max:255',
        ];
    }
}
