<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProviderRequest;
use App\Http\Requests\UpdateProviderHealthRequest;
use App\Http\Resources\ProviderResource;
use App\Services\ListProvidersService;
use App\Services\CreateProviderService;
use App\Services\UpdateProviderHealthService;

class ProviderController extends Controller
{
    public function __construct(
        protected ListProvidersService $listService,
        protected CreateProviderService $createService,
        protected UpdateProviderHealthService $healthService,
    ) {}

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $list = $this->listService->handle();
        return ProviderResource::collection($list);
    }

    public function store(CreateProviderRequest $request): ProviderResource
    {
        $provider = $this->createService->handle($request->validated());
        return new ProviderResource($provider);
    }

    public function updateHealth(string $id, UpdateProviderHealthRequest $request): ProviderResource
    {
        $provider = $this->healthService->handle($id, $request->validated());
        return new ProviderResource($provider);
    }
}
