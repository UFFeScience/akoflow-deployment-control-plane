<?php

namespace App\Http\Controllers;

use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Requests\CreateEnvironmentRequest;
use App\Http\Resources\EnvironmentResource;
use App\Services\CreateEnvironmentService;
use App\Services\GetEnvironmentService;
use App\Services\ListEnvironmentsService;
use App\Services\ProjectAuthorizationService;

class EnvironmentController extends Controller
{
    public function __construct(
        protected ListEnvironmentsService $listService,
        protected CreateEnvironmentService $createService,
        protected GetEnvironmentService $getService,
        protected ProjectAuthorizationService $projectAuthorizationService,
    ) {}

    public function index(string $projectId)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        return EnvironmentResource::collection($this->listService->handle($projectId));
    }

    public function store(string $projectId, CreateEnvironmentRequest $request)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $exp = $this->createService->handle($projectId, $request->validated());

        return new EnvironmentResource($exp);
    }

    public function show(string $projectId, string $environmentId)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $exp = $this->getService->handle($projectId, $environmentId);

        if (!$exp) {
            throw new EnvironmentNotFoundException();
        }

        return new EnvironmentResource($exp);
    }
}
