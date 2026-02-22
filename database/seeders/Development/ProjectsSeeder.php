<?php

namespace Database\Seeders\Development;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('name', 'AkoCloud Demo')->first();
        if (! $organization) {
            return;
        }

        Project::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Projeto Demo',
            ],
            [
                'description' => 'Projeto basico para desenvolvimento e testes manuais',
            ]
        );
    }
}
