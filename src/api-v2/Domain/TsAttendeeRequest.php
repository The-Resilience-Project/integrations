<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class TsAttendeeRequest
{
    public function __construct(
        // Contact fields (required)
        public readonly string $contactEmail,
        public readonly string $contactFirstName,
        public readonly string $contactLastName,

        // Organisation fields (required)
        public readonly string $schoolName,
        public readonly string $state,

        // Optional enrichment from prepare_ts_attendee
        public readonly ?int $numOfStudents = null,

        // Optional contact extras
        public readonly ?string $contactPhone = null,
        public readonly ?string $jobTitle = null,
    ) {
        if (trim($this->contactEmail) === '') {
            throw new \InvalidArgumentException('Contact email must not be empty');
        }
        if (trim($this->schoolName) === '') {
            throw new \InvalidArgumentException('School name must not be empty');
        }
        if (trim($this->state) === '') {
            throw new \InvalidArgumentException('State must not be empty');
        }
    }

    /**
     * Build a TsAttendeeRequest from raw form submission data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromFormData(array $data): self
    {
        return new self(
            contactEmail: (string) ($data['contact_email'] ?? ''),
            contactFirstName: (string) ($data['contact_first_name'] ?? ''),
            contactLastName: (string) ($data['contact_last_name'] ?? ''),
            schoolName: (string) ($data['school_name'] ?? ''),
            state: (string) ($data['state'] ?? ''),
            numOfStudents: !empty($data['num_of_students']) ? (int) $data['num_of_students'] : null,
            contactPhone: !empty($data['contact_phone']) ? (string) $data['contact_phone'] : null,
            jobTitle: !empty($data['job_title']) ? (string) $data['job_title'] : null,
        );
    }

    public function toContact(): Contact
    {
        return new Contact(
            email: $this->contactEmail,
            firstName: $this->contactFirstName,
            lastName: $this->contactLastName,
            phone: $this->contactPhone,
            jobTitle: $this->jobTitle,
        );
    }

    public function toOrganisation(): Organisation
    {
        return new Organisation(
            name: $this->schoolName,
            state: $this->state,
            numStudents: $this->numOfStudents,
        );
    }
}
