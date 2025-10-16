<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $filters = [
            'name' => $request->input('name'),
            'role' => $request->input('role'),
            'per_page' => $request->input('per_page', 10),
            'page' => $request->input('page', 1),
        ];

        $users = $this->userService->getFiltered($filters);

        if ($users->isEmpty()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Tidak ada user yang ditemukan.',
                'data'    => null,
            ], 200);
        }

        $userData = UserResource::collection($users->items())->resolve();

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar user berhasil ditampilkan.',
            'data'    => $userData,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());
        $userData = (new UserResource($user))->resolve();

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil dibuat.',
            'data'    => $userData,
        ]);
    }

    public function show($id)
    {
        $user = $this->userService->find($id);
        $userData = (new UserResource($user))->resolve();

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail user berhasil ditampilkan.',
            'data'    => $userData,
        ]);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = $this->userService->update($id, $request->validated());
        $userData = (new UserResource($user))->resolve();

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil diperbarui.',
            'data'    => $userData,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->userService->delete($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'User berhasil dihapus.',
            'data'    => null,
        ]);
    }
}
