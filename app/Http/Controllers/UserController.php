<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPasswordException;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\ChangePasswordService;
use App\Services\DeleteCurrentUserService;
use App\Services\GetCurrentUserService;
use App\Services\UpdateCurrentUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private GetCurrentUserService $getCurrentUserService,
        private UpdateCurrentUserService $updateCurrentUserService,
        private DeleteCurrentUserService $deleteCurrentUserService,
        private ChangePasswordService $changePasswordService,
    ) {}

    public function getCurrentUser(Request $request): JsonResponse
    {
        $user = $this->getCurrentUserService->execute($request->user());

        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function updateCurrentUser(UpdateUserRequest $request): JsonResponse
    {
        $user = $this->updateCurrentUserService->execute(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function deleteCurrentUser(Request $request): JsonResponse
    {
        $this->deleteCurrentUserService->execute($request->user());

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $success = $this->changePasswordService->execute(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('new_password')
        );

        if (!$success) {
            throw new InvalidPasswordException('Current password is incorrect');
        }

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }
}
