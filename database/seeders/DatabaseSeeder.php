<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('testing')) {
            $this->call([
                UserSeeder::class,
                OrganizationSeeder::class,
                ProjectSeeder::class,
            ]);

            return;
        }

        $this->call([
            DevelopmentSeeder::class,
        ]);
    }
}
