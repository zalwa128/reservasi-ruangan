<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FixedScheduleRequest;
use App\Http\Resources\Admin\FixedScheduleResource as AdminResource;
use App\Http\Resources\Karyawan\FixedScheduleResource as KaryawanResource;
use App\Services\FixedScheduleService;
use Illuminate\Support\Facades\Auth;
use App\Models\FixedSchedule;
use Illuminate\Http\Request;

class FixedScheduleController extends Controller
{
    protected $service;

    public function __construct(FixedScheduleService $service)
    {
        $this->service = $service;
    }

    /**
     * 🔹 Tampilkan semua Fixed Schedule dengan filter & pagination
     */
    public function index(Request $request)
    {
        $filters = [
            'room_id'     => $request->input('room_id'),
            'day_of_week' => $request->input('day_of_week'),
            'start_time'  => $request->input('start_time'),
            'end_time'    => $request->input('end_time'),
            'per_page'    => $request->input('per_page', 10),
        ];

        $schedules = $this->service->getFiltered($filters);

        return Auth::user()->hasRole('admin')
            ? AdminResource::collection($schedules)
            : KaryawanResource::collection($schedules);
    }

    /**
     * 🔹 Tambah Fixed Schedule (Admin Only)
     */
    public function store(FixedScheduleRequest $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();
        unset($data['tanggal']); // pastikan tanggal tidak ikut

        $schedule = $this->service->create($data);
        return new AdminResource($schedule);
    }

    /**
     * 🔹 Detail Fixed Schedule
     */
    public function show(FixedSchedule $schedule)
    {
        return Auth::user()->hasRole('admin')
            ? new AdminResource($schedule->load(['room', 'user']))
            : new KaryawanResource($schedule->load(['room', 'user']));
    }

    /**
     * 🔹 Update Fixed Schedule (Admin Only)
     */
    public function update(FixedScheduleRequest $request, FixedSchedule $schedule)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();
        unset($data['tanggal']); // pastikan tanggal tidak ikut

        $updatedSchedule = $this->service->update($schedule, $data);
        return new AdminResource($updatedSchedule);
    }

    /**
     * 🔹 Hapus Fixed Schedule (Admin Only)
     */
    public function destroy(FixedSchedule $schedule)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->service->delete($schedule);
        return response()->json(['message' => 'FixedSchedule deleted successfully']);
    }
}
