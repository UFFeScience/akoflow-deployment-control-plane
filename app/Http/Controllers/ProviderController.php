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

    public function index(): AnonymousResourceCollection
    {
        $list = $this->listService->handle();
        return ProviderResource::collection($list);
    }

    public function show(string $id): ProviderResource
    {
        $provider = $this->showService->handle($id);
        return ProviderResource::make($provider);
    }

    public function store(CreateProviderRequest $request): ProviderResource
    {
        $provider = $this->createService->handle($request->validated());
        return ProviderResource::make($provider);
    }

    public function updateHealth(string $id, UpdateProviderHealthRequest $request): ProviderResource
    {
        $provider = $this->healthService->handle($id, $request->validated());
        return ProviderResource::make($provider);
    }

    public function runHealthCheck(string $id): ProviderResource
    {
        $provider = $this->checkHealthService->handle($id);
        return ProviderResource::make($provider);
    }
}
