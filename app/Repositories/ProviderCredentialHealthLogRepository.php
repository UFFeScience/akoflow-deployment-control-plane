<?php

namespace App\Repositories;

use App\Models\ProviderCredentialHealthLog;
use Illuminate\Database\Eloquent\Collection;

class ProviderCredentialHealthLogRepository extends BaseRepository
{
    public function __construct(ProviderCredentialHealthLog $model)
    {
        parent::__construct($model);
    }

    public function createForCredential(string $credentialId, string $status, ?string $message): ProviderCredentialHealthLog
    {
        /** @var ProviderCredentialHealthLog $log */
        $log = $this->model->create([
            'provider_credential_id' => $credentialId,
            'health_status'          => $status,
            'health_message'         => $message,
            'checked_at'             => now(),
        ]);

        return $log;
    }

    public function latestByCredential(string $credentialId, int $limit = 20): Collection
    {
        return $this->model
            ->where('provider_credential_id', $credentialId)
            ->orderByDesc('checked_at')
            ->limit($limit)
            ->get();
    }
}
