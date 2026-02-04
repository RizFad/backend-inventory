<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'admin@inventory.test',
            ],
            [
                'name' => 'Super Admin',
                'email' => 'admin@inventory.test',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
