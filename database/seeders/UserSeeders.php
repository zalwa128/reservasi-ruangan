<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeders extends Seeder
{
    public function run(): void
    {
        // === ADMIN USER ===
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin123'),
            ]
        );
        $admin->assignRole('admin');
        $admin->role ='admin';
        $admin->save();
    }
}
