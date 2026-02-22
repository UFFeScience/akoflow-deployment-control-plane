<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExperimentRequest;
use App\Http\Resources\ExperimentResource;
use App\Services\ListExperimentsService;
use App\Services\CreateExperimentService;
use App\Services\GetExperimentService;

class ExperimentController extends Controller
{
    public function __construct(
        protected ListExperimentsService $listService,
        protected CreateExperimentService $createService,
        protected GetExperimentService $getService,
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

    public function show(string $projectId, string $experimentId)
    {
        $exp = $this->getService->handle($projectId, $experimentId);

        if (!$exp) {
            return response()->json(['error' => 'Experiment not found'], 404);
        }

        return new ExperimentResource($exp);
    }
}
