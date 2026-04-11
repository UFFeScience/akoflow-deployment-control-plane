<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;
use App\Models\EnvironmentTemplateProviderConfiguration;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Resolves the ordered list of AnsiblePlaybook records to execute for a given
 * provider configuration and trigger.
 *
 * Resolution follows two rules:
 *   1. Match by provider type: prefer a config whose applies_to_providers
 *      contains the runtime provider type; fall back to the default config
 *      (empty applies_to_providers).
 *   2. Within the matched trigger group, sort topologically by declared
 *      dependencies. Activities with no declared dependencies are ordered
 *      by their position column.
 *
 * The topological sort uses Kahn's algorithm (BFS). A cycle throws
 * RuntimeException — that is always a data-entry error.
 */
class AnsiblePlaybookResolverService
{
    /**
     * Return the playbooks that should run for the given provider + trigger,
     * sorted so every activity appears after all its dependencies.
     *
     * @param  int     $templateVersionId
     * @param  string  $providerType       e.g. 'GCP', 'AWS'
     * @param  string  $trigger            AnsiblePlaybook::TRIGGER_* constant
     * @return AnsiblePlaybook[]
     */
    public function resolve(int $templateVersionId, string $providerType, string $trigger): array
    {
        $config = $this->resolveProviderConfig($templateVersionId, $providerType);

        if ($config === null) {
            return [];
        }

        $playbooks = AnsiblePlaybook::with('dependencies')
            ->where('provider_configuration_id', $config->id)
            ->where('trigger', $trigger)
            ->where('enabled', true)
            ->orderBy('position')
            ->get();

        if ($playbooks->isEmpty()) {
            return [];
        }

        return $this->topologicalSort($playbooks);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Provider config resolution (mirrors AnsibleWorkspaceService logic)
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveProviderConfig(int $templateVersionId, string $providerType): ?EnvironmentTemplateProviderConfiguration
    {
        $configs = EnvironmentTemplateProviderConfiguration::where('template_version_id', $templateVersionId)->get();

        $upper = strtoupper($providerType);

        // Prefer exact match
        $match = $configs->first(function (EnvironmentTemplateProviderConfiguration $c) use ($upper) {
            return in_array($upper, array_map('strtoupper', $c->applies_to_providers ?? []), true);
        });

        if ($match) {
            return $match;
        }

        // Fall back to default config (no providers filter)
        return $configs->first(fn($c) => empty($c->applies_to_providers));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Kahn's topological sort (BFS)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  Collection<AnsiblePlaybook>  $playbooks
     * @return AnsiblePlaybook[]
     * @throws RuntimeException on circular dependency
     */
    private function topologicalSort(Collection $playbooks): array
    {
        $ids = $playbooks->pluck('id')->flip()->all(); // id => index (for membership check)

        // Build adjacency: dependsOn[A] = [B, C] means A depends on B and C
        $dependsOn = [];   // playbook_id => list of dependency ids (within this trigger group)
        $inDegree   = [];

        foreach ($playbooks as $activity) {
            $dependsOn[$activity->id] = [];
            $inDegree[$activity->id]  = 0;
        }

        foreach ($playbooks as $activity) {
            foreach ($activity->dependencies as $dep) {
                // Only count dependencies within this trigger group
                if (!array_key_exists($dep->id, $ids)) {
                    continue;
                }
                $dependsOn[$activity->id][] = $dep->id;
                $inDegree[$activity->id]++;
            }
        }

        // Start with playbooks that have no in-group dependencies, ordered by position
        $queue = $playbooks
            ->filter(fn($a) => $inDegree[$a->id] === 0)
            ->sortBy('position')
            ->values()
            ->all();

        $sorted = [];
        $map    = $playbooks->keyBy('id');

        while (!empty($queue)) {
            /** @var AnsiblePlaybook $current */
            $current  = array_shift($queue);
            $sorted[] = $current;

            // For each activity that depends on $current, reduce its in-degree
            foreach ($playbooks as $candidate) {
                if (!in_array($current->id, $dependsOn[$candidate->id], true)) {
                    continue;
                }

                $inDegree[$candidate->id]--;

                if ($inDegree[$candidate->id] === 0) {
                    $queue[] = $candidate;
                    // Keep queue ordered by position for deterministic output
                    usort($queue, fn($a, $b) => $a->position <=> $b->position);
                }
            }
        }

        if (count($sorted) !== $playbooks->count()) {
            throw new RuntimeException(
                'Circular dependency detected in AnsiblePlaybook trigger group "' .
                ($playbooks->first()?->trigger ?? '?') . '".'
            );
        }

        return $sorted;
    }
}
