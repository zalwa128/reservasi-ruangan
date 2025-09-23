<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FixedScheduleRequest;
use App\Http\Resources\Admin\FixedScheduleResource as AdminResource;
use App\Http\Resources\Karyawan\FixedScheduleResource as KaryawanResource;
use App\Services\FixedScheduleService;
use Illuminate\Support\Facades\Auth;

class FixedScheduleController extends Controller
{
    protected $fixedScheduleService;

    public function __construct(FixedScheduleService $fixedScheduleService)
    {
        $this->fixedScheduleService = $fixedScheduleService;
    }

    /**
     * Get all fixed schedules
     */
    public function index()
    {
        $schedules = $this->fixedScheduleService->getAll();

        // Pilih resource berdasarkan role user
        $resource = Auth::user()->hasRole('admin') ? AdminResource::class : KaryawanResource::class;

        return $resource::collection($schedules);
    }

    /**
     * Store a new fixed schedule
     */
    public function store(FixedScheduleRequest $request)
    {
        $schedule = $this->fixedScheduleService->create($request->validated());

        $resource = Auth::user()->hasRole('admin') ? AdminResource::class : KaryawanResource::class;

        return new $resource($schedule);
    }

    /**
     * Show a specific fixed schedule
     */
    public function show($id)
    {
        $schedule = $this->fixedScheduleService->find($id);

        $resource = Auth::user()->hasRole('admin') ? AdminResource::class : KaryawanResource::class;

        return new $resource($schedule);
    }

    /**
     * Update a fixed schedule
     */
    public function update(FixedScheduleRequest $request, $id)
    {
        $schedule = $this->fixedScheduleService->update($id, $request->validated());

        $resource = Auth::user()->hasRole('admin') ? AdminResource::class : KaryawanResource::class;

        return new $resource($schedule);
    }

    /**
     * Delete a fixed schedule
     */
    public function destroy($id)
    {
        $this->fixedScheduleService->delete($id);

        return response()->json([
            'message' => 'Fixed schedule deleted successfully'
        ]);
    }
}
