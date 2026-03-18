<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEnvironmentTemplateRequest;
use App\Http\Requests\CreateEnvironmentTemplateVersionRequest;
use App\Http\Resources\EnvironmentTemplateResource;
use App\Http\Resources\EnvironmentTemplateVersionResource;
use App\Models\EnvironmentTemplateVersion;
use App\Services\ActivateTemplateVersionService;
use App\Services\AddEnvironmentTemplateVersionService;
use App\Services\CreateEnvironmentTemplateService;
use App\Services\GetActiveEnvironmentTemplateVersionService;
use App\Services\GetEnvironmentTemplateService;
use App\Services\ListEnvironmentTemplatesService;
use App\Services\ListEnvironmentTemplateVersionsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnvironmentTemplateController extends Controller
{
    public function __construct(
        protected ListEnvironmentTemplatesService           $listService,
        protected CreateEnvironmentTemplateService          $createService,
        protected AddEnvironmentTemplateVersionService      $addVersionService,
        protected GetActiveEnvironmentTemplateVersionService $activeVersionService,
        protected GetEnvironmentTemplateService             $getService,
        protected ListEnvironmentTemplateVersionsService    $listVersionsService,
        protected ActivateTemplateVersionService           $activateVersionService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return EnvironmentTemplateResource::collection($this->listService->handle());
    }

    public function show(string $id)
    {
        $template = $this->getService->handle($id);
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }
        return EnvironmentTemplateResource::make($template);
    }

    public function store(CreateEnvironmentTemplateRequest $request)
    {
        $tpl = $this->createService->handle($request->validated());
        return EnvironmentTemplateResource::make($tpl);
    }

    public function listVersions(string $id): mixed
    {
        $versions = $this->listVersionsService->handle($id);
        if ($versions === null) {
            return response()->json(['error' => 'Template not found'], 404);
        }
        return EnvironmentTemplateVersionResource::collection($versions);
    }

    public function showVersion(string $id, string $versionId)
    {
        $version = EnvironmentTemplateVersion::where('template_id', $id)
            ->with('terraformModules')
            ->find($versionId);

        if (!$version) {
            return response()->json(['error' => 'Version not found'], 404);
        }
        return EnvironmentTemplateVersionResource::make($version);
    }

    public function addVersion(string $id, CreateEnvironmentTemplateVersionRequest $request)
    {
        $version = $this->addVersionService->handle($id, $request->validated());
        return EnvironmentTemplateVersionResource::make($version)->response()->setStatusCode(201);
    }

    public function activateVersion(string $id, string $versionId)
    {
        $version = $this->activateVersionService->handle($id, $versionId);
        if (!$version) {
            return response()->json(['error' => 'Version not found'], 404);
        }
        return EnvironmentTemplateVersionResource::make($version);
    }

    public function showActiveVersion(string $id)
    {
        $version = $this->activeVersionService->handle($id);
        if (!$version) {
            return response()->json(['error' => 'No active version found for this template'], 404);
        }
        return EnvironmentTemplateVersionResource::make($version);
    }
}
