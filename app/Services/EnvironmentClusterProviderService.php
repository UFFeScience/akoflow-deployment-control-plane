<?php

namespace App\Services;

use App\Models\Environment;
use App\Models\Provider;
use RuntimeException;

/**
 * Resolves the cloud Provider that is responsible for an Environment,
 * following the Environment → Cluster → Provider chain.
 */
class EnvironmentClusterProviderService
{
    /**
     * @throws RuntimeException when the environment has no cluster or the cluster has no provider.
     */
    public function resolve(Environment $environment): Provider
    {
        $cluster = $environment->clusters()->with('provider')->first();

        if (!$cluster) {
            throw new RuntimeException(
                "Environment [{$environment->id}] has no cluster associated. " .
                'A cluster with a linked Provider is required before provisioning.'
            );
        }

        if (!$cluster->provider) {
            throw new RuntimeException(
                "Cluster [{$cluster->id}] has no Provider associated."
            );
        }

        return $cluster->provider;
    }
}
