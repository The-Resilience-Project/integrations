<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class OrganisationDetails
{
    public function __construct(
        public readonly string $organisationId,
        public readonly string $name,
        public readonly string $assignedUserId,
        public readonly string $salesEvents2025,
        public readonly string $confirmationStatus2024,
        public readonly string $confirmationStatus2025,
        public readonly string $confirmationStatus2026,
    ) {
    }

    public function withAssignedUserId(string $assignedUserId): self
    {
        return new self(
            organisationId: $this->organisationId,
            name: $this->name,
            assignedUserId: $assignedUserId,
            salesEvents2025: $this->salesEvents2025,
            confirmationStatus2024: $this->confirmationStatus2024,
            confirmationStatus2025: $this->confirmationStatus2025,
            confirmationStatus2026: $this->confirmationStatus2026,
        );
    }
}
