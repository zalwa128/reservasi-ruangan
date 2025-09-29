<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // bikin permission untuk guard api
        Permission::firstOrCreate(['name' => 'manage reservations', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'view rooms', 'guard_name' => 'api']);

        // role admin untuk guard api
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(['manage reservations', 'view rooms']);

        // role karyawan untuk guard api
        $karyawanRole = Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'api']);
        $karyawanRole->givePermissionTo(['view rooms']);
    }
}
