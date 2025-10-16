<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Ambil semua user (deprecated, bisa pakai getFiltered)
     */
    public function getAll()
    {
        return User::with('roles')->get();
    }

    /**
     * Ambil user dengan filter + pagination
     */
    public function getFiltered(array $filters)
    {
        $query = User::with('roles');

        // Filter nama
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // Filter role
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Urutkan nama
        $query->orderBy('id', 'asc');

        // Pagination dengan page dan per_page
        return $query->paginate(
            $filters['per_page'] ?? 10,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );
    }

    /**
     * Ambil detail user
     */
    public function find($id)
    {
        return User::with('roles')->findOrFail($id);
    }

    /**
     * Buat user baru
     */
    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user->load('roles');
    }

    /**
     * Update user
     */
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

    /**
     * Hapus user
     */
    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
    }
}
