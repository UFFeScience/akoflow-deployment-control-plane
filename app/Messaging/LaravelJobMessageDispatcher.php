<?php

namespace App\Messaging;

use App\Enums\Messages;
use App\Jobs\CheckProviderHealthJob;
use App\Jobs\DestroyEnvironmentJob;
use App\Jobs\ProvisionEnvironmentJob;
use App\Messaging\Contracts\MessageDispatcherInterface;
use InvalidArgumentException;

class LaravelJobMessageDispatcher implements MessageDispatcherInterface
{
    /**
     * Maps each Messages case to its Laravel Job class and the ordered list of
     * payload keys that will be passed as constructor arguments.
     *
     * To add a new message:
     *   1. Add a case to App\Enums\Messages.
     *   2. Add an entry here: Messages::YOUR_CASE->value => [YourJob::class, ['your_payload_key']].
     *   3. No other file needs to change.
     *
     * @var array<string, array{0: class-string, 1: list<string>}>
     */
    private array $map = [
        Messages::PROVISION_ENVIRONMENT->value => [ProvisionEnvironmentJob::class, ['environment_id']],
        Messages::DESTROY_ENVIRONMENT->value   => [DestroyEnvironmentJob::class,   ['environment_id']],
        Messages::CHECK_PROVIDER_HEALTH->value => [CheckProviderHealthJob::class,  ['provider_id']],
    ];

    public function dispatch(Messages $message, array $payload = []): void
    {
        $config = $this->map[$message->value] ?? null;

        if ($config === null) {
            throw new InvalidArgumentException(
                "No job is mapped for message [{$message->value}]."
            );
        }

        [$jobClass, $keys] = $config;

        $args = array_map(
            fn (string $key) => $payload[$key] ?? throw new InvalidArgumentException(
                "Missing required payload key [{$key}] for message [{$message->value}]."
            ),
            $keys
        );

        $jobClass::dispatch(...$args);
    }
}
