<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class Organisation
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $accountNo = null,
        public readonly ?string $name = null,
        public readonly ?string $state = null,
        public readonly ?string $subType = null,
        public readonly ?int $numStudents = null,
        public readonly ?int $numEmployees = null,
        public readonly ?string $leadSource = null,
    ) {
    }

    /**
     * Build a school Organisation from raw form data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromFormData(array $data): self
    {
        return new self(
            accountNo: !empty($data['school_account_no']) ? (string) $data['school_account_no'] : null,
            name: !empty($data['school_name_other']) ? (string) $data['school_name_other'] : null,
            state: !empty($data['state']) ? (string) $data['state'] : null,
            subType: !empty($data['organisation_sub_type']) ? (string) $data['organisation_sub_type'] : null,
            numStudents: !empty($data['num_of_students']) ? (int) $data['num_of_students'] : null,
            numEmployees: !empty($data['num_of_employees']) ? (int) $data['num_of_employees'] : null,
            leadSource: !empty($data['contact_lead_source']) ? (string) $data['contact_lead_source'] : null,
        );
    }
}
