<?php

namespace Database\Seeders\Development;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@akoflow.com')->first();
        if (! $admin) {
            return;
        }

        $organization = Organization::firstOrCreate(
            [
                'name' => 'AkoCloud Demo',
                'user_id' => $admin->id,
            ],
            [
                'description' => 'Initial organization for development and testing.',
            ]
        );

        $organization->members()->syncWithoutDetaching([
            $admin->id => ['role' => 'owner'],
        ]);
    }
}
