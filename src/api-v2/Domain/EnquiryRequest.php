<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class EnquiryRequest
{
    public function __construct(
        public readonly string $contactEmail,
        public readonly string $contactFirstName,
        public readonly string $contactLastName,
        public readonly ?string $contactPhone = null,
        public readonly ?string $orgPhone = null,
        public readonly ?string $jobTitle = null,
        public readonly ?string $contactType = null,
        public readonly ?string $contactNewsletter = null,
        public readonly ?string $schoolAccountNo = null,
        public readonly ?string $schoolNameOther = null,
        public readonly bool $schoolNameOtherSelected = false,
        public readonly ?string $state = null,
        public readonly ?string $organisationSubType = null,
        public readonly ?int $numOfStudents = null,
        public readonly ?int $numOfEmployees = null,
        public readonly ?string $contactLeadSource = null,
        public readonly ?string $enquiry = null,
        public readonly ?int $participatingNumOfStudents = null,
        public readonly ?string $sourceForm = null,
    ) {
        if (trim($this->contactEmail) === '') {
            throw new \InvalidArgumentException('Contact email must not be empty');
        }
    }

    /**
     * Build an EnquiryRequest from raw form submission data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromFormData(array $data): self
    {
        return new self(
            contactEmail: (string) ($data['contact_email'] ?? ''),
            contactFirstName: (string) ($data['contact_first_name'] ?? ''),
            contactLastName: (string) ($data['contact_last_name'] ?? ''),
            contactPhone: !empty($data['contact_phone']) ? (string) $data['contact_phone'] : null,
            orgPhone: !empty($data['org_phone']) ? (string) $data['org_phone'] : null,
            jobTitle: !empty($data['job_title']) ? (string) $data['job_title'] : null,
            contactType: !empty($data['contact_type']) ? (string) $data['contact_type'] : null,
            contactNewsletter: !empty($data['contact_newsletter']) ? (string) $data['contact_newsletter'] : null,
            schoolAccountNo: !empty($data['school_account_no']) ? (string) $data['school_account_no'] : null,
            schoolNameOther: !empty($data['school_name_other']) ? (string) $data['school_name_other'] : null,
            schoolNameOtherSelected: !empty($data['school_name_other_selected']),
            state: !empty($data['state']) ? (string) $data['state'] : null,
            organisationSubType: !empty($data['organisation_sub_type']) ? (string) $data['organisation_sub_type'] : null,
            numOfStudents: !empty($data['num_of_students']) ? (int) $data['num_of_students'] : null,
            numOfEmployees: !empty($data['num_of_employees']) ? (int) $data['num_of_employees'] : null,
            contactLeadSource: !empty($data['contact_lead_source']) ? (string) $data['contact_lead_source'] : null,
            enquiry: !empty($data['enquiry']) ? (string) $data['enquiry'] : null,
            participatingNumOfStudents: !empty($data['participating_num_of_students']) ? (int) $data['participating_num_of_students'] : null,
            sourceForm: !empty($data['source_form']) ? (string) $data['source_form'] : null,
        );
    }

    /**
     * Convert to a Contact domain object.
     */
    public function toContact(): Contact
    {
        return new Contact(
            email: $this->contactEmail,
            firstName: $this->contactFirstName,
            lastName: $this->contactLastName,
            phone: $this->contactPhone,
            orgPhone: $this->orgPhone,
            jobTitle: $this->jobTitle,
            type: $this->contactType,
            newsletter: $this->contactNewsletter,
        );
    }

    /**
     * Convert to an Organisation domain object.
     */
    public function toOrganisation(): Organisation
    {
        return new Organisation(
            accountNo: $this->schoolAccountNo,
            name: $this->schoolNameOther,
            state: $this->state,
            subType: $this->organisationSubType,
            numStudents: $this->numOfStudents,
            numEmployees: $this->numOfEmployees,
            leadSource: $this->contactLeadSource,
        );
    }
}
