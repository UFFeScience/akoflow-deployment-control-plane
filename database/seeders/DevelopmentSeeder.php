<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Development\UsersSeeder;
use Database\Seeders\Development\OrganizationsSeeder;
use Database\Seeders\Development\ProjectsSeeder;
use Database\Seeders\Development\ProvidersSeeder;
use Database\Seeders\Development\ProviderSchemasSeeder;
use Database\Seeders\Development\ProviderCredentialsSeeder;
use Database\Seeders\Development\InstanceTypesSeeder;
use Database\Seeders\Development\ProvisionedResourceKindsAndTypesSeeder;
use Database\Seeders\Development\TemplatesSeeder;
use Database\Seeders\Development\TemplateTerraformModulesSeeder;
use Database\Seeders\Development\TemplateAnsiblePlaybooksSeeder;
use Database\Seeders\Development\EnvironmentsSeeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            OrganizationsSeeder::class,
            ProjectsSeeder::class,
            ProvidersSeeder::class,
            ProviderSchemasSeeder::class,
            ProviderCredentialsSeeder::class,
            InstanceTypesSeeder::class,
            ProvisionedResourceKindsAndTypesSeeder::class,
            TemplatesSeeder::class,
            TemplateTerraformModulesSeeder::class,
            TemplateAnsiblePlaybooksSeeder::class,
            EnvironmentsSeeder::class,
        ]);
    }
}
