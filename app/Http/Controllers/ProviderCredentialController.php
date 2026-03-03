<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProviderCredentialRequest;
use App\Http\Resources\ProviderCredentialResource;
use App\Services\CreateProviderCredentialService;
use App\Services\DeleteProviderCredentialService;
use App\Services\ListProviderCredentialsService;

class ProviderCredentialController extends Controller
{
    public function __construct(
        protected ListProviderCredentialsService $listService,
        protected CreateProviderCredentialService $createService,
        protected DeleteProviderCredentialService $deleteService,
    ) {}

    public function index(string $providerId): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $credentials = $this->listService->handle($providerId);
        return ProviderCredentialResource::collection($credentials);
    }

    public function store(string $providerId, CreateProviderCredentialRequest $request): ProviderCredentialResource
    {
        $credential = $this->createService->handle($providerId, $request->validated());
        return new ProviderCredentialResource($credential);
    }

    public function destroy(string $providerId, string $credentialId): \Illuminate\Http\JsonResponse
    {
        $this->deleteService->handle($providerId, $credentialId);
        return response()->json(['message' => 'Credential deleted successfully']);
    }
}
