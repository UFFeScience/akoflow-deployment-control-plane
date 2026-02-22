<?php

namespace App\Http\Controllers;

use Exception;
use App\Exceptions\InvalidPasswordException;
use App\Exceptions\UserNotFoundException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\LoginUserService;
use App\Services\LogoutService;
use App\Services\RefreshTokenService;
use App\Services\RegisterUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private RegisterUserService $registerUserService,
        private LoginUserService $loginUserService,
        private RefreshTokenService $refreshTokenService,
        private LogoutService $logoutService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->registerUserService->execute($request->validated());

            return response()->json([
                'message' => 'User registered successfully',
                'user' => new UserResource($user),
                'token' => $user->createToken('api-token')->plainTextToken,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginUserService->execute(
                $request->validated('email'),
                $request->validated('password')
            );

            return response()->json([
                'message' => 'Login successful',
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ]);
        } catch (UserNotFoundException|InvalidPasswordException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $result = $this->refreshTokenService->execute($request->user());

            return response()->json([
                'message' => 'Token refreshed successfully',
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->logoutService->execute($request->user());

            return response()->json([
                'message' => 'Logout successful',
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function lost(Request $request): JsonResponse
    {
        // This is a placeholder for the password reset functionality
        return response()->json([
            'message' => 'Lost Session'
        ], 400);
    }
}
