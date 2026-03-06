<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertTemplateTerraformModuleRequest;
use App\Http\Resources\TemplateTerraformModuleResource;
use App\Services\GetTemplateTerraformModuleService;
use App\Services\UpsertTemplateTerraformModuleService;
use Illuminate\Http\JsonResponse;

class ExperimentTemplateTerraformModuleController extends Controller
{
    public function __construct(
        private UpsertTemplateTerraformModuleService $upsertService,
        private GetTemplateTerraformModuleService    $getService,
    ) {}

    /**
     * GET /experiment-templates/{templateId}/versions/{versionId}/terraform-module
     *
     * Returns the Terraform module associated with a template version.
     */
    public function show(string $templateId, string $versionId): JsonResponse
    {
        $module = $this->getService->handle($versionId);

        if (!$module) {
            return response()->json(['message' => 'No Terraform module found for this version.'], 404);
        }

        return response()->json(new TemplateTerraformModuleResource($module));
    }

    /**
     * PUT /experiment-templates/{templateId}/versions/{versionId}/terraform-module
     *
     * Creates or fully replaces the Terraform module for a template version.
     * Accepts built-in module_slug and/or custom HCL files + tfvars_mapping_json.
     */
    public function upsert(
        string $templateId,
        string $versionId,
        UpsertTemplateTerraformModuleRequest $request,
    ): JsonResponse {
        $module = $this->upsertService->handle($versionId, $request->validated());

        $statusCode = $module->wasRecentlyCreated ? 201 : 200;

        return response()->json(new TemplateTerraformModuleResource($module), $statusCode);
    }
}
