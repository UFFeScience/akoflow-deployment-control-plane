<?php

namespace Database\Seeders\Development;

use App\Models\Provider;
use App\Models\ProvisionedResourceKind;
use App\Models\ProvisionedResourceType;
use Illuminate\Database\Seeder;

/**
 * Seeds the provisioned_resource_kinds and provisioned_resource_types tables
 * with the standard taxonomy used by the platform.
 *
 * Kinds  → high-level categories (compute, storage, database, …)
 * Types  → cloud-provider implementations tied to a Terraform resource type
 *           (aws_instance → compute/AWS, google_compute_instance → compute/GCP)
 */
class ProvisionedResourceKindsAndTypesSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Kinds ─────────────────────────────────────────────────────────
        $kindsData = [
            [
                'slug'        => ProvisionedResourceKind::SLUG_COMPUTE,
                'name'        => 'Compute',
                'description' => 'Virtual machines and compute instances.',
            ],
            [
                'slug'        => ProvisionedResourceKind::SLUG_STORAGE,
                'name'        => 'Storage',
                'description' => 'Object and block storage services.',
            ],
            [
                'slug'        => ProvisionedResourceKind::SLUG_DATABASE,
                'name'        => 'Database',
                'description' => 'Managed relational and NoSQL database services.',
            ],
            [
                'slug'        => ProvisionedResourceKind::SLUG_NETWORK,
                'name'        => 'Network',
                'description' => 'Networking resources such as VPCs and load balancers.',
            ],
            [
                'slug'        => ProvisionedResourceKind::SLUG_SERVERLESS,
                'name'        => 'Serverless',
                'description' => 'Function-as-a-service and serverless compute.',
            ],
            [
                'slug'        => ProvisionedResourceKind::SLUG_CONTAINER,
                'name'        => 'Container',
                'description' => 'Container orchestration and registry services.',
            ],
        ];

        /** @var array<string, ProvisionedResourceKind> $kindMap */
        $kindMap = [];
        foreach ($kindsData as $kindData) {
            $kindMap[$kindData['slug']] = ProvisionedResourceKind::updateOrCreate(
                ['slug' => $kindData['slug']],
                [
                    'name'        => $kindData['name'],
                    'description' => $kindData['description'],
                    'is_active'   => true,
                ],
            );
        }

        // ── 2. Types ─────────────────────────────────────────────────────────
        $awsProvider = Provider::where('slug', 'aws')->first();
        $gcpProvider = Provider::where('slug', 'gcp')->first();

        $typesData = [
            // ── AWS ───────────────────────────────────────────────────────────
            [
                'kind_slug'                    => ProvisionedResourceKind::SLUG_COMPUTE,
                'provider'                     => $awsProvider,
                'slug'                         => 'aws_ec2',
                'name'                         => 'AWS EC2 Instance',
                'description'                  => 'Amazon Elastic Compute Cloud virtual machine.',
                'provider_resource_identifier' => 'aws_instance',
            ],
            [
                'kind_slug'                    => ProvisionedResourceKind::SLUG_STORAGE,
                'provider'                     => $awsProvider,
                'slug'                         => 'aws_s3',
                'name'                         => 'AWS S3 Bucket',
                'description'                  => 'Amazon Simple Storage Service bucket.',
                'provider_resource_identifier' => 'aws_s3_bucket',
            ],
            [
                'kind_slug'                    => ProvisionedResourceKind::SLUG_DATABASE,
                'provider'                     => $awsProvider,
                'slug'                         => 'aws_rds',
                'name'                         => 'AWS RDS Instance',
                'description'                  => 'Amazon Relational Database Service managed instance.',
                'provider_resource_identifier' => 'aws_db_instance',
            ],
            // ── GCP ───────────────────────────────────────────────────────────
            [
                'kind_slug'                    => ProvisionedResourceKind::SLUG_COMPUTE,
                'provider'                     => $gcpProvider,
                'slug'                         => 'gcp_compute_engine',
                'name'                         => 'GCP Compute Engine VM',
                'description'                  => 'Google Compute Engine virtual machine instance.',
                'provider_resource_identifier' => 'google_compute_instance',
            ],
            [
                'kind_slug'                    => ProvisionedResourceKind::SLUG_STORAGE,
                'provider'                     => $gcpProvider,
                'slug'                         => 'gcp_cloud_storage',
                'name'                         => 'GCP Cloud Storage Bucket',
                'description'                  => 'Google Cloud Storage bucket.',
                'provider_resource_identifier' => 'google_storage_bucket',
            ],
            [
                'kind_slug'                    => ProvisionedResourceKind::SLUG_DATABASE,
                'provider'                     => $gcpProvider,
                'slug'                         => 'gcp_cloud_sql',
                'name'                         => 'GCP Cloud SQL Instance',
                'description'                  => 'Google Cloud SQL managed database instance.',
                'provider_resource_identifier' => 'google_sql_database_instance',
            ],
        ];

        foreach ($typesData as $typeData) {
            $kind = $kindMap[$typeData['kind_slug']];

            ProvisionedResourceType::updateOrCreate(
                ['slug' => $typeData['slug']],
                [
                    'provisioned_resource_kind_id' => $kind->id,
                    'provider_id'                  => $typeData['provider']?->id,
                    'name'                         => $typeData['name'],
                    'description'                  => $typeData['description'] ?? null,
                    'provider_resource_identifier' => $typeData['provider_resource_identifier'],
                    'is_active'                    => true,
                ],
            );
        }
    }
}
