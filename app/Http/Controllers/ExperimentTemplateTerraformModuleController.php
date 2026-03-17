<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertTemplateTerraformModuleRequest;
use App\Http\Resources\TemplateTerraformModuleResource;
use App\Services\GetTemplateTerraformModuleService;
use App\Services\UpsertTemplateTerraformModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExperimentTemplateTerraformModuleController extends Controller
{
    public function __construct(
        private UpsertTemplateTerraformModuleService $upsertService,
        private GetTemplateTerraformModuleService    $getService,
    ) {}

    /**
     * GET /experiment-templates/{templateId}/versions/{versionId}/terraform-modules
     *
     * Lists all Terraform modules for a template version (one per provider).
     */
    public function index(string $templateId, string $versionId): AnonymousResourceCollection
    {
        $modules = $this->getService->allForVersion($versionId);

        return TemplateTerraformModuleResource::collection($modules);
    }

    /**
     * GET /experiment-templates/{templateId}/versions/{versionId}/terraform-modules/{providerType}
     */
    public function show(string $templateId, string $versionId, string $providerType): JsonResponse
    {
        $module = $this->getService->handle($versionId, $providerType);

        if (!$module) {
            return response()->json(['message' => 'No Terraform module found for this version and provider.'], 404);
        }

        return response()->json(TemplateTerraformModuleResource::make($module));
    }

    /**
     * GET /experiment-templates/{templateId}/versions/{versionId}/terraform-module
     *
     * Returns whichever module exists for the version (any provider).
     */
    public function showByVersion(string $templateId, string $versionId): JsonResponse
    {
        $module = $this->getService->firstForVersion($versionId);

        if (!$module) {
            return response()->json(['message' => 'No Terraform module found for this version.'], 404);
        }

        return response()->json(TemplateTerraformModuleResource::make($module));
    }

    /**
     * PUT /experiment-templates/{templateId}/versions/{versionId}/terraform-modules/{providerType}
     *
     * Creates or fully replaces the Terraform module for a specific provider.
     */
    public function upsert(
        string $templateId,
        string $versionId,
        string $providerType,
        UpsertTemplateTerraformModuleRequest $request,
    ): JsonResponse {
        $module = $this->upsertService->handle($versionId, $providerType, $request->validated());

        $statusCode = $module->wasRecentlyCreated ? 201 : 200;

        return response()->json(new TemplateTerraformModuleResource($module), $statusCode);
    }

    /**
     * PUT /experiment-templates/{templateId}/versions/{versionId}/terraform-module
     *
     * Upserts a module, auto-detecting provider type when only slug is provided.
     */
    public function upsertByVersion(
        string $templateId,
        string $versionId,
        UpsertTemplateTerraformModuleRequest $request,
    ): JsonResponse {
        $data = $request->validated();

        $providerType = $request->input('provider_type');
        if (!$providerType) {
            $providerType = $data['module_slug'] ?? null;
            if ($providerType) {
                $providerType = $this->upsertService->detectProviderTypeFromSlug($providerType);
            }
        }

        if (!$providerType) {
            return response()->json(['message' => 'Provider type is required to upsert a module.'], 422);
        }

        // If a module already exists for this version, update it in place (status 200)
        $existing = $this->getService->firstForVersion($versionId);
        if ($existing) {
            $payload = $data;
            unset($payload['provider_type']);

            $existing->fill(array_merge($payload, [
                'provider_type'       => $providerType,
                'template_version_id' => $versionId,
            ]));
            $existing->save();

            return response()->json(TemplateTerraformModuleResource::make($existing), 200);
        }

        $module = $this->upsertService->handle($versionId, $providerType, $data);
        $statusCode = $module->wasRecentlyCreated ? 201 : 200;

        return response()->json(TemplateTerraformModuleResource::make($module), $statusCode);
    }
}
