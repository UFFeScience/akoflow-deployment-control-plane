<?php

namespace App\Messaging;

use App\Enums\Messages;
use App\Jobs\CheckProviderHealthJob;
use App\Jobs\ConfigureEnvironmentJob;
use App\Jobs\DestroyEnvironmentJob;
use App\Jobs\ExecuteRunbookJob;
use App\Jobs\ProvisionEnvironmentJob;
use App\Messaging\Contracts\MessageDispatcherInterface;
use InvalidArgumentException;

class LaravelJobMessageDispatcher implements MessageDispatcherInterface
{
    /**
     * Maps each Messages case to its Laravel Job class.
     *
     * The full payload is always serialized to JSON and passed as the single
     * constructor argument of the job. To add a new message:
     *   1. Add a case to App\Enums\Messages.
     *   2. Add an entry here: Messages::YOUR_CASE->value => YourJob::class.
     *   3. No other file needs to change.
     *
     * @var array<string, class-string>
     */
    private array $map = [
        Messages::PROVISION_ENVIRONMENT->value  => ProvisionEnvironmentJob::class,
        Messages::CONFIGURE_ENVIRONMENT->value  => ConfigureEnvironmentJob::class,
        Messages::DESTROY_ENVIRONMENT->value    => DestroyEnvironmentJob::class,
        Messages::DESTROY_DEPLOYMENT->value     => DestroyEnvironmentJob::class,
        Messages::CHECK_PROVIDER_HEALTH->value  => CheckProviderHealthJob::class,
        Messages::EXECUTE_RUNBOOK->value        => ExecuteRunbookJob::class,
    ];

    public function dispatch(Messages $message, array $payload = []): void
    {
        $jobClass = $this->map[$message->value] ?? null;

        if ($jobClass === null) {
            throw new InvalidArgumentException(
                "No job is mapped for message [{$message->value}]."
            );
        }

        $jobClass::dispatch(json_encode($payload));
    }
}
