<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::all();

        foreach ($organizations as $organization) {
            Project::factory(3)->create([
                'organization_id' => $organization->id,
            ]);
        }
    }
}
