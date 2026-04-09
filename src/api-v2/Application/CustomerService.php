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
     */
    public function captureContact(Contact $contact, Organisation $organisation, string $sourceForm): CapturedContact
    {
        $payload = $this->buildCustomerPayload($contact, $organisation, $sourceForm);

        if ($organisation->name !== null) {
            $payload['organisationName'] = $organisation->name ?? '';
            $response = $this->client->post('captureCustomerInfo', $payload);
        } else {
            $payload['organisationAccountNo'] = $organisation->accountNo ?? '';
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
            yearsWithTrp: $org->cf_accounts_yearswithtrp ?? '',
        );
    }

    /**
     * Update the organisation's assignee (based on state routing) and append
     * the current form to sales event tracking.
     *
     * Returns updated OrganisationDetails (assignee may have changed).
     */
    public function updateOrgAssigneeAndSalesEvents(OrganisationDetails $orgDetails, string $sourceForm, ?string $state): OrganisationDetails
    {
        $requestBody = [];

        $newOrgAssignee = AssigneeRules::resolveOrgAssignee($orgDetails->assignedUserId, $state);
        if ($newOrgAssignee !== $orgDetails->assignedUserId) {
            $requestBody['assignee'] = $newOrgAssignee;
        }

        if ($sourceForm !== '') {
            $existingFormsArray = array_filter(explode(' |##| ', $orgDetails->salesEvents2025), fn ($v) => $v !== '');
            if (!in_array($sourceForm, $existingFormsArray, true)) {
                $existingFormsArray[] = $sourceForm;
                $requestBody['salesEvents2025'] = array_values($existingFormsArray);
            }
        }

        if (count($requestBody) === 0) {
            log_info('updateOrgAssigneeAndSalesEvents: No changes needed, skipping update');

            return $orgDetails;
        }

        $requestBody['organisationId'] = $orgDetails->organisationId;
        log_info('updateOrgAssigneeAndSalesEvents: Calling updateOrganisation', $requestBody);
        $response = $this->client->post('updateOrganisation', $requestBody);
        $responseData = $response->result[0];

        return $orgDetails->withAssignedUserId($responseData->assigned_user_id);
    }

    /**
     * Update the contact's assignee (based on state routing) and append
     * the current form to forms-completed tracking.
     */
    public function updateContactAssigneeAndFormsCompleted(
        CapturedContact $captured,
        OrganisationDetails $orgDetails,
        string $sourceForm,
        ?string $state,
    ): void {
        $requestBody = [];

        if ($sourceForm !== '') {
            $existingFormsArray = array_filter(explode(' |##| ', $captured->formsCompleted), fn ($v) => $v !== '');
            if (!in_array($sourceForm, $existingFormsArray, true)) {
                $existingFormsArray[] = $sourceForm;
                $requestBody['contactLeadSource'] = array_values($existingFormsArray);
            }
        }

        $newContactAssignee = AssigneeRules::resolveContactAssignee($orgDetails->assignedUserId, $state);
        if ($newContactAssignee !== $captured->assignedUserId) {
            $requestBody['assignee'] = $newContactAssignee;
        }

        if (count($requestBody) === 0) {
            log_info('updateContactAssigneeAndFormsCompleted: No changes needed, skipping update');

            return;
        }

        $requestBody['contactId'] = $captured->contactId;
        log_info('updateContactAssigneeAndFormsCompleted: Calling updateContactById', $requestBody);
        $this->client->post('updateContactById', $requestBody);
    }

    /**
     * Mark the organisation as a 2026 Lead if it has no existing confirmation status.
     */
    public function markOrgAsLead(OrganisationDetails $orgDetails): void
    {
        if ($orgDetails->confirmationStatus2026 !== '') {
            log_info('markOrgAsLead: Organisation already has 2026 status, skipping', [
                'organisationId' => $orgDetails->organisationId,
                'confirmationStatus2026' => $orgDetails->confirmationStatus2026,
            ]);

            return;
        }

        $requestBody = [
            'organisationId' => $orgDetails->organisationId,
            'organisation2026Status' => 'Lead',
        ];

        log_info('markOrgAsLead: Calling updateOrganisation', $requestBody);
        $this->client->post('updateOrganisation', $requestBody);
    }

    /**
     * Build the customer info payload for Vtiger.
     *
     * @return array<string, mixed>
     */
    private function buildCustomerPayload(Contact $contact, Organisation $organisation, string $sourceForm): array
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
        if ($sourceForm !== '') {
            $payload['sourceForm'] = $sourceForm;
        }

        return $payload;
    }
}
