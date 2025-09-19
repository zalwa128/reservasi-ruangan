<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Room
            'create room',
            'edit room',
            'delete room',
            'view room',

            // Reservation
            'create reservation',
            'edit reservation',
            'delete reservation',
            'view reservation',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $karyawan = Role::firstOrCreate(['name' => 'karyawan']);

        $admin->syncPermissions(Permission::all());

        $karyawan->syncPermissions([
            'create reservation',
            'edit reservation',
            'view reservation',
        ]);
    }
}
