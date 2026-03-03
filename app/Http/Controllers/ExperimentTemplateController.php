<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExperimentTemplateRequest;
use App\Http\Requests\CreateExperimentTemplateVersionRequest;
use App\Http\Resources\ExperimentTemplateResource;
use App\Http\Resources\ExperimentTemplateVersionResource;
use App\Services\AddExperimentTemplateVersionService;
use App\Services\CreateExperimentTemplateService;
use App\Services\GetActiveExperimentTemplateVersionService;
use App\Services\ListExperimentTemplatesService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExperimentTemplateController extends Controller
{
    public function __construct(
        protected ListExperimentTemplatesService $listService,
        protected CreateExperimentTemplateService $createService,
        protected AddExperimentTemplateVersionService $addVersionService,
        protected GetActiveExperimentTemplateVersionService $activeVersionService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ExperimentTemplateResource::collection($this->listService->handle());
    }

    public function store(CreateExperimentTemplateRequest $request)
    {
        $tpl = $this->createService->handle($request->validated());
        return new ExperimentTemplateResource($tpl);
    }

    public function addVersion(string $id, CreateExperimentTemplateVersionRequest $request)
    {
        $version = $this->addVersionService->handle($id, $request->validated());
        return new ExperimentTemplateVersionResource($version);
    }

    public function showActiveVersion(string $id)
    {
        $version = $this->activeVersionService->handle($id);
        if (!$version) {
            return response()->json(['error' => 'No active version found for this template'], 404);
        }
        return new ExperimentTemplateVersionResource($version);
    }
}
