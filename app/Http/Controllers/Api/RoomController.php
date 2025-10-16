<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\Admin\RoomResource as AdminRoomResource;
use App\Http\Resources\Karyawan\RoomResource as KaryawanRoomResource;
use App\Services\RoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    protected $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
                'data'    => null,
            ], 401);
        }

        $filters = [
            'nama_ruangan' => $request->query('nama_ruangan'),
            'capacity'     => $request->query('capacity'),
            'status'       => $request->query('status'),
            'page'         => $request->query('page', 1),
            'per_page'     => $request->query('per_page', 10),
        ];

        if (!empty($filters['status']) && !in_array(strtolower($filters['status']), ['aktif', 'non-aktif'])) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Status hanya boleh AKTIF atau NON-AKTIF.',
                'data'    => null,
            ], 400);
        }

        if (!empty($filters['capacity']) && !is_numeric($filters['capacity'])) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Capacity harus berupa angka.',
                'data'    => null,
            ], 400);
        }

        $query = $this->roomService->getAll($filters);

        $rooms = $query->paginate($filters['per_page'], ['*'], 'page', $filters['page']);

        if ($rooms->isEmpty()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Tidak ada ruangan yang ditemukan.',
                'data'    => null,
            ], 200);
        }

        $resource = $user->hasRole('admin')
            ? AdminRoomResource::collection($rooms->items())
            : KaryawanRoomResource::collection($rooms->items());

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar ruangan berhasil ditampilkan.',
            'data'    => $resource->resolve(),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page'    => $rooms->lastPage(),
                'per_page'     => $rooms->perPage(),
                'total'        => $rooms->total(),
            ]
        ]);
    }

    /**
     * Detail ruangan
     */
    public function show($id)
    {
        $room = $this->roomService->find($id);

        if (!$room) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Ruangan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $resource = Auth::user()->hasRole('admin')
            ? new AdminRoomResource($room)
            : new KaryawanRoomResource($room);

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail ruangan berhasil ditampilkan.',
            'data'    => $resource->resolve(),
        ]);
    }

    /**
     * Tambah ruangan
     */
    public function store(RoomRequest $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Hanya admin yang diperbolehkan menambah ruangan.',
                'data'    => null,
            ], 403);
        }

        $room = $this->roomService->create($request->validated());

        $resource = (new AdminRoomResource($room))->resolve();

        return response()->json([
            'status'  => 'success',
            'message' => 'Ruangan baru telah berhasil dibuat.',
            'data'    => $resource,
        ]);
    }

    /**
     * Update ruangan
     */
    public function update(RoomRequest $request, $id)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Hanya admin yang dapat memperbarui ruangan.',
                'data'    => null,
            ], 403);
        }

        $room = $this->roomService->update($id, $request->validated());

        if (!$room) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Ruangan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $resource = (new AdminRoomResource($room))->resolve();

        return response()->json([
            'status'  => 'success',
            'message' => 'Informasi ruangan berhasil diperbarui.',
            'data'    => $resource,
        ]);
    }

    /**
     * Hapus ruangan
     */
    public function destroy($id)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Hanya admin yang diperbolehkan menghapus ruangan.',
                'data'    => null,
            ], 403);
        }

        $room = $this->roomService->find($id);
        if (!$room) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Ruangan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        try {
            $this->roomService->delete($id);
            return response()->json([
                'status'  => 'success',
                'message' => 'Ruangan berhasil dihapus dari sistem.',
                'data'    => null,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'failed',
                'errors' => $e->errors(),
                'data'   => null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'failed',
                'message' => $e->getMessage(),
                'data'    => null,
            ], 400);
        }
    }
}
