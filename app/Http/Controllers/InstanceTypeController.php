<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInstanceTypeRequest;
use App\Http\Requests\UpdateInstanceTypeStatusRequest;
use App\Http\Resources\InstanceTypeResource;
use App\Services\ListInstanceTypesService;
use App\Services\CreateInstanceTypeService;
use App\Services\UpdateInstanceTypeStatusService;

class InstanceTypeController extends Controller
{
    public function __construct(
        protected ListInstanceTypesService $listService,
        protected CreateInstanceTypeService $createService,
        protected UpdateInstanceTypeStatusService $statusService,
    ) {}

    public function index()
    {
        return InstanceTypeResource::collection($this->listService->handle());
    }

    public function store(CreateInstanceTypeRequest $request)
    {
        $it = $this->createService->handle($request->validated());
        return new InstanceTypeResource($it);
    }

    public function updateStatus(string $id, UpdateInstanceTypeStatusRequest $request)
    {
        $it = $this->statusService->handle($id, $request->validated()['status']);
        return new InstanceTypeResource($it);
    }
}
