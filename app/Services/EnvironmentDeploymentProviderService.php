<?php

namespace App\Services;

use App\Models\Environment;
use App\Models\Provider;
use RuntimeException;

/**
 * Resolves the cloud Provider that is responsible for an Environment,
 * following the Environment → Deployment → Provider chain.
 */
class EnvironmentClusterProviderService
{
    /**
     * @throws RuntimeException when the environment has no deployment or the deployment has no provider.
     */
    public function resolve(Environment $environment): Provider
    {
        $deployment = $environment->deployments()->with('provider')->first();

        if (!$deployment) {
            throw new RuntimeException(
                "Environment [{$environment->id}] has no deployment associated. " .
                'A deployment with a linked Provider is required before provisioning.'
            );
        }

        if (!$deployment->provider) {
            throw new RuntimeException(
                "Deployment [{$deployment->id}] has no Provider associated."
            );
        }

        return $deployment->provider;
    }
}
