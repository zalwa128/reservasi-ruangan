<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rooms;

class RoomController extends Controller
{
    public function index()
    {
        return response()->json(Room::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'deskripsi' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        $room = Room::create($request->all());
        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'deskripsi' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        $room->update($request->all());
        return response()->json($room);
    }

    public function destroy(Room $room)
    {
        if ($room->reservations()->whereIn('status', ['pending', 'approved'])->count() > 0) {
            return response()->json(['error' => 'Room cannot be deleted because it has active reservations.'], 400);
        }

        $room->delete();
        return response()->json(['message' => 'Room deleted successfully.']);
    }
}
