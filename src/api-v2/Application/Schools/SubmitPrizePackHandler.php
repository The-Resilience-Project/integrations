<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\Contact;
use ApiV2\Domain\Organisation;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitPrizePackHandler
{
    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Handle a school prize pack submission.
     *
     * @param array<string, mixed> $data Raw request data
     */
    public function handle(array $data): bool
    {
        $contact = Contact::fromFormData($data);
        $organisation = Organisation::fromFormData($data);

        // 1. Deactivate existing contacts with this email
        $customerService = new CustomerService($this->client);
        $customerService->deactivateExistingContacts($contact->email);

        // 2. Create/update contact and organisation in CRM
        $captured = $customerService->captureContact($contact, $organisation, $data);

        // 3. Fetch organisation details
        $orgDetails = $customerService->fetchOrganisationDetails($captured->organisationId);

        // 4. Update org assignee routing and sales event tracking
        $orgDetails = $customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $data);

        // 5. Update contact assignee routing and forms-completed tracking
        $customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $data);

        // 6. Mark org as 2026 lead if confirmation status is not yet set
        if (empty($orgDetails->confirmationStatus2026)) {
            $this->client->post('updateOrganisation', [
                'organisationId' => $orgDetails->organisationId,
                'organisation2026Status' => 'Lead',
            ]);
        }

        return true;
    }
}
