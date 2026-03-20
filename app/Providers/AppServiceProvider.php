<?php

namespace App\Providers;

use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Messaging\LaravelJobMessageDispatcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MessageDispatcherInterface::class, LaravelJobMessageDispatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
