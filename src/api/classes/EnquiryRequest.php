<?php

class EnquiryRequest
{
    public readonly ContactInfo $contact;
    public readonly string $serviceType;
    public readonly ?string $state;
    public readonly ?string $sourceForm;

    // Organisation identifier — exactly one of these should be set
    public readonly ?string $accountNo;
    public readonly ?string $organisationName;
    public readonly bool $isNewOrganisation;

    // Service-specific optional fields
    public readonly ?string $enquiryBody;
    public readonly ?string $numOfStudents;
    public readonly ?string $numOfEyChildren;
    public readonly ?string $numOfEmployees;
    public readonly ?string $contactLeadSource;
    public readonly ?string $organisationSubType;

    // Raw form data for backward compatibility with controller $this->data
    private array $rawData;

    private function __construct(
        ContactInfo $contact,
        string $serviceType,
        array $rawData,
        ?string $state = null,
        ?string $sourceForm = null,
        ?string $accountNo = null,
        ?string $organisationName = null,
        bool $isNewOrganisation = false,
        ?string $enquiryBody = null,
        ?string $numOfStudents = null,
        ?string $numOfEyChildren = null,
        ?string $numOfEmployees = null,
        ?string $contactLeadSource = null,
        ?string $organisationSubType = null,
    ) {
        $this->contact = $contact;
        $this->serviceType = $serviceType;
        $this->rawData = $rawData;
        $this->state = $state;
        $this->sourceForm = $sourceForm;
        $this->accountNo = $accountNo;
        $this->organisationName = $organisationName;
        $this->isNewOrganisation = $isNewOrganisation;
        $this->enquiryBody = $enquiryBody;
        $this->numOfStudents = $numOfStudents;
        $this->numOfEyChildren = $numOfEyChildren;
        $this->numOfEmployees = $numOfEmployees;
        $this->contactLeadSource = $contactLeadSource;
        $this->organisationSubType = $organisationSubType;
    }

    /**
     * Create from raw form data array. Validates required fields and
     * resolves the organisation identifier based on service type.
     */
    public static function fromFormData(array $data): self
    {
        $contact = ContactInfo::fromArray($data);
        $serviceType = $data['service_type'] ?? 'General';

        $accountNo = null;
        $organisationName = null;
        $isNew = false;

        switch ($serviceType) {
            case 'School':
                if (!empty($data['school_name_other_selected'])) {
                    $organisationName = $data['school_name_other'] ?? null;
                    $isNew = true;
                } else {
                    $accountNo = $data['school_account_no'] ?? null;
                }
                break;
            case 'Workplace':
                if (!empty($data['organisation_name'])) {
                    $organisationName = $data['organisation_name'];
                } elseif (!empty($data['workplace_name_other_selected'])) {
                    $organisationName = $data['workplace_name_other'] ?? null;
                    $isNew = true;
                } else {
                    $accountNo = $data['workplace_account_no'] ?? null;
                }
                break;
            case 'Early Years':
                if (!empty($data['service_name_other_selected'])) {
                    $organisationName = $data['earlyyears_name_other'] ?? null;
                    $isNew = true;
                } else {
                    $accountNo = $data['earlyyears_account_no'] ?? null;
                }
                break;
        }

        return new self(
            contact: $contact,
            serviceType: $serviceType,
            rawData: $data,
            state: $data['state'] ?? null,
            sourceForm: $data['source_form'] ?? null,
            accountNo: $accountNo,
            organisationName: $organisationName,
            isNewOrganisation: $isNew,
            enquiryBody: $data['enquiry'] ?? null,
            numOfStudents: $data['num_of_students'] ?? null,
            numOfEyChildren: $data['num_of_ey_children'] ?? null,
            numOfEmployees: $data['num_of_employees'] ?? null,
            contactLeadSource: $data['contact_lead_source'] ?? null,
            organisationSubType: $data['organisation_sub_type'] ?? null,
        );
    }

    /**
     * Returns the best available organisation display name for logging.
     */
    public function organisationDisplayName(): string
    {
        return $this->organisationName ?? $this->accountNo ?? 'unknown';
    }

    /**
     * Returns the controller class name for this service type.
     */
    public function controllerClass(): string
    {
        $controllers = [
            'School' => 'SchoolVTController',
            'Workplace' => 'WorkplaceVTController',
            'Early Years' => 'EarlyYearsVTController',
            'Imperfects' => 'ImperfectsVTController',
        ];

        return $controllers[$this->serviceType] ?? 'GeneralVTController';
    }

    /**
     * Backward compatibility — returns the raw form data array
     * so controllers can still access $this->data fields.
     */
    public function toArray(): array
    {
        return $this->rawData;
    }
}
