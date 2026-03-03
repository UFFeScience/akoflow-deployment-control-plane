<?php

namespace App\Http\Controllers;

use App\Exceptions\ExperimentNotFoundException;
use App\Http\Requests\CreateExperimentRequest;
use App\Http\Resources\ExperimentResource;
use App\Services\CreateExperimentService;
use App\Services\GetExperimentService;
use App\Services\ListExperimentsService;
use App\Services\ProjectAuthorizationService;

class ExperimentController extends Controller
{
    public function __construct(
        protected ListExperimentsService $listService,
        protected CreateExperimentService $createService,
        protected GetExperimentService $getService,
        protected ProjectAuthorizationService $projectAuthorizationService,
    ) {}

    public function index(string $projectId)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        return ExperimentResource::collection($this->listService->handle($projectId));
    }

    public function store(string $projectId, CreateExperimentRequest $request)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $exp = $this->createService->handle($projectId, $request->validated());

        return new ExperimentResource($exp);
    }

    public function show(string $projectId, string $experimentId)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $exp = $this->getService->handle($projectId, $experimentId);

        if (!$exp) {
            throw new ExperimentNotFoundException();
        }

        return new ExperimentResource($exp);
    }
}
