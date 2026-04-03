<?php

namespace App\Http\Controllers;

use App\Enums\Messages;
use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Resources\AnsibleRunResource;
use App\Http\Resources\RunLogResource;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\Deployment;
use App\Repositories\AnsibleRunRepository;
use App\Services\EnvironmentAuthorizationService;
use App\Services\GetEnvironmentService;
use App\Services\ListRunLogsService;
use App\Services\ProjectAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnsibleRunController extends Controller
{
    public function __construct(
        private AnsibleRunRepository            $runRepository,
        private GetEnvironmentService           $getEnvironment,
        private ProjectAuthorizationService     $projectAuth,
        private EnvironmentAuthorizationService $environmentAuth,
        private ListRunLogsService              $logsService,
        private MessageDispatcherInterface      $dispatcher,
    ) {}

    /**
     * Manually re-trigger the Configure Environment (Ansible) phase for a deployment.
     *
     * POST /projects/{projectId}/environments/{environmentId}/ansible-runs
     * Body: { deployment_id: int }
     */
    public function store(string $projectId, string $environmentId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $deploymentId = $request->integer('deployment_id');

        if (!$deploymentId) {
            // Fall back to the latest deployment for this environment
            $deployment = Deployment::where('environment_id', $environmentId)
                ->orderByDesc('created_at')
                ->first();

            $deploymentId = $deployment?->id;
        }

        if (!$deploymentId) {
            return response()->json(['message' => 'No deployment found for this environment.'], 422);
        }

        $this->dispatcher->dispatch(Messages::CONFIGURE_ENVIRONMENT, [
            'deployment_id' => (int) $deploymentId,
        ]);

        return response()->json(['message' => 'Configure job queued.'], 202);
    }

    /**
     * List all Ansible runs for an environment (via its deployments).
     *
     * GET /projects/{projectId}/environments/{environmentId}/ansible-runs
     */
    public function index(string $projectId, string $environmentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        // Collect ansible runs across all deployments of this environment
        $deploymentIds = $environment->deployments()->pluck('id');

        $runs = \App\Models\AnsibleRun::whereIn('deployment_id', $deploymentIds)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(AnsibleRunResource::collection($runs));
    }

    /**
     * GET /projects/{projectId}/environments/{environmentId}/ansible-runs/{runId}
     */
    public function show(string $projectId, string $environmentId, string $runId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $run = $this->runRepository->find($runId);
        if (!$run) {
            return response()->json(['message' => 'Ansible run not found.'], 404);
        }

        return response()->json(AnsibleRunResource::make($run));
    }

    /**
     * List log lines for a specific Ansible run (supports incremental polling).
     *
     * GET /projects/{projectId}/environments/{environmentId}/ansible-runs/{runId}/logs
     * Query params:
     *   after_id (int, optional) — return only rows with id > after_id
     */
    public function logs(string $projectId, string $environmentId, string $runId, Request $request)
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $run = $this->runRepository->find($runId);
        if (!$run) {
            return response()->json(['message' => 'Ansible run not found.'], 404);
        }

        $afterId = $request->integer('after_id') ?: null;
        $logs    = $this->logsService->handleByAnsibleRun($runId, $afterId);

        return RunLogResource::collection($logs);
    }
}
