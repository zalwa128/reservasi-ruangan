<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReservationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'     => 'required|integer|exists:rooms,id',
            'tanggal'     => 'required|date|after_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'reason'      => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tanggal = $this->input('tanggal');
            $start   = Carbon::parse($tanggal . ' ' . $this->input('start_time'));
            $end     = Carbon::parse($tanggal . ' ' . $this->input('end_time'));

            // 🔹 1. Limit waktu booking H-30
            if ($start->gt(now()->addDays(30))) {
                $validator->errors()->add('tanggal', 'Reservasi hanya bisa dilakukan maksimal H-30 sebelum tanggal meeting.');
            }

            // 🔹 2. Durasi maksimum 3 jam
            $duration = $start->diffInMinutes($end, false);
            if ($duration > 180) {
                $validator->errors()->add('end_time', 'Durasi meeting maksimal 3 jam (180 menit). Anda input: ' . $duration . ' menit.');
            }
        });
    }
}
