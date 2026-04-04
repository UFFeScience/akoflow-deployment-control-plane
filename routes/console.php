<?php

use App\Console\Commands\ProviderHealthCheckLoopCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run one sweep every minute via the Laravel scheduler (alternative to the long-running loop).
// Use `provider:health-check-loop` for a continuously-running process (e.g. Supervisor).
Schedule::command(ProviderHealthCheckLoopCommand::class, ['--interval=0'])
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
