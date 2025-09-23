<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UserSeeders extends Seeder
{
    public function run(): void
    {
        // === ADMIN USER ===
        $admin = User::create([
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'password' => Hash::make('admin123'),
            ]);

        $admin->assignRole('admin');
        $admin->role ='admin';
        $admin->save();
    }
}
