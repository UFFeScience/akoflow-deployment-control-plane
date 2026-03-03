<?php

namespace App\Services;

use App\Exceptions\ExperimentNotFoundException;
use App\Models\Experiment;
use App\Models\User;
use App\Repositories\ExperimentRepository;

class ExperimentAuthorizationService
{
    public function __construct(
        private ExperimentRepository $experimentRepository,
        private OrganizationAuthorizationService $organizationAuthorizationService,
    ) {}

    /**
     * Fetch an experiment by its id and assert the user belongs to the
     * experiment's project's organization.
     *
     * @throws ExperimentNotFoundException
     * @throws \App\Exceptions\OrganizationNotFoundException
     * @throws \App\Exceptions\UnauthorizedOrganizationAccessException
     */
    public function assertUserCanAccessExperiment(User $user, string $experimentId): Experiment
    {
        /** @var Experiment|null $experiment */
        $experiment = $this->experimentRepository->find($experimentId);

        if (!$experiment) {
            throw new ExperimentNotFoundException();
        }

        $project = $experiment->project()->first();

        $this->organizationAuthorizationService->assertUserBelongsToOrganization($user, $project->organization_id);

        return $experiment;
    }
}
