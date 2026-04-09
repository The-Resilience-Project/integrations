<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class ConfirmationRequest
{
    public function __construct(
        // Contact (required)
        public readonly string $contactEmail,
        public readonly string $contactFirstName,
        public readonly string $contactLastName,

        // Address (required)
        public readonly string $state,
        public readonly string $address,
        public readonly string $suburb,
        public readonly string $postcode,

        // Contact (optional)
        public readonly ?string $contactPhone = null,
        public readonly ?string $orgPhone = null,
        public readonly ?string $jobTitle = null,
        public readonly ?string $contactType = null,
        public readonly ?string $contactNewsletter = null,

        // Organisation (optional)
        public readonly ?string $schoolAccountNo = null,
        public readonly ?string $schoolNameOther = null,
        public readonly bool $schoolNameOtherSelected = false,
        public readonly ?string $organisationSubType = null,
        public readonly ?string $contactLeadSource = null,

        // Participation
        public readonly ?int $participatingNumOfStudents = null,
        public readonly ?int $numOfStudents = null,

        // Billing
        public readonly bool $differentBillingContact = false,
        public readonly ?string $billingContactEmail = null,
        public readonly ?string $billingContactFirstName = null,
        public readonly ?string $billingContactLastName = null,
        public readonly ?string $billingContactPhone = null,

        // Programs
        public readonly ?string $engage = null,
        public readonly ?string $inspire = null,
        public readonly ?string $mentalHealthFunding = null,
        public readonly ?array $selectedYearLevels = null,

        // Source form
        public readonly ?string $sourceForm = null,
    ) {
        if (trim($this->contactEmail) === '') {
            throw new \InvalidArgumentException('Contact email must not be empty');
        }
    }

    /**
     * Build a ConfirmationRequest from raw form submission data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromFormData(array $data): self
    {
        $participatingNumOfStudents = !empty($data['participating_num_of_students'])
            ? (int) $data['participating_num_of_students']
            : null;

        // If participating_num_of_students is not provided but journal + planner are, calculate sum
        if ($participatingNumOfStudents === null) {
            $journal = !empty($data['journal']) ? (int) $data['journal'] : 0;
            $planner = !empty($data['planner']) ? (int) $data['planner'] : 0;
            if ($journal > 0 || $planner > 0) {
                $participatingNumOfStudents = $journal + $planner;
            }
        }

        return new self(
            contactEmail: (string) ($data['contact_email'] ?? ''),
            contactFirstName: (string) ($data['contact_first_name'] ?? ''),
            contactLastName: (string) ($data['contact_last_name'] ?? ''),
            state: (string) ($data['state'] ?? ''),
            address: (string) ($data['address'] ?? ''),
            suburb: (string) ($data['suburb'] ?? ''),
            postcode: (string) ($data['postcode'] ?? ''),
            contactPhone: !empty($data['contact_phone']) ? (string) $data['contact_phone'] : null,
            orgPhone: !empty($data['org_phone']) ? (string) $data['org_phone'] : null,
            jobTitle: !empty($data['job_title']) ? (string) $data['job_title'] : null,
            contactType: !empty($data['contact_type']) ? (string) $data['contact_type'] : null,
            contactNewsletter: !empty($data['contact_newsletter']) ? (string) $data['contact_newsletter'] : null,
            schoolAccountNo: !empty($data['school_account_no']) ? (string) $data['school_account_no'] : null,
            schoolNameOther: !empty($data['school_name_other']) ? (string) $data['school_name_other'] : null,
            schoolNameOtherSelected: !empty($data['school_name_other_selected']),
            organisationSubType: !empty($data['organisation_sub_type']) ? (string) $data['organisation_sub_type'] : null,
            contactLeadSource: !empty($data['contact_lead_source']) ? (string) $data['contact_lead_source'] : null,
            participatingNumOfStudents: $participatingNumOfStudents,
            numOfStudents: !empty($data['num_of_students']) ? (int) $data['num_of_students'] : null,
            differentBillingContact: ($data['different_billing_contact'] ?? '') === 'Yes',
            billingContactEmail: !empty($data['billing_contact_email']) ? (string) $data['billing_contact_email'] : null,
            billingContactFirstName: !empty($data['billing_contact_first_name']) ? (string) $data['billing_contact_first_name'] : null,
            billingContactLastName: !empty($data['billing_contact_last_name']) ? (string) $data['billing_contact_last_name'] : null,
            billingContactPhone: !empty($data['billing_contact_phone']) ? (string) $data['billing_contact_phone'] : null,
            engage: !empty($data['engage']) ? (string) $data['engage'] : null,
            inspire: !empty($data['inspire']) ? (string) $data['inspire'] : null,
            mentalHealthFunding: !empty($data['mental_health_funding']) ? (string) $data['mental_health_funding'] : null,
            selectedYearLevels: isset($data['selected_year_levels']) && is_array($data['selected_year_levels']) ? $data['selected_year_levels'] : null,
            sourceForm: !empty($data['source_form']) ? (string) $data['source_form'] : null,
        );
    }

    /**
     * Build a Contact domain object from this request.
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
     * Build an Organisation domain object from this request.
     */
    public function toOrganisation(): Organisation
    {
        return new Organisation(
            accountNo: $this->schoolAccountNo,
            name: $this->schoolNameOther,
            state: $this->state,
            subType: $this->organisationSubType,
            numStudents: $this->numOfStudents,
            leadSource: $this->contactLeadSource,
        );
    }
}
