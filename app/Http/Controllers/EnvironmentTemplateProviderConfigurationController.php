<?php

namespace App\Http\Controllers;

use App\Http\Resources\TemplateProviderConfigurationResource;
use App\Services\GetTemplateProviderConfigurationService;
use App\Services\UpsertTemplateProviderConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnvironmentTemplateProviderConfigurationController extends Controller
{
    public function __construct(
        private GetTemplateProviderConfigurationService    $getService,
        private UpsertTemplateProviderConfigurationService $upsertService,
    ) {}

    public function index(string $templateId, string $versionId): AnonymousResourceCollection
    {
        $configs = $this->getService->allForVersion($versionId);
        return TemplateProviderConfigurationResource::collection($configs);
    }

    public function store(string $templateId, string $versionId, Request $request): JsonResponse
    {
        $config = $this->upsertService->createConfig($versionId, $request->all());
        $config->load(['terraformModule', 'playbooks']);
        return response()->json(new TemplateProviderConfigurationResource($config), 201);
    }

    public function show(string $templateId, string $versionId, string $configId): JsonResponse
    {
        $config = $this->getService->findByVersionAndId($versionId, $configId);
        if (!$config) {
            return response()->json(['message' => 'Provider configuration not found.'], 404);
        }
        return response()->json(new TemplateProviderConfigurationResource($config));
    }

    public function update(string $templateId, string $versionId, string $configId, Request $request): JsonResponse
    {
        $config = $this->upsertService->updateConfig($configId, $request->all());
        if (!$config) {
            return response()->json(['message' => 'Provider configuration not found.'], 404);
        }
        return response()->json(new TemplateProviderConfigurationResource($config));
    }

    public function destroy(string $templateId, string $versionId, string $configId): JsonResponse
    {
        $this->upsertService->deleteConfig($configId);
        return response()->json(null, 204);
    }

    public function upsertTerraform(string $templateId, string $versionId, string $configId, Request $request): JsonResponse
    {
        $config = $this->getService->findByVersionAndId($versionId, $configId);
        if (!$config) {
            return response()->json(['message' => 'Provider configuration not found.'], 404);
        }
        $this->upsertService->upsertTerraform($configId, $request->all());
        $config->load(['terraformModule', 'playbooks']);
        return response()->json(new TemplateProviderConfigurationResource($config));
    }
}
