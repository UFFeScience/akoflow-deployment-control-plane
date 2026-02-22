<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Development\UsersSeeder;
use Database\Seeders\Development\OrganizationsSeeder;
use Database\Seeders\Development\ProjectsSeeder;
use Database\Seeders\Development\ProvidersSeeder;
use Database\Seeders\Development\InstanceTypesSeeder;
use Database\Seeders\Development\TemplatesSeeder;
use Database\Seeders\Development\ExperimentsSeeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            OrganizationsSeeder::class,
            ProjectsSeeder::class,
            ProvidersSeeder::class,
            InstanceTypesSeeder::class,
            TemplatesSeeder::class,
            ExperimentsSeeder::class,
        ]);
    }
}
