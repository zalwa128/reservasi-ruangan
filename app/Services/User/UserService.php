<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Ambil semua user (lama)
     */
    public function getAll()
    {
        return User::with('roles')->get();
    }

    /**
     * Ambil user dengan filter & pagination
     */
    public function getFiltered(array $filters)
    {
        $query = User::with('roles');

        // Filter berdasarkan nama (like)
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // Filter berdasarkan role
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Urutkan berdasarkan nama (default)
        $query->orderBy('name', 'asc');

        // Pagination
        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function find($id)
    {
        return User::with('roles')->findOrFail($id);
    }

    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user->load('roles');
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user->load('roles');
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
    }
}
