<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExperimentTemplateRequest;
use App\Http\Requests\CreateExperimentTemplateVersionRequest;
use App\Http\Resources\ExperimentTemplateResource;
use App\Http\Resources\ExperimentTemplateVersionResource;
use App\Models\ExperimentTemplateVersion;
use App\Services\ActivateTemplateVersionService;
use App\Services\AddExperimentTemplateVersionService;
use App\Services\CreateExperimentTemplateService;
use App\Services\GetActiveExperimentTemplateVersionService;
use App\Services\GetExperimentTemplateService;
use App\Services\ListExperimentTemplatesService;
use App\Services\ListExperimentTemplateVersionsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExperimentTemplateController extends Controller
{
    public function __construct(
        protected ListExperimentTemplatesService           $listService,
        protected CreateExperimentTemplateService          $createService,
        protected AddExperimentTemplateVersionService      $addVersionService,
        protected GetActiveExperimentTemplateVersionService $activeVersionService,
        protected GetExperimentTemplateService             $getService,
        protected ListExperimentTemplateVersionsService    $listVersionsService,
        protected ActivateTemplateVersionService           $activateVersionService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ExperimentTemplateResource::collection($this->listService->handle());
    }

    public function show(string $id)
    {
        $template = $this->getService->handle($id);
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }
        return new ExperimentTemplateResource($template);
    }

    public function store(CreateExperimentTemplateRequest $request)
    {
        $tpl = $this->createService->handle($request->validated());
        return new ExperimentTemplateResource($tpl);
    }

    public function listVersions(string $id): mixed
    {
        $versions = $this->listVersionsService->handle($id);
        if ($versions === null) {
            return response()->json(['error' => 'Template not found'], 404);
        }
        return ExperimentTemplateVersionResource::collection($versions);
    }

    public function showVersion(string $id, string $versionId)
    {
        $version = ExperimentTemplateVersion::where('template_id', $id)
            ->with('terraformModule')
            ->find($versionId);

        if (!$version) {
            return response()->json(['error' => 'Version not found'], 404);
        }
        return new ExperimentTemplateVersionResource($version);
    }

    public function addVersion(string $id, CreateExperimentTemplateVersionRequest $request)
    {
        $version = $this->addVersionService->handle($id, $request->validated());
        return (new ExperimentTemplateVersionResource($version))->response()->setStatusCode(201);
    }

    public function activateVersion(string $id, string $versionId)
    {
        $version = $this->activateVersionService->handle($id, $versionId);
        if (!$version) {
            return response()->json(['error' => 'Version not found'], 404);
        }
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
