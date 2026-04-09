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

        $customerService = new CustomerService($this->client);
        log_info('Capturing and updating customer');
        $result = $customerService->captureAndUpdateCustomer($contact, $organisation, $sourceForm, $request->state);
        $captured = $result->captured;
        $orgDetails = $result->orgDetails;

        // 6. Mark organisation as 2026 Lead if not already marked
        log_info('Step 6: Marking organisation as 2026 Lead');
        $customerService->markOrgAsLead($orgDetails);

        log_info('All steps complete');

        return true;
    }
}
