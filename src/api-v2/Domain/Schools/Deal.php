<?php

declare(strict_types=1);

namespace ApiV2\Domain\Schools;

class Deal
{
    public const SCHOOL_DEAL_NAME = '2027 School Partnership Program';

    public function __construct(
        public readonly string $name,
        public readonly string $type = 'School',
        public readonly string $orgType = 'School - New',
        public readonly ?string $stage = null,
        public readonly ?string $closeDate = null,
        public readonly ?string $state = null,
        public readonly ?string $pipeline = null,
        public readonly ?string $inCampaignRating = null,
    ) {
    }

    public static function forSchoolEnquiry(): self
    {
        return new self(
            name: self::SCHOOL_DEAL_NAME,
            stage: 'New',
            closeDate: date('d/m/Y', strtotime('+1 Week')),
            pipeline: 'New Schools',
        );
    }

    public static function forSchoolRegistration(string $closeDate): self
    {
        return new self(
            name: self::SCHOOL_DEAL_NAME,
            stage: 'In Campaign',
            closeDate: $closeDate,
            pipeline: 'New Schools',
            inCampaignRating: 'Hot',
        );
    }
}
