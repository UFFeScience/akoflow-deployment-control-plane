<?php

namespace App\Messaging\Contracts;

use App\Enums\Messages;

interface MessageDispatcherInterface
{
    /**
     * Dispatch a message by name, carrying an arbitrary payload.
     *
     * @param  Messages  $message  The message type to dispatch.
     * @param  array     $payload  Key/value pairs consumed by the underlying handler.
     */
    public function dispatch(Messages $message, array $payload = []): void;
}
