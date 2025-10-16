<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FixedScheduleService;
use App\Http\Requests\FixedScheduleRequest;
use App\Http\Resources\Admin\FixedScheduleResource as AdminResource;
use App\Http\Resources\Karyawan\FixedScheduleResource as KaryawanResource;
use App\Models\Room;

class FixedScheduleController extends Controller
{
    protected $service;

    public function __construct(FixedScheduleService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $filters = [
            'room_id'     => $request->query('room_id'),
            'day_of_week' => $request->query('day_of_week'),
            'start_time'  => $request->query('start_time'),
            'end_time'    => $request->query('end_time'),
            'page'         => $request->query('page', 1),
            'per_page'    => $request->query('per_page', 10),
        ];

        try {
            $result = $this->service->getFiltered($filters);

            $collection = $user->hasRole('admin')
                ? AdminResource::collection($result['data'])
                : KaryawanResource::collection($result['data']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Daftar jadwal tetap berhasil diambil.',
                'data'    => $collection,
                'meta'    => $result['meta'],
            ], 200);
        } catch (\Throwable $th) {
            return $this->responseError('Terjadi kesalahan server: ' . $th->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        try {
            $schedule = $this->service->getById($id);
            $resource = $user->hasRole('admin')
                ? new AdminResource($schedule)
                : new KaryawanResource($schedule);

            return $this->responseSuccess('Detail jadwal tetap berhasil diambil.', $resource);
        } catch (\Throwable $th) {
            return $this->responseError('Data tidak ditemukan: ' . $th->getMessage(), 404);
        }
    }

    public function store(FixedScheduleRequest $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return $this->responseError('Hanya admin yang bisa menambahkan jadwal tetap.', 403);
        }

        try {
            $schedule = $this->service->create($request->validated());
            return $this->responseSuccess('Jadwal tetap berhasil dibuat.', new AdminResource($schedule));
        } catch (\Throwable $th) {
            return $this->responseError('Gagal membuat jadwal tetap: ' . $th->getMessage(), 500);
        }
    }

    public function update(FixedScheduleRequest $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return $this->responseError('Hanya admin yang bisa memperbarui jadwal tetap.', 403);
        }

        try {
            $schedule = $this->service->updateById($id, $request->validated());
            return $this->responseSuccess('Jadwal tetap berhasil diperbarui.', new AdminResource($schedule));
        } catch (\Throwable $th) {
            return $this->responseError('Gagal memperbarui jadwal tetap: ' . $th->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return $this->responseError('Hanya admin yang bisa menghapus jadwal tetap.', 403);
        }

        try {
            $this->service->deleteById($id);
            return $this->responseSuccess('Jadwal tetap berhasil dihapus.');
        } catch (\Throwable $th) {
            return $this->responseError('Gagal menghapus jadwal tetap: ' . $th->getMessage(), 500);
        }
    }

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
