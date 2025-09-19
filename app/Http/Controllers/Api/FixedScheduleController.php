<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FixedSchedule;

class FixedScheduleController extends Controller
{
    public function index()
    {
        return response()->json(FixedSchedule::with('room')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'day_of_week' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
        ]);

        $schedule = FixedSchedule::create($request->all());
        return response()->json($schedule, 201);
    }

    public function show(FixedSchedule $fixedSchedule)
    {
        return response()->json($fixedSchedule->load('room'));
    }

    public function update(Request $request, FixedSchedule $fixedSchedule)
    {
        $request->validate([
            'day_of_week' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
        ]);

        $fixedSchedule->update($request->all());
        return response()->json($fixedSchedule);
    }

    public function destroy(FixedSchedule $fixedSchedule)
    {
        $fixedSchedule->delete();
        return response()->json(['message' => 'Fixed schedule deleted']);
    }
}
