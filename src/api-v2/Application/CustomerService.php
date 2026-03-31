<?php

declare(strict_types=1);

namespace ApiV2\Application;

use ApiV2\Domain\Schools\AssigneeRules;
use ApiV2\Domain\CapturedContact;
use ApiV2\Domain\Contact;
use ApiV2\Domain\Organisation;
use ApiV2\Domain\OrganisationDetails;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class CustomerService
{
    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Deactivate all existing contacts with this email address in the CRM.
     */
    public function deactivateExistingContacts(string $email): void
    {
        $this->client->post('setContactsInactive', ['contactEmail' => $email]);
    }

    /**
     * Create or update a contact and organisation in the CRM.
     *
     * @param array<string, mixed> $data Raw request data
     */
    public function captureContact(Contact $contact, Organisation $organisation, array $data): CapturedContact
    {
        $payload = $this->buildCustomerPayload($contact, $organisation, $data);

        if (!empty($data['school_name_other_selected'])) {
            $payload['organisationName'] = $data['school_name_other'] ?? '';
            $response = $this->client->post('captureCustomerInfo', $payload);
        } else {
            $payload['organisationAccountNo'] = $data['school_account_no'] ?? '';
            $response = $this->client->post('captureCustomerInfoWithAccountNo', $payload);
        }

        $responseData = $response->result[0];

        return new CapturedContact(
            contactId: $responseData->id,
            organisationId: $responseData->account_id,
            assignedUserId: $responseData->assigned_user_id ?? '',
            formsCompleted: $responseData->cf_contacts_formscompleted ?? '',
        );
    }

    /**
     * Fetch organisation details from the CRM.
     */
    public function fetchOrganisationDetails(string $organisationId): OrganisationDetails
    {
        $response = $this->client->post('getOrgDetails', [
            'organisationId' => $organisationId,
        ], true);

        $org = $response->result[0];

        return new OrganisationDetails(
            organisationId: $organisationId,
            name: $org->accountname,
            assignedUserId: $org->assigned_user_id,
            salesEvents2025: $org->cf_accounts_2025salesevents ?? '',
            confirmationStatus2024: $org->cf_accounts_2024confirmationstatus ?? '',
            confirmationStatus2025: $org->cf_accounts_2025confirmationstatus ?? '',
            confirmationStatus2026: $org->cf_accounts_2026confirmationstatus ?? '',
        );
    }

    /**
     * Update the organisation's assignee (based on state routing) and append
     * the current form to sales event tracking.
     *
     * Returns updated OrganisationDetails (assignee may have changed).
     *
     * @param array<string, mixed> $data Raw request data (needs source_form, state)
     */
    public function updateOrgAssigneeAndSalesEvents(OrganisationDetails $orgDetails, array $data): OrganisationDetails
    {
        $requestBody = [];
        $state = $data['state'] ?? null;

        $newOrgAssignee = AssigneeRules::resolveOrgAssignee($orgDetails->assignedUserId, $state);
        if ($newOrgAssignee !== $orgDetails->assignedUserId) {
            $requestBody['assignee'] = $newOrgAssignee;
        }

        $currentForm = $data['source_form'] ?? '';
        if ($currentForm !== '') {
            $existingFormsArray = explode(' |##| ', $orgDetails->salesEvents2025);
            if (!in_array($currentForm, $existingFormsArray, true)) {
                $existingFormsArray[] = $currentForm;
                $requestBody['salesEvents2025'] = $existingFormsArray;
            }
        }

        if (count($requestBody) === 0) {
            return $orgDetails;
        }

        $requestBody['organisationId'] = $orgDetails->organisationId;
        $response = $this->client->post('updateOrganisation', $requestBody);
        $responseData = $response->result[0];

        return $orgDetails->withAssignedUserId($responseData->assigned_user_id);
    }

    /**
     * Update the contact's assignee (based on state routing) and append
     * the current form to forms-completed tracking.
     *
     * @param array<string, mixed> $data Raw request data (needs source_form, state)
     */
    public function updateContactAssigneeAndFormsCompleted(
        CapturedContact $captured,
        OrganisationDetails $orgDetails,
        array $data,
    ): void {
        $requestBody = [];
        $state = $data['state'] ?? null;

        $currentForm = $data['source_form'] ?? '';
        if ($currentForm !== '') {
            $existingFormsArray = explode(' |##| ', $captured->formsCompleted);
            if (!in_array($currentForm, $existingFormsArray, true)) {
                $existingFormsArray[] = $currentForm;
                $requestBody['contactLeadSource'] = $existingFormsArray;
            }
        }

        $newContactAssignee = AssigneeRules::resolveContactAssignee($orgDetails->assignedUserId, $state);
        if ($newContactAssignee !== $captured->assignedUserId) {
            $requestBody['assignee'] = $newContactAssignee;
        }

        if (count($requestBody) === 0) {
            return;
        }

        $requestBody['contactId'] = $captured->contactId;
        $this->client->post('updateContactById', $requestBody);
    }

    /**
     * Build the customer info payload for Vtiger.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildCustomerPayload(Contact $contact, Organisation $organisation, array $data): array
    {
        $payload = [
            'contactEmail' => $contact->email,
            'contactFirstName' => $contact->firstName,
            'contactLastName' => $contact->lastName,
            'organisationType' => 'School',
        ];

        if ($organisation->state) {
            $payload['state'] = $organisation->state;
        }
        if ($contact->type) {
            $payload['contactType'] = $contact->type;
        }
        if ($contact->phone) {
            $payload['contactPhone'] = $contact->phone;
        }
        if ($contact->orgPhone) {
            $payload['orgPhone'] = $contact->orgPhone;
        }
        if ($contact->newsletter) {
            $payload['newsletter'] = $contact->newsletter;
        }
        if ($contact->jobTitle) {
            $payload['jobTitle'] = $contact->jobTitle;
        }
        if ($organisation->numStudents) {
            $payload['organisationNumOfStudents'] = $organisation->numStudents;
        }
        if ($organisation->numEmployees) {
            $payload['organisationNumOfEmployees'] = $organisation->numEmployees;
        }
        if ($organisation->leadSource) {
            $payload['contactLeadSource'] = $organisation->leadSource;
        }
        if ($organisation->subType) {
            $payload['organisationSubType'] = $organisation->subType;
        }

        return $payload;
    }
}
