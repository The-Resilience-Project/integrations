<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class Enquiry
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $type,
        public readonly ?string $contactId = null,
        public readonly ?string $assigneeId = null,
    ) {
    }
}
