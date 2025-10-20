<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReservationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationLogController extends Controller
{
   
    public function index($reservationId)
    {
        $user = Auth::user();

        $query = ReservationLog::with('user')
            ->where('reservation_id', $reservationId)
            ->orderBy('created_at', 'desc');

        // Jika role karyawan, batasi log hanya milik reservasi dia sendiri
        if ($user->role === 'karyawan') {
            $query->whereHas('reservation', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $logs = $query->get();

        // Jika karyawan mencoba akses reservasi yang bukan miliknya
        if ($user->role === 'karyawan' && $logs->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Anda tidak memiliki akses ke log reservasi ini.',
                'data' => null
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data log reservasi berhasil diambil.',
            'data' => $logs
        ]);
    }

        public function show($id)
    {
        $user = Auth::user();

        $log = ReservationLog::with(['reservation', 'user'])->find($id);

        if (! $log) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Data log tidak ditemukan.',
                'data' => null
            ], 404);
        }

        // Jika karyawan, pastikan log ini milik reservasi dia sendiri
        if ($user->role === 'karyawan' && $log->reservation->user_id !== $user->id) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Anda tidak memiliki akses ke log ini.',
                'data' => null
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail log reservasi berhasil ditampilkan.',
            'data' => $log
        ]);
    }
}