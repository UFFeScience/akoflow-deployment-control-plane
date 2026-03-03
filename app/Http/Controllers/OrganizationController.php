<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddOrganizationMemberRequest;
use App\Http\Requests\CreateOrganizationRequest;
use App\Http\Requests\UpdateOrganizationMemberRoleRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Http\Resources\OrganizationResource;
use App\Repositories\OrganizationRepository;
use App\Services\AddOrganizationMemberService;
use App\Services\CreateOrganizationService;
use App\Services\DeleteOrganizationService;
use App\Services\ListOrganizationMembersService;
use App\Services\ListOrganizationsService;
use App\Services\OrganizationAuthorizationService;
use App\Services\RemoveOrganizationMemberService;
use App\Services\UpdateOrganizationMemberRoleService;
use App\Services\UpdateOrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private CreateOrganizationService $createOrganizationService,
        private UpdateOrganizationService $updateOrganizationService,
        private DeleteOrganizationService $deleteOrganizationService,
        private ListOrganizationsService $listOrganizationsService,
        private AddOrganizationMemberService $addOrganizationMemberService,
        private RemoveOrganizationMemberService $removeOrganizationMemberService,
        private UpdateOrganizationMemberRoleService $updateOrganizationMemberRoleService,
        private ListOrganizationMembersService $listOrganizationMembersService,
        private OrganizationRepository $organizationRepository,
        private OrganizationAuthorizationService $organizationAuthorizationService,
    ) {}

    public function create(CreateOrganizationRequest $request): JsonResponse
    {
        $organization = $this->createOrganizationService->execute(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Organization created successfully',
            'data' => new OrganizationResource($organization),
        ], 201);
    }

    public function list(Request $request): JsonResponse
    {
        $organizations = $this->listOrganizationsService->execute($request->user());

        return response()->json([
            'message' => 'Organizations retrieved successfully',
            'data' => OrganizationResource::collection($organizations),
        ]);
    }

    public function getById(Request $request, int $organizationId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $organization = $this->organizationRepository->findByIdWithAll($organizationId);

        return response()->json([
            'message' => 'Organization retrieved successfully',
            'data' => new OrganizationResource($organization),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, int $organizationId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $organization = $this->organizationRepository->findById($organizationId);

        $updated = $this->updateOrganizationService->execute($organization, $request->validated());

        return response()->json([
            'message' => 'Organization updated successfully',
            'data' => new OrganizationResource($updated),
        ]);
    }

    public function delete(Request $request, int $organizationId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $organization = $this->organizationRepository->findById($organizationId);

        $this->deleteOrganizationService->execute($organization);

        return response()->json([
            'message' => 'Organization deleted successfully',
        ]);
    }

    public function addMember(AddOrganizationMemberRequest $request, int $organizationId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $member = $this->addOrganizationMemberService->execute(
            $organizationId,
            $request->validated()
        );

        return response()->json([
            'message' => 'Member added successfully',
            'data' => new OrganizationMemberResource($member),
        ], 201);
    }

    public function removeMember(Request $request, int $organizationId, int $userId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $this->removeOrganizationMemberService->execute($organizationId, $userId);

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }

    public function updateMemberRole(UpdateOrganizationMemberRoleRequest $request, int $organizationId, int $userId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $member = $this->updateOrganizationMemberRoleService->execute(
            $organizationId,
            $userId,
            $request->validated('role')
        );

        return response()->json([
            'message' => 'Member role updated successfully',
            'data' => new OrganizationMemberResource($member),
        ]);
    }

    public function listMembers(Request $request, int $organizationId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $members = $this->listOrganizationMembersService->execute($organizationId);

        return response()->json([
            'message' => 'Members retrieved successfully',
            'data' => OrganizationMemberResource::collection($members),
        ]);
    }
}

