<?php

namespace App\Repositories;

use App\Models\ProviderCredential;
use App\Models\ProviderCredentialValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProviderCredentialRepository extends BaseRepository
{
    public function __construct(
        ProviderCredential $model,
        private ProviderCredentialValue $valueModel,
    ) {
        parent::__construct($model);
    }

    public function allByProvider(string $providerId): Collection
    {
        return $this->model
            ->where('provider_id', $providerId)
            ->with('values')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createWithValues(array $credentialData, array $values): ProviderCredential
    {
        /** @var ProviderCredential $credential */
        $credential = $this->model->create($credentialData);

        foreach ($values as $fieldKey => $fieldValue) {
            $this->valueModel->create([
                'provider_credential_id' => $credential->id,
                'field_key'              => $fieldKey,
                'field_value'            => $fieldValue,
            ]);
        }

        return $credential->load('values');
    }

    public function findByProviderAndId(string $providerId, string $credentialId): ?ProviderCredential
    {
        return $this->model
            ->where('provider_id', $providerId)
            ->where('id', $credentialId)
            ->first();
    }

    public function findByProviderAndIdOrFail(string $providerId, string $credentialId): ProviderCredential
    {
        $credential = $this->findByProviderAndId($providerId, $credentialId);

        if (!$credential) {
            throw (new ModelNotFoundException())->setModel(ProviderCredential::class, $credentialId);
        }

        return $credential;
    }

    public function deleteByProviderAndId(string $providerId, string $credentialId): void
    {
        $credential = $this->findByProviderAndIdOrFail($providerId, $credentialId);
        $credential->delete();
    }
}
