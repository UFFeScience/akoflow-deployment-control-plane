<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEnvironmentTemplateRequest;
use App\Http\Requests\CreateEnvironmentTemplateVersionRequest;
use App\Http\Resources\EnvironmentTemplateResource;
use App\Http\Resources\EnvironmentTemplateVersionResource;
use App\Services\ActivateTemplateVersionService;
use App\Services\AddEnvironmentTemplateVersionService;
use App\Services\CreateEnvironmentTemplateService;
use App\Services\GetActiveEnvironmentTemplateVersionService;
use App\Services\GetEnvironmentTemplateService;
use App\Services\GetEnvironmentTemplateVersionService;
use App\Services\ListEnvironmentTemplatesService;
use App\Services\ListEnvironmentTemplateVersionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnvironmentTemplateController extends Controller
{
    public function __construct(
        protected ListEnvironmentTemplatesService            $listService,
        protected CreateEnvironmentTemplateService           $createService,
        protected AddEnvironmentTemplateVersionService       $addVersionService,
        protected GetActiveEnvironmentTemplateVersionService $activeVersionService,
        protected GetEnvironmentTemplateService              $getService,
        protected GetEnvironmentTemplateVersionService       $getVersionService,
        protected ListEnvironmentTemplateVersionsService     $listVersionsService,
        protected ActivateTemplateVersionService             $activateVersionService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return EnvironmentTemplateResource::collection($this->listService->handle());
    }

    public function show(string $id): JsonResponse
    {
        $template = $this->getService->handle($id);
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }
        return response()->json(EnvironmentTemplateResource::make($template));
    }

    public function store(CreateEnvironmentTemplateRequest $request): JsonResponse
    {
        $tpl = $this->createService->handle($request->validated());
        return response()->json(EnvironmentTemplateResource::make($tpl));
    }

    public function listVersions(string $id): mixed
    {
        $versions = $this->listVersionsService->handle($id);
        if ($versions === null) {
            return response()->json(['error' => 'Template not found'], 404);
        }
        return EnvironmentTemplateVersionResource::collection($versions);
    }

    public function showVersion(string $id, string $versionId): JsonResponse
    {
        $version = $this->getVersionService->handle($id, $versionId);
        if (!$version) {
            return response()->json(['error' => 'Version not found'], 404);
        }
        return response()->json(EnvironmentTemplateVersionResource::make($version));
    }

    public function addVersion(string $id, CreateEnvironmentTemplateVersionRequest $request): JsonResponse
    {
        $version = $this->addVersionService->handle($id, $request->validated());
        return response()->json(EnvironmentTemplateVersionResource::make($version), 201);
    }

    public function activateVersion(string $id, string $versionId): JsonResponse
    {
        $version = $this->activateVersionService->handle($id, $versionId);
        if (!$version) {
            return response()->json(['error' => 'Version not found'], 404);
        }
        return response()->json(EnvironmentTemplateVersionResource::make($version));
    }

    public function showVersionById(string $versionId): JsonResponse
    {
        $version = $this->getVersionService->findById($versionId);
        if (!$version) {
            return response()->json(['error' => 'Version not found'], 404);
        }
        return response()->json(EnvironmentTemplateVersionResource::make($version));
    }

    public function showActiveVersion(string $id): JsonResponse
    {
        $version = $this->activeVersionService->handle($id);
        if (!$version) {
            return response()->json(['error' => 'No active version found for this template'], 404);
        }
        return response()->json(EnvironmentTemplateVersionResource::make($version));
    }
}
