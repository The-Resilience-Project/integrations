<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class CapturedContact
{
    public function __construct(
        public readonly string $contactId,
        public readonly string $organisationId,
        public readonly string $assignedUserId,
        public readonly string $formsCompleted,
    ) {
    }
}
