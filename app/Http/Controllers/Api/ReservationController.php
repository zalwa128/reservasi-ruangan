<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

// Services
use App\Services\Admin\ReservationService as AdminReservationService;
use App\Services\Karyawan\ReservationService as KaryawanReservationService;

// Requests
use App\Http\Requests\Karyawan\ReservationStoreRequest;
use App\Http\Requests\Karyawan\ReservationCancelRequest;

// Resources
use App\Http\Resources\Admin\ReservationResource as AdminReservationResource;
use App\Http\Resources\Karyawan\ReservationResource as KaryawanReservationResource;

// Mail
use App\Mail\ReservationCanceledByUserMail;

// Models
use App\Models\ReservationLog;

// PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationController extends Controller
{
    protected $adminService;
    protected $karyawanService;

    public function __construct(
        AdminReservationService $adminService,
        KaryawanReservationService $karyawanService
    ) {
        $this->adminService    = $adminService;
        $this->karyawanService = $karyawanService;
    }

    /* ----------------------------- GET /reservations ----------------------------- */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) return $this->responseError('Token tidak valid.', 401);

        $filters = [
            'tanggal'    => $request->query('tanggal', null),
            'day_of_week'=> $request->query('day_of_week', null),
            'start_time' => $request->query('start_time', null),
            'end_time'   => $request->query('end_time', null),
            'status'     => $request->query('status', null),
            'per_page'   => $request->query('per_page', 10),
        ];

        $page = (int) max(1, $request->query('page', 1));
        $perPage = min(10, max(1, (int)$request->query('per_page', 10)));

        $timeRegex = '/^([01]\d|2[0-3]):[0-5]\d$/';
        if (!empty($filters['start_time']) && !preg_match($timeRegex, $filters['start_time'])) {
            return $this->responseError('Start time tidak valid. Format HH:MM.', 400);
        }
        if (!empty($filters['end_time']) && !preg_match($timeRegex, $filters['end_time'])) {
            return $this->responseError('End time tidak valid. Format HH:MM.', 400);
        }
        if (!empty($filters['start_time']) && empty($filters['end_time'])) {
            return $this->responseError('End time harus diisi jika start time diisi.', 400);
        }

        try {
            $query = $user->hasRole('admin')
                ? $this->adminService->getAll($filters)
                : $this->karyawanService->getAll($user->id, $filters);

            $reservations = $query->paginate($perPage, ['*'], 'page', $page)->appends($request->query());

            if ($reservations->isEmpty()) {
                return $this->responseSuccess('Data tidak ditemukan.', null);
            }

            $resource = $user->hasRole('admin')
                ? AdminReservationResource::collection($reservations)
                : KaryawanReservationResource::collection($reservations);

            $meta = [
                'current_page' => $reservations->currentPage(),
                'last_page'    => $reservations->lastPage(),
                'per_page'     => $reservations->perPage(),
                'total'        => $reservations->total(),
            ];

            return response()->json([
                'status'  => 'success',
                'message' => 'Data reservasi berhasil diambil.',
                'data'    => $resource,
                'meta'    => $meta,
            ], 200);
        } catch (\Throwable $th) {
            return $this->responseError('Terjadi kesalahan server: ' . $th->getMessage(), 500);
        }
    }

    /* ----------------------------- GET /reservations/{id} ----------------------------- */
    public function show($id)
    {
        $user = Auth::user();
        try {
            if ($user->hasRole('admin')) {
                $reservation = $this->adminService->getById($id);
                return $this->responseSuccess('Detail reservasi berhasil diambil.', new AdminReservationResource($reservation));
            }

            if ($user->hasRole('karyawan')) {
                $reservation = $this->karyawanService->getById($id);
                return $this->responseSuccess('Detail reservasi berhasil diambil.', new KaryawanReservationResource($reservation));
            }

            return $this->responseError('Anda tidak memiliki akses.', 403);
        } catch (\Throwable $th) {
            return $this->responseError('Data tidak ditemukan: ' . $th->getMessage(), 404);
        }
    }

    /* ----------------------------- POST /karyawan/reservations ----------------------------- */
    public function store(ReservationStoreRequest $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('karyawan')) {
            return $this->responseError('Hanya karyawan yang bisa membuat reservasi.', 403);
        }

        try {
            $reservation = $this->karyawanService->create([
                'user_id'      => $user->id,
                'room_id'      => $request->room_id,
                'tanggal'      => $request->tanggal,
                'day_of_week'  => strtolower(Carbon::parse($request->tanggal)->locale('id')->dayName),
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'reason'       => $request->reason ?? '-',
            ]);

            $this->logAction($user->id, $reservation->id, 'create', 'Reservasi baru dibuat.');

            return $this->responseSuccess('Reservasi berhasil dibuat.', new KaryawanReservationResource($reservation));
        } catch (\Throwable $th) {
            return $this->responseError('Gagal membuat reservasi: ' . $th->getMessage(), 500);
        }
    }

    /* ----------------------------- PUT /admin/reservations/{id}/approve ----------------------------- */
    public function approve($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) return $this->responseError('Hanya admin yang bisa approve.', 403);

        try {
            $reservation = $this->adminService->approve($id);
            $this->logAction($user->id, $id, 'approve', 'Reservasi disetujui oleh admin.');
            return $this->responseSuccess('Reservasi disetujui.', new AdminReservationResource($reservation));
        } catch (\Throwable $th) {
            return $this->responseError('Gagal approve reservasi: ' . $th->getMessage(), 500);
        }
    }

    /* ----------------------------- PUT /admin/reservations/{id}/reject ----------------------------- */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) return $this->responseError('Hanya admin yang bisa reject.', 403);

        $reason = $request->input('reason', 'Ditolak oleh admin');

        try {
            $reservation = $this->adminService->reject($id, $reason);
            $this->logAction($user->id, $id, 'reject', "Reservasi ditolak. Alasan: $reason");
            return $this->responseSuccess('Reservasi ditolak.', new AdminReservationResource($reservation));
        } catch (\Throwable $th) {
            return $this->responseError('Gagal reject reservasi: ' . $th->getMessage(), 500);
        }
    }

    /* ----------------------------- PUT /karyawan/reservations/{id}/cancel ----------------------------- */
    public function cancel(ReservationCancelRequest $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('karyawan')) return $this->responseError('Hanya karyawan yang bisa membatalkan reservasi.', 403);

        try {
            $reason = $request->validated()['reason'] ?? 'Dibatalkan oleh pengguna';
            $reservation = $this->karyawanService->cancel($id, $user->id, $reason);

            Mail::to("admin@reservasi.com")->send(new ReservationCanceledByUserMail($reservation));

            $this->logAction($user->id, $id, 'cancel', "Reservasi dibatalkan oleh karyawan. Alasan: $reason");

            return $this->responseSuccess('Reservasi berhasil dibatalkan.', new KaryawanReservationResource($reservation));
        } catch (\Throwable $th) {
            return $this->responseError('Gagal membatalkan reservasi: ' . $th->getMessage(), 500);
        }
    }

    /* ----------------------------- DELETE /admin/reservations/{id} ----------------------------- */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) return $this->responseError('Hanya admin yang bisa menghapus reservasi.', 403);

        try {
            $this->adminService->delete($id);
            $this->logAction($user->id, $id, 'delete', 'Reservasi dihapus oleh admin.');
            return $this->responseSuccess('Reservasi berhasil dihapus.');
        } catch (\Throwable $th) {
            return $this->responseError('Gagal menghapus reservasi: ' . $th->getMessage(), 500);
        }
    }

    /* ----------------------------- GET /reservations/{id}/logs ----------------------------- */
    public function logs($id)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin') && !$user->hasRole('karyawan')) {
            return $this->responseError('Anda tidak memiliki akses untuk melihat log.', 403);
        }

        try {
            $logs = ReservationLog::where('reservation_id', $id)
                ->orderBy('created_at', 'desc')
                ->get(['id', 'reservation_id', 'user_id', 'action', 'details', 'created_at']); // <-- pakai details

            if ($logs->isEmpty()) {
                return $this->responseSuccess('Belum ada riwayat untuk reservasi ini.', []);
            }

            return $this->responseSuccess('Riwayat reservasi berhasil diambil.', $logs);
        } catch (\Throwable $th) {
            return $this->responseError('Gagal mengambil log: ' . $th->getMessage(), 500);
        }
    }

    /* ----------------------------- EXPORT /reservations/export ----------------------------- */
    public function export(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return $this->responseError('Hanya admin yang bisa export data.', 403);
        }

        $filters = [
            'tanggal'    => $request->query('tanggal', null),
            'day_of_week'=> $request->query('day_of_week', null),
            'start_time' => $request->query('start_time', null),
            'end_time'   => $request->query('end_time', null),
            'status'     => $request->query('status', null),
        ];

        try {
            // Ambil semua data (tanpa pagination)
            $reservations = $this->adminService->getAll($filters)->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Daftar Reservasi');

            // Header
            $sheet->fromArray([
                ['ID', 'User', 'Room', 'Tanggal', 'Hari', 'Start Time', 'End Time', 'Alasan', 'Status']
            ], null, 'A1');

            // Data
            $row = 2;
            foreach ($reservations as $res) {
                $sheet->setCellValue("A{$row}", $res->id);
                $sheet->setCellValue("B{$row}", $res->user->name ?? '-');
                $sheet->setCellValue("C{$row}", $res->room->name ?? '-');
                $sheet->setCellValue("D{$row}", $res->tanggal->format('Y-m-d'));
                $sheet->setCellValue("E{$row}", $res->day_of_week);
                $sheet->setCellValue("F{$row}", $res->start_time);
                $sheet->setCellValue("G{$row}", $res->end_time);
                $sheet->setCellValue("H{$row}", $res->reason);
                $sheet->setCellValue("I{$row}", $res->status);
                $row++;
            }

            $writer = new Xlsx($spreadsheet);

            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $fileName = 'daftar_reservasi_' . date('Ymd_His') . '.xlsx';

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', "attachment;filename=\"{$fileName}\"");
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;

        } catch (\Throwable $th) {
            return $this->responseError('Gagal export reservasi: ' . $th->getMessage(), 500);
        }
    }

    /* ----------------------------- LOGIC: Simpan Log Riwayat ----------------------------- */
    private function logAction($userId, $reservationId, $action, $details)
    {
        try {
            ReservationLog::create([
                'user_id'        => $userId,
                'reservation_id' => $reservationId,
                'action'         => $action,
                'details'        => $details, // <-- pakai details
                'created_at'     => now(),
            ]);
        } catch (\Throwable $th) {
            \Log::error('Gagal menyimpan log reservasi: ' . $th->getMessage());
        }
    }

    /* ----------------------------- Helper ----------------------------- */
    private function responseSuccess(string $message, $data = null, int $statusCode = 200)
    {
        $response = ['status' => 'success', 'message' => $message];
        if (!is_null($data)) $response['data'] = $data;
        return response()->json($response, $statusCode);
    }

    private function responseError(string $message, int $statusCode = 400)
    {
        return response()->json(['status' => 'error', 'message' => $message], $statusCode);
    }
}
