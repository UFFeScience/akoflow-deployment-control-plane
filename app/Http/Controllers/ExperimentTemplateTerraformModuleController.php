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

        return response()->json(new TemplateTerraformModuleResource($module));
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
}
