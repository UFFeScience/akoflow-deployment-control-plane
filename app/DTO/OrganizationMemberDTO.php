<?php

namespace App\DTO;

class OrganizationMemberDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $organizationId,
        public readonly string $role,
        public readonly ?\DateTime $joinedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? 0,
            organizationId: $data['organization_id'] ?? 0,
            role: $data['role'] ?? 'member',
            joinedAt: isset($data['joined_at']) ? new \DateTime($data['joined_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'organization_id' => $this->organizationId,
            'role' => $this->role,
            'joined_at' => $this->joinedAt?->toIso8601String(),
        ];
    }
}
