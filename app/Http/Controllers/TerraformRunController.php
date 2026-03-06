<?php

namespace App\Http\Controllers;

use App\Exceptions\ExperimentNotFoundException;
use App\Http\Resources\TerraformRunResource;
use App\Jobs\DestroyExperimentJob;
use App\Jobs\ProvisionExperimentJob;
use App\Models\TerraformRun;
use App\Repositories\TerraformRunRepository;
use App\Services\ExperimentAuthorizationService;
use App\Services\GetExperimentService;
use App\Services\ProjectAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TerraformRunController extends Controller
{
    public function __construct(
        private TerraformRunRepository       $runRepository,
        private GetExperimentService         $getExperiment,
        private ProjectAuthorizationService  $projectAuth,
        private ExperimentAuthorizationService $experimentAuth,
    ) {}

    /**
     * List all Terraform runs for an experiment.
     *
     * GET /projects/{projectId}/experiments/{experimentId}/terraform-runs
     */
    public function index(string $projectId, string $experimentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $experiment = $this->getExperiment->handle($projectId, $experimentId);
        if (!$experiment) {
            throw new ExperimentNotFoundException();
        }

        $runs = $this->runRepository->findByExperiment($experimentId);

        return response()->json(TerraformRunResource::collection($runs));
    }

    /**
     * Get a single Terraform run (with logs).
     *
     * GET /projects/{projectId}/experiments/{experimentId}/terraform-runs/{runId}
     */
    public function show(string $projectId, string $experimentId, string $runId, Request $request): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $experiment = $this->getExperiment->handle($projectId, $experimentId);
        if (!$experiment) {
            throw new ExperimentNotFoundException();
        }

        /** @var TerraformRun|null $run */
        $run = $this->runRepository->find($runId);
        if (!$run || (string) $run->experiment_id !== $experimentId) {
            return response()->json(['message' => 'Terraform run not found.'], 404);
        }

        // Attach logs automatically when showing a single run
        $request->merge(['with_logs' => true]);

        return response()->json(new TerraformRunResource($run));
    }

    /**
     * Manually trigger provisioning for an experiment.
     *
     * POST /projects/{projectId}/experiments/{experimentId}/terraform-runs
     */
    public function store(string $projectId, string $experimentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $experiment = $this->getExperiment->handle($projectId, $experimentId);
        if (!$experiment) {
            throw new ExperimentNotFoundException();
        }

        ProvisionExperimentJob::dispatch((int) $experimentId);

        return response()->json(['message' => 'Provisioning job queued.'], 202);
    }

    /**
     * Trigger infrastructure teardown for an experiment.
     *
     * POST /projects/{projectId}/experiments/{experimentId}/terraform-runs/destroy
     */
    public function destroy(string $projectId, string $experimentId): JsonResponse
    {
        $this->projectAuth->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $experiment = $this->getExperiment->handle($projectId, $experimentId);
        if (!$experiment) {
            throw new ExperimentNotFoundException();
        }

        DestroyExperimentJob::dispatch((int) $experimentId);

        return response()->json(['message' => 'Destroy job queued.'], 202);
    }
}
