<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProviderCredentialRequest;
use App\Http\Resources\ProviderCredentialHealthLogResource;
use App\Http\Resources\ProviderCredentialResource;
use App\Models\ProviderCredential;
use App\Repositories\ProviderCredentialHealthLogRepository;
use App\Repositories\ProviderCredentialRepository;
use App\Services\CheckCredentialHealthService;
use App\Services\CreateProviderCredentialService;
use App\Services\DeleteProviderCredentialService;
use App\Services\ListProviderCredentialsService;
use App\Services\UpdateProviderCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProviderCredentialController extends Controller
{
    public function __construct(
        protected ListProviderCredentialsService      $listService,
        protected CreateProviderCredentialService     $createService,
        protected UpdateProviderCredentialService     $updateService,
        protected DeleteProviderCredentialService     $deleteService,
        protected CheckCredentialHealthService        $checkHealthService,
        protected ProviderCredentialRepository        $credentialRepository,
        protected ProviderCredentialHealthLogRepository $logRepository,
    ) {}

    public function index(string $organizationId, string $providerId): AnonymousResourceCollection
    {
        $credentials = $this->listService->handle($providerId);
        return ProviderCredentialResource::collection($credentials);
    }

    public function store(string $organizationId, string $providerId, CreateProviderCredentialRequest $request): ProviderCredentialResource
    {
        $credential = $this->createService->handle($providerId, $request->validated());
        return new ProviderCredentialResource($credential);
    }

    public function update(string $organizationId, string $providerId, string $credentialId, CreateProviderCredentialRequest $request): ProviderCredentialResource
    {
        $credential = $this->credentialRepository->findByProviderAndIdOrFail($providerId, $credentialId);
        $updated    = $this->updateService->handle($credential, $request->validated());
        return new ProviderCredentialResource($updated);
    }

    public function destroy(string $organizationId, string $providerId, string $credentialId): JsonResponse
    {
        $this->deleteService->handle($providerId, $credentialId);
        return response()->json(['message' => 'Credential deleted successfully']);
    }

    public function runHealthCheck(string $organizationId, string $providerId, string $credentialId): ProviderCredentialResource
    {
        $credential = $this->credentialRepository->findByProviderAndIdOrFail($providerId, $credentialId);
        $updated    = $this->checkHealthService->handle($credential);
        return new ProviderCredentialResource($updated);
    }

    public function healthLogs(string $organizationId, string $providerId, string $credentialId): AnonymousResourceCollection
    {
        $this->credentialRepository->findByProviderAndIdOrFail($providerId, $credentialId);
        $logs = $this->logRepository->latestByCredential($credentialId);
        return ProviderCredentialHealthLogResource::collection($logs);
    }
}
