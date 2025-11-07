<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\FixedSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Hitung total data utama
     */
    public function counts()
    {
        try {
            $reservasiCount = Reservation::count();
            $roomCount = Room::count();
            $scheduleCount = FixedSchedule::count();
            $userCount = User::count();

            return response()->json([
                'reservasi' => $reservasiCount,
                'ruangan' => $roomCount,
                'jadwal_tetap' => $scheduleCount,
                'user' => $userCount,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Gagal mengambil data dashboard',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Statistik bulanan reservasi
     */
    public function statistikBulanan()
    {
        try {
            $currentYear = Carbon::now()->year;

            // Ambil total reservasi per bulan
            $data = Reservation::select(
                DB::raw('MONTH(created_at) as bulan'),
                DB::raw('COUNT(*) as total')
            )
                ->whereYear('created_at', $currentYear)
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->get();

            // Format ke array bulan Januari-Desember
            $bulanList = [
                'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
            ];

            $chartData = [];
            for ($i = 1; $i <= 12; $i++) {
                $item = $data->firstWhere('bulan', $i);
                $chartData[] = [
                    'bulan' => $bulanList[$i - 1],
                    'total' => $item ? $item->total : 0
                ];
            }

            return response()->json($chartData);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Gagal memuat statistik bulanan',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
