<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Environment;
use App\Models\Provider;
use RuntimeException;

/**
 * Resolves the cloud Provider that is responsible for an Environment,
 * following the Environment → Deployment → Provider chain.
 */
class EnvironmentDeploymentProviderService
{
    /**
     * @throws RuntimeException when the environment has no deployment or the deployment has no provider.
     */
    public function resolve(Environment $environment): Provider
    {
        $deployment = $environment->deployments()
            ->with(['providerCredentials'])
            ->first();

        if (!$deployment) {
            throw new RuntimeException(
                "Environment [{$environment->id}] has no deployment associated. " .
                'A deployment with a linked Provider is required before provisioning.'
            );
        }

        $pivot = $deployment->providerCredentials->first();

        if (!$pivot) {
            throw new RuntimeException(
                "Deployment [{$deployment->id}] has no Provider associated."
            );
        }

        $provider = Provider::find($pivot->provider_id);

        if (!$provider) {
            throw new RuntimeException(
                "Deployment [{$deployment->id}] has no Provider associated."
            );
        }

        return $provider;
    }

    /**
     * Resolves the Provider directly from a specific Deployment.
     *
     * Prefer this over resolve(Environment) whenever a concrete Deployment is
     * available (e.g. during provisioning or destruction of a given deployment)
     * to avoid accidentally resolving the wrong provider in multi-deployment
     * environments.
     *
     * @throws RuntimeException when the deployment has no provider pivot.
     */
    public function resolveFromDeployment(Deployment $deployment): Provider
    {
        $deployment->loadMissing('providerCredentials');

        $pivot = $deployment->providerCredentials->first();

        if (!$pivot) {
            throw new RuntimeException(
                "Deployment [{$deployment->id}] has no Provider associated."
            );
        }

        $provider = Provider::find($pivot->provider_id);

        if (!$provider) {
            throw new RuntimeException(
                "Provider [{$pivot->provider_id}] not found for Deployment [{$deployment->id}]."
            );
        }

        return $provider;
    }
}
