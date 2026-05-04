<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\OrganisationDetails;
use ApiV2\Domain\TsAttendeeRequest;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

/**
 * Handles a single TS Attendee upload row.
 *
 * Mirrors the standard customer-capture flow used by other v2 endpoints
 * (deactivate → capture → fetch org → update org → update contact), but
 * applies different source-form tags to the contact and organisation:
 *   - Contact `forms_completed`     ← "2026 {STATE} TS Attendee"
 *   - Organisation `salesEvents2025` ← "2027 {STATE} TS Attendee"
 *
 * The tags differ by year because the conference itself runs in 2027 but
 * is being marketed during the 2026 cycle (contacts are tracked against
 * the year they were captured; orgs are tracked against the year of the
 * sales event).
 */
class SubmitTsAttendeeHandler
{
    private VtigerWebhookClientInterface $client;
    private string $contactTagTemplate;
    private string $orgTagTemplate;

    public function __construct(
        VtigerWebhookClientInterface $client,
        string $contactTagTemplate = '2026 {STATE} TS Attendee',
        string $orgTagTemplate = '2027 {STATE} TS Attendee',
    ) {
        $this->client = $client;
        $this->contactTagTemplate = $contactTagTemplate;
        $this->orgTagTemplate = $orgTagTemplate;
    }

    public function handle(TsAttendeeRequest $request): bool
    {
        $contact = $request->toContact();
        $organisation = $request->toOrganisation();
        $state = $request->state;

        $contactTag = $this->renderTag($this->contactTagTemplate, $state);
        $orgTag = $this->renderTag($this->orgTagTemplate, $state);

        $customerService = new CustomerService($this->client);

        // Same create/update process as every other v2 upload, but
        // orchestrated step-by-step so contact and org get different tags.
        log_info('TS Attendee: deactivating existing contacts', ['email' => $contact->email]);
        $customerService->deactivateExistingContacts($contact->email);

        log_info('TS Attendee: capturing contact + organisation');
        // Pass empty source form to captureContact — the per-record tags
        // are applied in the subsequent update calls below, so vTiger's
        // capture step shouldn't auto-tag with anything.
        $captured = $customerService->captureContact($contact, $organisation, '');

        log_info('TS Attendee: fetching organisation details', [
            'organisationId' => $captured->organisationId,
        ]);
        $orgDetails = $customerService->fetchOrganisationDetails($captured->organisationId);

        log_info('TS Attendee: updating org assignee + sales events', ['orgTag' => $orgTag]);
        $orgDetails = $customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $orgTag, $state);

        log_info('TS Attendee: updating contact assignee + forms completed', [
            'contactTag' => $contactTag,
        ]);
        $customerService->updateContactAssigneeAndFormsCompleted(
            $captured,
            $orgDetails,
            $contactTag,
            $state,
        );

        $this->maybeCreateDeal($request, $orgDetails);

        log_info('TS Attendee: all steps complete');

        return true;
    }

    /**
     * Replace `{STATE}` in the template with the request's state.
     */
    private function renderTag(string $template, string $state): string
    {
        return str_replace('{STATE}', $state, $template);
    }

    /**
     * Optional follow-up: decide whether to create a Deal for this attendee.
     *
     * Left intentionally blank for now — pending product decision on whether
     * TS attendees should always create a deal, only for new schools, or
     * never. When implemented, this should mirror the existing
     * `update_or_create_deal` flow used by the school enquiry handler.
     */
    private function maybeCreateDeal(TsAttendeeRequest $request, OrganisationDetails $orgDetails): void
    {
        // TODO: deal creation logic to be specified.
    }
}
