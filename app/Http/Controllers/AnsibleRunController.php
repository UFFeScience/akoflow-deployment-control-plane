<?php

namespace App\Http\Controllers;

use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Resources\AnsibleRunResource;
use App\Http\Resources\RunLogResource;
use App\Services\EnvironmentAuthorizationService;
use App\Services\GetAnsibleRunService;
use App\Services\GetEnvironmentService;
use App\Services\ListAnsibleRunsService;
use App\Services\ListRunLogsService;
use App\Services\ProjectAuthorizationService;
use App\Services\TriggerAnsibleRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnsibleRunController extends Controller
{
    public function __construct(
        private TriggerAnsibleRunService        $triggerRun,
        private ListAnsibleRunsService          $listRuns,
        private GetAnsibleRunService            $getRun,
        private ListRunLogsService              $listLogs,
        private GetEnvironmentService           $getEnvironment,
        private ProjectAuthorizationService     $projectAuth,
        private EnvironmentAuthorizationService $environmentAuth,
    ) {}

    public function store(string $projectId, string $environmentId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $result = $this->triggerRun->handle($environmentId, $request->integer('deployment_id') ?: null);

        if (!$result['deployment_found']) {
            return response()->json(['message' => 'No deployment found for this environment.'], 422);
        }

        return response()->json(['message' => 'Configure job queued.'], 202);
    }

    public function index(string $projectId, string $environmentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $runs = $this->listRuns->handle($environmentId);

        return response()->json(AnsibleRunResource::collection($runs));
    }

    public function show(string $projectId, string $environmentId, string $runId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $run = $this->getRun->handle($runId);
        if (!$run) {
            return response()->json(['message' => 'Ansible run not found.'], 404);
        }

        return response()->json(AnsibleRunResource::make($run));
    }

    public function logs(string $projectId, string $environmentId, string $runId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $run = $this->getRun->handle($runId);
        if (!$run) {
            return response()->json(['message' => 'Ansible run not found.'], 404);
        }

        $afterId = $request->integer('after_id') ?: null;
        $logs    = $this->listLogs->handleByAnsibleRun($runId, $afterId);

        return response()->json(RunLogResource::collection($logs));
    }
}
