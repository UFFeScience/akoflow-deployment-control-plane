<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExperimentRequest;
use App\Http\Resources\ExperimentResource;
use App\Services\ListExperimentsService;
use App\Services\CreateExperimentService;

class ExperimentController extends Controller
{
    public function __construct(
        protected ListExperimentsService $listService,
        protected CreateExperimentService $createService,
    ) {}

    public function index(string $projectId)
    {
        return ExperimentResource::collection($this->listService->handle($projectId));
    }

    public function store(string $projectId, CreateExperimentRequest $request)
    {
        $exp = $this->createService->handle($projectId, $request->validated());
        return new ExperimentResource($exp);
    }
}
