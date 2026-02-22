<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExperimentTemplateRequest;
use App\Http\Requests\CreateExperimentTemplateVersionRequest;
use App\Http\Resources\ExperimentTemplateResource;
use App\Http\Resources\ExperimentTemplateVersionResource;
use App\Services\ListExperimentTemplatesService;
use App\Services\CreateExperimentTemplateService;
use App\Services\AddExperimentTemplateVersionService;

class ExperimentTemplateController extends Controller
{
    public function __construct(
        protected ListExperimentTemplatesService $listService,
        protected CreateExperimentTemplateService $createService,
        protected AddExperimentTemplateVersionService $addVersionService,
    ) {}

    public function index()
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
}
