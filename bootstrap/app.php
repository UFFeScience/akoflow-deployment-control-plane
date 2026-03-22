<?php

use App\Exceptions\DeploymentNotFoundException;
use App\Exceptions\EnvironmentNotFoundException;
use App\Exceptions\InstanceNotFoundException;
use App\Exceptions\InvalidPasswordException;
use App\Exceptions\MemberAlreadyExistsException;
use App\Exceptions\OrganizationNotFoundException;
use App\Exceptions\ProjectNotFoundException;
use App\Exceptions\UnauthorizedEnvironmentAccessException;
use App\Exceptions\UnauthorizedOrganizationAccessException;
use App\Exceptions\UnauthorizedProjectAccessException;
use App\Exceptions\UserNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (OrganizationNotFoundException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (ProjectNotFoundException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (UserNotFoundException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (EnvironmentNotFoundException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (DeploymentNotFoundException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (InstanceNotFoundException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (InvalidPasswordException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 401);
        });

        $exceptions->render(function (MemberAlreadyExistsException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 409);
        });

        $exceptions->render(function (UnauthorizedOrganizationAccessException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 403);
        });

        $exceptions->render(function (UnauthorizedProjectAccessException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 403);
        });

        $exceptions->render(function (UnauthorizedEnvironmentAccessException $e, Request $request) {
            return response()->json(['error' => $e->getMessage()], 403);
        });
    })->create();
