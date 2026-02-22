<?php

namespace App\Http\Controllers;

use Exception;
use App\Exceptions\OrganizationNotFoundException;
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
    ) {}

    public function create(CreateOrganizationRequest $request): JsonResponse
    {
        try {
            $organization = $this->createOrganizationService->execute(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Organization created successfully',
                'data' => new OrganizationResource($organization),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $organizations = $this->listOrganizationsService->execute($request->user());

            return response()->json([
                'message' => 'Organizations retrieved successfully',
                'data' => OrganizationResource::collection($organizations),
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getById(Request $request, int $organizationId): JsonResponse
    {
        try {
            $organization = $this->organizationRepository->findByIdWithAll($organizationId);

            if (!$organization) {
                throw new OrganizationNotFoundException();
            }

            return response()->json([
                'message' => 'Organization retrieved successfully',
                'data' => new OrganizationResource($organization),
            ]);
        } catch (OrganizationNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(UpdateOrganizationRequest $request, int $organizationId): JsonResponse
    {
        try {
            $organization = $this->organizationRepository->findById($organizationId);

            if (!$organization) {
                throw new OrganizationNotFoundException();
            }

            $updated = $this->updateOrganizationService->execute(
                $organization,
                $request->validated()
            );

            return response()->json([
                'message' => 'Organization updated successfully',
                'data' => new OrganizationResource($updated),
            ]);
        } catch (OrganizationNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(Request $request, int $organizationId): JsonResponse
    {
        try {
            $organization = $this->organizationRepository->findById($organizationId);

            if (!$organization) {
                throw new OrganizationNotFoundException();
            }

            $this->deleteOrganizationService->execute($organization);

            return response()->json([
                'message' => 'Organization deleted successfully',
            ]);
        } catch (OrganizationNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function addMember(AddOrganizationMemberRequest $request, int $organizationId): JsonResponse
    {
        try {
            $member = $this->addOrganizationMemberService->execute(
                $organizationId,
                $request->validated()
            );

            return response()->json([
                'message' => 'Member added successfully',
                'data' => new OrganizationMemberResource($member),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function removeMember(Request $request, int $organizationId, int $userId): JsonResponse
    {
        try {
            $this->removeOrganizationMemberService->execute($organizationId, $userId);

            return response()->json([
                'message' => 'Member removed successfully',
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateMemberRole(UpdateOrganizationMemberRoleRequest $request, int $organizationId, int $userId): JsonResponse
    {
        try {
            $member = $this->updateOrganizationMemberRoleService->execute(
                $organizationId,
                $userId,
                $request->validated('role')
            );

            return response()->json([
                'message' => 'Member role updated successfully',
                'data' => new OrganizationMemberResource($member),
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function listMembers(Request $request, int $organizationId): JsonResponse
    {
        try {
            $members = $this->listOrganizationMembersService->execute($organizationId);

            return response()->json([
                'message' => 'Members retrieved successfully',
                'data' => OrganizationMemberResource::collection($members),
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
