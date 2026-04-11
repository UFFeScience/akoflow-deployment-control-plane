<?php

namespace App\Http\Controllers;

use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Resources\AnsiblePlaybookRunResource;
use App\Http\Resources\RunLogResource;
use App\Services\EnvironmentAuthorizationService;
use App\Services\GetAnsiblePlaybookRunService;
use App\Services\GetEnvironmentService;
use App\Services\ListAnsiblePlaybookRunsService;
use App\Services\ListRunLogsService;
use App\Services\ProjectAuthorizationService;
use App\Services\TriggerAnsiblePlaybookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnsiblePlaybookRunController extends Controller
{
    public function __construct(
        private ListAnsiblePlaybookRunsService  $listRuns,
        private TriggerAnsiblePlaybookService   $triggerRun,
        private GetAnsiblePlaybookRunService    $getRun,
        private ListRunLogsService              $listLogs,
        private GetEnvironmentService           $getEnvironment,
        private ProjectAuthorizationService     $projectAuth,
        private EnvironmentAuthorizationService $environmentAuth,
    ) {}

    public function index(string $projectId, string $environmentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $runs = $this->listRuns->handleByEnvironment($environmentId);

        return response()->json(AnsiblePlaybookRunResource::collection($runs));
    }

    public function indexByDeployment(string $projectId, string $environmentId, string $deploymentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $runs = $this->listRuns->handleByDeployment($deploymentId);

        return response()->json(AnsiblePlaybookRunResource::collection($runs));
    }

    public function store(string $projectId, string $environmentId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $validated = $request->validate([
            'playbook_id'   => 'required|integer|exists:ansible_playbooks,id',
            'deployment_id' => 'nullable|integer|exists:deployments,id',
        ]);

        $result = $this->triggerRun->handle(
            (int) $validated['playbook_id'],
            $environmentId,
            isset($validated['deployment_id']) ? (int) $validated['deployment_id'] : null,
            (string) auth()->id(),
        );

        if (!$result['deployment_found']) {
            return response()->json(['message' => 'No deployment found for this environment.'], 422);
        }

        return response()->json(new AnsiblePlaybookRunResource($result['run']), 202);
    }

    public function show(string $projectId, string $environmentId, string $runId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $run = $this->getRun->handle($runId);

        return response()->json(new AnsiblePlaybookRunResource($run));
    }

    public function logs(string $projectId, string $environmentId, string $runId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $afterId = $request->query('after_id') ? (int) $request->query('after_id') : null;
        $logs    = $this->listLogs->handleByPlaybookRun($runId, $afterId);

        return response()->json(RunLogResource::collection($logs));
    }
}
