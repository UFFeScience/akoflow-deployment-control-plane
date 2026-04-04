<?php

namespace App\Contracts;

interface HasRunLog
{
    /**
     * Append a single log line to the run's log stream.
     */
    public function appendLog(string $line): void;
}
