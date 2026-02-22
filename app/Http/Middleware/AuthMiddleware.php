<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse([
                'message' => 'Authorization header missing or malformed'
            ], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return new JsonResponse([
                'message' => 'Invalid token'
            ], 401);
        }

        $user = $accessToken->tokenable;

        if (!$user) {
            return new JsonResponse([
                'message' => 'User not found'
            ], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}