<?php

namespace App\Http\Controllers;

use App\Enums\Messages;
use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Resources\RunLogResource;
use App\Http\Resources\TerraformRunResource;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\TerraformRun;
use App\Repositories\TerraformRunRepository;
use App\Services\EnvironmentAuthorizationService;
use App\Services\GetEnvironmentService;
use App\Services\ListRunLogsService;
use App\Services\ProjectAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TerraformRunController extends Controller
{
    public function __construct(
        private TerraformRunRepository          $runRepository,
        private GetEnvironmentService           $getEnvironment,
        private ProjectAuthorizationService     $projectAuth,
        private EnvironmentAuthorizationService $environmentAuth,
        private MessageDispatcherInterface      $dispatcher,
    ) {}

    /**
     * List all Terraform runs for an environment.
     *
     * GET /projects/{projectId}/environments/{environmentId}/terraform-runs
     */
    public function index(string $projectId, string $environmentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $runs = $this->runRepository->findByEnvironment($environmentId);

        return response()->json(TerraformRunResource::collection($runs));
    }

    /**
     * Get a single Terraform run (with logs).
     *
     * GET /projects/{projectId}/environments/{environmentId}/terraform-runs/{runId}
     */
    public function show(string $projectId, string $environmentId, string $runId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        /** @var TerraformRun|null $run */
        $run = $this->runRepository->find($runId);
        if (!$run || (string) $run->environment_id !== $environmentId) {
            return response()->json(['message' => 'Terraform run not found.'], 404);
        }

        // Attach logs automatically when showing a single run
        $request->merge(['with_logs' => true]);

        return response()->json(new TerraformRunResource($run));
    }

    /**
     * Manually trigger provisioning for an environment.
     *
     * POST /projects/{projectId}/environments/{environmentId}/terraform-runs
     */
    public function store(string $projectId, string $environmentId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $payload = ['environment_id' => (int) $environmentId];

        if ($request->filled('deployment_id')) {
            $payload['deployment_id'] = (int) $request->input('deployment_id');
        }

        $this->dispatcher->dispatch(Messages::PROVISION_ENVIRONMENT, $payload);

        return response()->json(['message' => 'Provisioning job queued.'], 202);
    }

    /**
     * Trigger infrastructure teardown for an environment.
     *
     * POST /projects/{projectId}/environments/{environmentId}/terraform-runs/destroy
     */
    public function destroy(string $projectId, string $environmentId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $payload = ['environment_id' => (int) $environmentId];

        if ($request->filled('deployment_id')) {
            $payload['deployment_id'] = (int) $request->input('deployment_id');
        }

        $this->dispatcher->dispatch(Messages::DESTROY_ENVIRONMENT, $payload);

        return response()->json(['message' => 'Destroy job queued.'], 202);
    }

    /**
     * List log lines for a specific Terraform run (supports incremental polling).
     *
     * GET /projects/{projectId}/environments/{environmentId}/terraform-runs/{runId}/logs
     * Query params:
     *   after_id (int, optional) — return only rows with id > after_id
     */
    public function logs(
        string $projectId,
        string $environmentId,
        string $runId,
        Request $request,
        ListRunLogsService $logsService,
    ) {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $run = $this->runRepository->find($runId);
        if (!$run || (string) $run->environment_id !== $environmentId) {
            return response()->json(['message' => 'Terraform run not found.'], 404);
        }

        $afterId = $request->integer('after_id', 0) ?: null;

        return RunLogResource::collection($logsService->handleByRun($runId, $afterId));
    }
}
