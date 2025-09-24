<?php

namespace App\Services\Admin;

use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationApprovedMail;
use App\Mail\ReservationRejectedMail;
use App\Mail\ReservationCanceledByOverlapMail;

class ReservationService
{
    public function getAll()
    {
        return Reservation::with(['user', 'room'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('start_time')
            ->get();
    }

    public function approve($id)
    {
        return $this->updateStatus($id, 'approved');
    }

    public function reject($id, $reason = null)
    {
        return $this->updateStatus($id, 'rejected', $reason);
    }

    public function delete($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return true;
    }

    public function updateStatus($id, string $status, $reason = null)
    {
        $reservation = Reservation::with(['user', 'room'])->findOrFail($id);

        $reservation->update([
            'status' => $status,
            'reason' => $reason
        ]);

        //kalo disetujui
        if ($status === 'approved') {
            // kirim email approved
            if ($reservation->user) {
                Mail::to($reservation->user->email)
                    ->send(new ReservationApprovedMail($reservation));
            }

            // Cancel semua pending yang bentrok di tanggal yg sama
            $overlaps = Reservation::where('room_id', $reservation->room_id)
                ->where('tanggal', $reservation->tanggal)   // filter tanggal
                ->where('id', '!=', $reservation->id)
                ->where('status', 'pending')
                ->where(function ($q) use ($reservation) {
                    $q->whereBetween('start_time', [$reservation->start_time, $reservation->end_time])
                      ->orWhereBetween('end_time', [$reservation->start_time, $reservation->end_time])
                      ->orWhere(function ($q2) use ($reservation) {
                          $q2->where('start_time', '<=', $reservation->start_time)
                             ->where('end_time', '>=', $reservation->end_time);
                      });
                })
                ->get();

            foreach ($overlaps as $overlap) {
                $overlap->update(['status' => 'canceled']);
                if ($overlap->user) {
                    Mail::to($overlap->user->email)
                        ->send(new ReservationCanceledByOverlapMail($overlap, $reservation));
                }
            }
        }

        //kalo ditolak
        if ($status === 'rejected' && $reservation->user) {
            Mail::to($reservation->user->email)
                ->send(new ReservationRejectedMail($reservation, $reason));
        }

        return $reservation;
    }
}
