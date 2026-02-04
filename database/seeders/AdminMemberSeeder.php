<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Member;
use Illuminate\Support\Str;

class AdminMemberSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@inventory.test')->first();

        if (!$user) return;

        Member::updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'name' => 'Super Admin',
                'position' => 'Administrator',
                'department' => 'IT',
            ]
        );
    }
}
