<?php

namespace App\Http\Controllers;

use App\Http\Resources\TemplateAnsiblePlaybookResource;
use App\Services\GetTemplateAnsiblePlaybookService;
use App\Services\UpsertTemplateAnsiblePlaybookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnvironmentTemplateAnsiblePlaybookController extends Controller
{
    public function __construct(
        private UpsertTemplateAnsiblePlaybookService $upsertService,
        private GetTemplateAnsiblePlaybookService    $getService,
    ) {}

    /**
     * GET /environment-templates/{templateId}/versions/{versionId}/ansible-playbooks
     */
    public function index(string $templateId, string $versionId): AnonymousResourceCollection
    {
        $playbooks = $this->getService->allForVersion($versionId);

        return TemplateAnsiblePlaybookResource::collection($playbooks);
    }

    /**
     * GET /environment-templates/{templateId}/versions/{versionId}/ansible-playbooks/{providerType}
     */
    public function show(string $templateId, string $versionId, string $providerType): JsonResponse
    {
        $playbook = $this->getService->handle($versionId, $providerType);

        if (!$playbook) {
            return response()->json(['message' => 'No Ansible playbook found for this version and provider.'], 404);
        }

        return response()->json(TemplateAnsiblePlaybookResource::make($playbook));
    }

    /**
     * PUT /environment-templates/{templateId}/versions/{versionId}/ansible-playbooks/{providerType}
     */
    public function upsert(string $templateId, string $versionId, string $providerType, Request $request): JsonResponse
    {
        $playbook = $this->upsertService->handle($versionId, $providerType, $request->all());

        return response()->json(TemplateAnsiblePlaybookResource::make($playbook), 200);
    }
}
