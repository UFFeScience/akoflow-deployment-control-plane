<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProviderRequest;
use App\Http\Requests\UpdateProviderHealthRequest;
use App\Http\Resources\ProviderResource;
use App\Services\CheckProviderHealthService;
use App\Services\CreateProviderService;
use App\Services\ListProvidersService;
use App\Services\ShowProviderService;
use App\Services\UpdateProviderHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProviderController extends Controller
{
    public function __construct(
        protected ListProvidersService $listService,
        protected ShowProviderService $showService,
        protected CreateProviderService $createService,
        protected UpdateProviderHealthService $healthService,
        protected CheckProviderHealthService $checkHealthService,
    ) {}

    public function index(string $organizationId): AnonymousResourceCollection
    {
        $list = $this->listService->handle($organizationId);
        return ProviderResource::collection($list);
    }

    public function show(string $organizationId, string $id): ProviderResource
    {
        $provider = $this->showService->handle($id, $organizationId);
        return ProviderResource::make($provider);
    }

    public function store(string $organizationId, CreateProviderRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), ['organization_id' => $organizationId]);
        $provider = $this->createService->handle($data);
        return response()->json(['data' => new ProviderResource($provider)], 201);
    }

    public function updateHealth(string $organizationId, string $id, UpdateProviderHealthRequest $request): ProviderResource
    {
        $provider = $this->healthService->handle($id, $organizationId, $request->validated());
        return ProviderResource::make($provider);
    }

    public function runHealthCheck(string $organizationId, string $id): JsonResponse
    {
        $provider = $this->checkHealthService->handle($id, $organizationId);
        return response()->json(['data' => new ProviderResource($provider)], 201);
    }
}
