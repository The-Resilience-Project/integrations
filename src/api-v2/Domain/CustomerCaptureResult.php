<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class CustomerCaptureResult
{
    public function __construct(
        public readonly CapturedContact $captured,
        public readonly OrganisationDetails $orgDetails,
    ) {
    }
}
