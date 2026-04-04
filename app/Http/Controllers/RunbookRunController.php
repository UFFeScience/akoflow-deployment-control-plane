<?php

namespace App\Http\Controllers;

use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Resources\RunbookRunResource;
use App\Services\EnvironmentAuthorizationService;
use App\Services\GetEnvironmentService;
use App\Services\GetRunbookRunService;
use App\Services\ListRunbookRunsByDeploymentService;
use App\Services\ListRunbookRunsService;
use App\Services\ProjectAuthorizationService;
use App\Services\TriggerRunbookRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RunbookRunController extends Controller
{
    public function __construct(
        private ListRunbookRunsService            $listRuns,
        private ListRunbookRunsByDeploymentService $listRunsByDeployment,
        private TriggerRunbookRunService          $triggerRun,
        private GetRunbookRunService              $getRun,
        private GetEnvironmentService             $getEnvironment,
        private ProjectAuthorizationService       $projectAuth,
        private EnvironmentAuthorizationService   $environmentAuth,
    ) {}

    public function index(string $projectId, string $environmentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $runs = $this->listRuns->handle($environmentId);

        return response()->json(RunbookRunResource::collection($runs));
    }

    public function indexByDeployment(string $projectId, string $environmentId, string $deploymentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $runs = $this->listRunsByDeployment->handle($deploymentId);

        return response()->json(RunbookRunResource::collection($runs));
    }

    public function store(string $projectId, string $environmentId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $validated = $request->validate([
            'runbook_id'    => 'required|integer|exists:environment_template_runbooks,id',
            'deployment_id' => 'nullable|integer|exists:deployments,id',
        ]);

        $result = $this->triggerRun->handle(
            (int) $validated['runbook_id'],
            $environmentId,
            isset($validated['deployment_id']) ? (int) $validated['deployment_id'] : null,
            (string) auth()->id(),
        );

        if (!$result['deployment_found']) {
            return response()->json(['message' => 'No deployment found for this environment.'], 422);
        }

        return response()->json([
            'message'       => 'Runbook execution queued.',
            'runbook_name'  => $result['runbook_name'],
            'deployment_id' => $result['deployment_id'],
        ], 202);
    }

    public function show(string $projectId, string $environmentId, string $runId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $run = $this->getRun->handle($runId);

        return response()->json(new RunbookRunResource($run));
    }
}
