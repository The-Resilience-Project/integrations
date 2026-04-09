<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\PrizePackRequest;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitPrizePackHandler
{
    private VtigerWebhookClientInterface $client;
    private string $defaultSourceForm;

    public function __construct(VtigerWebhookClientInterface $client, string $defaultSourceForm)
    {
        $this->client = $client;
        $this->defaultSourceForm = $defaultSourceForm;
    }

    /**
     * Handle a school conference delegate or prize pack submission.
     *
     * @param PrizePackRequest $request The validated request
     */
    public function handle(PrizePackRequest $request): bool
    {
        $sourceForm = $request->sourceForm ?? $this->defaultSourceForm;

        $contact = $request->toContact();
        $organisation = $request->toOrganisation();

        // 1. Deactivate existing contacts with this email
        $customerService = new CustomerService($this->client);
        log_info('Step 1: Deactivating existing contacts', ['email' => $contact->email]);
        $customerService->deactivateExistingContacts($contact->email);

        // 2. Create/update contact and organisation in CRM
        log_info('Step 2: Capturing contact and organisation');
        $captured = $customerService->captureContact($contact, $organisation, $sourceForm);
        log_info('Step 2 complete: Contact captured', [
            'contactId' => $captured->contactId,
            'organisationId' => $captured->organisationId,
            'assignedUserId' => $captured->assignedUserId,
            'formsCompleted' => $captured->formsCompleted,
        ]);

        // 3. Fetch organisation details (assignee, sales events, etc.)
        log_info('Step 3: Fetching organisation details', ['organisationId' => $captured->organisationId]);
        $orgDetails = $customerService->fetchOrganisationDetails($captured->organisationId);
        log_info('Step 3 complete: Organisation details fetched', [
            'orgName' => $orgDetails->name,
            'assignedUserId' => $orgDetails->assignedUserId,
            'salesEvents2025' => $orgDetails->salesEvents2025,
        ]);

        // 4. Update org assignee routing and sales event tracking
        log_info('Step 4: Updating org assignee and sales events', [
            'sourceForm' => $sourceForm,
            'state' => $request->state ?? '',
        ]);
        $orgDetails = $customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $sourceForm, $request->state);
        log_info('Step 4 complete: Org updated', [
            'assignedUserId' => $orgDetails->assignedUserId,
        ]);

        // 5. Update contact assignee routing and forms-completed tracking
        log_info('Step 5: Updating contact assignee and forms completed', [
            'sourceForm' => $sourceForm,
            'capturedAssignee' => $captured->assignedUserId,
            'capturedFormsCompleted' => $captured->formsCompleted,
            'orgAssignee' => $orgDetails->assignedUserId,
        ]);
        $customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $sourceForm, $request->state);

        // 6. Mark organisation as 2026 Lead if not already marked
        log_info('Step 6: Marking organisation as 2026 Lead');
        $customerService->markOrgAsLead($orgDetails);

        log_info('All steps complete');

        return true;
    }
}
