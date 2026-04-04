<?php

namespace Database\Seeders\Development;


use App\Models\Provider;
use App\Models\ProviderCredential;
use App\Models\ProviderCredentialValue;
use Illuminate\Database\Seeder;
use Database\Seeders\Development\ProviderCredentials\CredentialsData;

class ProviderCredentialsSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = CredentialsData::get();

        foreach ($credentials as $credentialData) {
            $provider = Provider::where('slug', $credentialData['provider_slug'])->first();

            if (! $provider) {
                continue;
            }

            $credential = ProviderCredential::firstOrCreate(
                [
                    'provider_id' => $provider->id,
                    'slug'        => $credentialData['slug'],
                ],
                [
                    'name'                  => $credentialData['name'],
                    'description'           => $credentialData['description'],
                    'is_active'             => $credentialData['is_active'],
                    'health_check_template' => $credentialData['health_check_template'] ?? null,
                ]
            );

            // Always keep template up-to-date on subsequent seed runs
            if ($credential->health_check_template !== ($credentialData['health_check_template'] ?? null)) {
                $credential->update(['health_check_template' => $credentialData['health_check_template'] ?? null]);
            }

            foreach ($credentialData['values'] as $key => $value) {
                ProviderCredentialValue::firstOrCreate(
                    [
                        'provider_credential_id' => $credential->id,
                        'field_key'              => $key,
                    ],
                    [
                        'field_value' => $value,
                    ]
                );
            }
        }
    }
}
