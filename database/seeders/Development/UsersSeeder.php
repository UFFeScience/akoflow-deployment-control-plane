<?php

namespace Database\Seeders\Development;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@akoflow.com'],
            [
                'name' => 'Admin Ako',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}
