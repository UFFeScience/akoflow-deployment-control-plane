<?php

namespace App\Http\Controllers;

use App\Enums\Messages;
use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Resources\TerraformRunResource;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\TerraformRun;
use App\Repositories\TerraformRunRepository;
use App\Services\EnvironmentAuthorizationService;
use App\Services\GetEnvironmentService;
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
    public function store(string $projectId, string $environmentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $this->dispatcher->dispatch(Messages::PROVISION_ENVIRONMENT, [
            'environment_id' => (int) $environmentId,
        ]);

        return response()->json(['message' => 'Provisioning job queued.'], 202);
    }

    /**
     * Trigger infrastructure teardown for an environment.
     *
     * POST /projects/{projectId}/environments/{environmentId}/terraform-runs/destroy
     */
    public function destroy(string $projectId, string $environmentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $environment = $this->getEnvironment->handle($projectId, $environmentId);
        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $this->dispatcher->dispatch(Messages::DESTROY_ENVIRONMENT, [
            'environment_id' => (int) $environmentId,
        ]);

        return response()->json(['message' => 'Destroy job queued.'], 202);
    }
}
