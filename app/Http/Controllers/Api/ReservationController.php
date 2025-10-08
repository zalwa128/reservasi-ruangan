<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

// Services
use App\Services\Admin\ReservationService as AdminReservationService;
use App\Services\Karyawan\ReservationService as KaryawanReservationService;

// Requests
use App\Http\Requests\Admin\ReservationUpdateRequest;
use App\Http\Requests\Karyawan\ReservationStoreRequest;
use App\Http\Requests\Karyawan\ReservationCancelRequest;

// Resources
use App\Http\Resources\Admin\ReservationResource as AdminReservationResource;
use App\Http\Resources\Karyawan\ReservationResource as KaryawanReservationResource;

// Mail
use App\Mail\ReservationCanceledByUserMail;

class ReservationController extends Controller
{
    protected $adminService;
    protected $karyawanService;

    public function __construct(
        AdminReservationService $adminService,
        KaryawanReservationService $karyawanService
    ) {
        $this->adminService = $adminService;
        $this->karyawanService = $karyawanService;
    }

public function index(Request $request)
{
    $user = Auth::user();

    $filters = [
        'tanggal'     => $request->query('tanggal'),
        'day_of_week' => $request->query('day_of_week'),
        'start_time'  => $request->query('start_time'),
        'end_time'    => $request->query('end_time'),
    ];

    $perPage = $request->query('per_page', 10);

    if ($user->hasRole('admin')) {
        $query = $this->adminService->getAll($filters);
        $reservations = $query->paginate($perPage);

        return AdminReservationResource::collection($reservations)
            ->additional([
                'meta' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page'    => $reservations->lastPage(),
                    'per_page'     => $reservations->perPage(),
                    'total'        => $reservations->total(),
                ]
            ]);
    }

    if ($user->hasRole('karyawan')) {
        $query = $this->karyawanService->getUserReservations($user->id, $filters);
        $reservations = $query->paginate($perPage);

        return KaryawanReservationResource::collection($reservations)
            ->additional([
                'meta' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page'    => $reservations->lastPage(),
                    'per_page'     => $reservations->perPage(),
                    'total'        => $reservations->total(),
                ]
            ]);
    }

    abort(403, 'Anda tidak punya akses.');
}

    // Detail reservasi
    public function show($id)
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $reservation = $this->adminService->getById($id);
            return new AdminReservationResource($reservation);
        }

        if ($user->hasRole('karyawan')) {
            $reservation = $this->karyawanService->getUserReservationById($user->id, $id);
            return new KaryawanReservationResource($reservation);
        }

        abort(403, 'Anda tidak punya akses.');
    }

    // Buat reservasi (karyawan)
    public function store(ReservationStoreRequest $request)
    {
        $user = Auth::user();

        if (! $user->hasRole('karyawan')) {
            abort(403, 'Hanya karyawan yang bisa membuat reservasi.');
        }

        $reservation = $this->karyawanService->create([
            'user_id'    => $user->id,
            'tanggal'    => $request->tanggal,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
        ]);

        return new KaryawanReservationResource($reservation);
    }

    // Update status reservasi (admin)
    public function update(ReservationUpdateRequest $request, $id)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin')) {
            abort(403, 'Hanya admin yang bisa mengubah reservasi.');
        }

        $data = $request->validated();

        $reservation = $this->adminService->updateStatus($id, [
            'status' => $data['status'],
            'reason' => $data['reason'] ?? null,
        ]);

        return new AdminReservationResource($reservation);
    }

    // Approve reservasi (admin)
    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin')) {
            abort(403, 'Hanya admin yang bisa menyetujui reservasi.');
        }

        $reservation = $this->adminService->updateStatus($id, [
            'status' => 'approved',
            'reason' => $request->reason ?? 'Disetujui oleh admin',
        ]);

        return new AdminReservationResource($reservation);
    }

    // Reject reservasi (admin)
    public function reject($id)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin')) {
            abort(403, 'Hanya admin yang bisa menolak reservasi.');
        }

        $reservation = $this->adminService->updateStatus($id, [
            'status' => 'rejected',
            'reason' => 'Ditolak oleh admin',
        ]);

        return new AdminReservationResource($reservation);
    }

    // Hapus reservasi (admin)
    public function destroy($id)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin')) {
            abort(403, 'Hanya admin yang bisa menghapus reservasi.');
        }

        $this->adminService->delete($id);

        return response()->json([
            'message' => 'Reservasi berhasil dihapus.',
        ]);
    }

    // Cancel reservasi (karyawan)
    public function cancel(ReservationCancelRequest $request, $id)
    {
        $user = Auth::user();

        if (! $user->hasRole('karyawan')) {
            abort(403, 'Hanya karyawan yang bisa membatalkan reservasi.');
        }

        $reservation = $this->karyawanService->cancel(
            $id,
            $user->id,
            $request->validated()['reason'] ?? null
        );

        Mail::to('admin@reservasi.com')->send(new ReservationCanceledByUserMail($reservation));

        return new KaryawanReservationResource($reservation);
    }
}
