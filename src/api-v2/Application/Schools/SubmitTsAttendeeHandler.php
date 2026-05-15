<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\CapturedContact;
use ApiV2\Domain\Contact;
use ApiV2\Domain\OrganisationDetails;
use ApiV2\Domain\Schools\AssigneeRules;
use ApiV2\Domain\Schools\Deal;
use ApiV2\Domain\TsAttendeeRequest;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

/**
 * Handles a single TS Attendee upload row.
 *
 * Pipeline:
 *  1. Standard customer-capture (deactivate → capture → fetch org →
 *     update org → update contact), with two different tags applied:
 *       - Contact `forms_completed`     ← "2026 {STATE} TS Attendee"
 *       - Organisation sales-events list ← "2027 {STATE} TS Attendee"
 *  2. Branch on `num_of_students`:
 *       - >= 500 → create a deal for new schools (G&D nurture)
 *       - <  500 → register the contact for the TS Attendee event and
 *                  flag them Lead/Hot for the email comms workflow
 */
class SubmitTsAttendeeHandler
{
    private const DEFAULT_TS_EVENT_ID = '18x821531';

    private const LARGE_SCHOOL_THRESHOLD = 500;

    private VtigerWebhookClientInterface $client;
    private string $contactTagTemplate;
    private string $orgTagTemplate;
    private string $eventId;

    public function __construct(
        VtigerWebhookClientInterface $client,
        string $contactTagTemplate = '2026 {STATE} TS Attendee',
        string $orgTagTemplate = '2026 {STATE} TS Attendee',
        string $eventId = self::DEFAULT_TS_EVENT_ID,
    ) {
        $this->client = $client;
        $this->contactTagTemplate = $contactTagTemplate;
        $this->orgTagTemplate = $orgTagTemplate;
        $this->eventId = $eventId;
    }

    public function handle(TsAttendeeRequest $request): bool
    {
        $contact = $request->toContact();
        $organisation = $request->toOrganisation();
        $state = $request->state;

        $contactTag = $this->renderTag($this->contactTagTemplate, $state);
        $orgTag = $this->renderTag($this->orgTagTemplate, $state);

        $customerService = new CustomerService($this->client);

        // 1–5. Standard create/update flow with separate per-record tags.
        log_info('TS Attendee: deactivating existing contacts', ['email' => $contact->email]);
        $customerService->deactivateExistingContacts($contact->email);

        log_info('TS Attendee: capturing contact + organisation');
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

        // 6. Branch on student count.
        // Missing num_of_students is treated as 0 (lead-nurture path) — by the
        // time a row reaches the upload step the prep stage should have filled
        // it in, but the safer default is the smaller-school path.
        $studentCount = $request->numOfStudents ?? 0;
        log_info('TS Attendee: branching on student count', [
            'studentCount' => $studentCount,
            'threshold' => self::LARGE_SCHOOL_THRESHOLD,
        ]);

        if ($studentCount >= self::LARGE_SCHOOL_THRESHOLD) {
            $this->createDealForLargeSchool($request, $captured, $orgDetails, $orgTag);
        } else {
            $this->registerForEventAndMarkLead($contact, $captured, $orgDetails, $contactTag, $state);
        }

        log_info('TS Attendee: all steps complete');

        return true;
    }

    /**
     * 500+ students path. Creates one deal per school via getOrCreateDeal —
     * subsequent attendees from the same school hit the existing deal and
     * don't create duplicates. Skipped when the org already has a dedicated
     * School Partnership Manager (i.e. isn't a "new school").
     *
     * Contact-link rule: if this is the first attendee from the school, link
     * the deal to that attendee (contactId). If a prior attendee from the
     * same school has already been processed, omit contactId — we can't pick
     * one attendee to represent the school once multiple have come in.
     * "Prior attendee" is detected via the org tag (e.g. "2027 VIC TS Attendee")
     * already being present on the org's salesEvents2025 list at fetch time
     * (before this submission's tag append).
     *
     * Mirrors the deal-creation step in the More Info handler.
     */
    private function createDealForLargeSchool(
        TsAttendeeRequest $request,
        CapturedContact $captured,
        OrganisationDetails $orgDetails,
        string $orgTag,
    ): void {
        if (!AssigneeRules::isNewSchool($orgDetails->assignedUserId)) {
            log_info('TS Attendee: skipping deal — school already has a dedicated SPM', [
                'orgAssignee' => $orgDetails->assignedUserId,
            ]);

            return;
        }

        $deal = Deal::forSchoolEnquiry();

        $existingOrgTags = array_filter(
            explode(' |##| ', $orgDetails->salesEvents2025),
            fn ($v) => $v !== '',
        );
        $hasPriorAttendee = in_array($orgTag, $existingOrgTags, true);

        $dealPayload = [
            'dealName' => $deal->name,
            'dealType' => $deal->type,
            'dealOrgType' => $deal->orgType,
            'dealStage' => $deal->stage,
            'dealCloseDate' => $deal->closeDate,
            'dealPipeline' => $deal->pipeline,
            'organisationId' => $captured->organisationId,
            'assignee' => AssigneeRules::resolveContactAssignee(
                $orgDetails->assignedUserId,
                $request->state,
            ),
            'dealState' => $request->state,
        ];

        if (!$hasPriorAttendee) {
            $dealPayload['contactId'] = $captured->contactId;
        }

        if (($request->numOfStudents ?? 0) > 0) {
            $dealPayload['dealNumOfParticipants'] = (string) $request->numOfStudents;
        }

        log_info('TS Attendee: creating deal for large school (>= 500)', [
            'payload' => $dealPayload,
            'hasPriorAttendee' => $hasPriorAttendee,
        ]);
        $response = $this->client->post('getOrCreateDeal', $dealPayload);
        log_info('TS Attendee: getOrCreateDeal response', ['response' => $response]);
    }

    /**
     * Sub-500 students path. Registers the contact for the TS Attendee event
     * and flags them Lead/Hot. The two follow-up emails (Email 1 +1wk,
     * Email 2 +2wks) and the eventual move to Lead/WARM are driven by
     * vTiger Process Designer — not this handler.
     *
     * Mirrors the event-registration step in the More Info handler.
     */
    private function registerForEventAndMarkLead(
        Contact $contact,
        CapturedContact $captured,
        OrganisationDetails $orgDetails,
        string $contactTag,
        string $state,
    ): void {
        log_info('TS Attendee: registering contact for TS Attendee event');
        $this->registerContactForEvent($contact, $captured->contactId, $contactTag, $state);

        log_info('TS Attendee: setting contact lifecycle = Lead, status = Hot');
        $this->client->post('updateContactById', [
            'contactId' => $captured->contactId,
            'lifecycleStage' => 'Lead',
            'contactStatus' => 'Hot',
        ]);
    }

    /**
     * Register a contact for the TS Attendee event. Skips if already
     * registered. Same approach as SubmitMoreInfoHandler.
     */
    private function registerContactForEvent(
        Contact $contact,
        string $contactId,
        string $sourceTag,
        string $state,
    ): void {
        $eventResponse = $this->client->post('getEventDetails', [
            'eventId' => $this->eventId,
        ], true);
        $event = $eventResponse->result[0];

        $checkResponse = $this->client->post('checkContactRegisteredForEvent', [
            'eventNo' => $event->event_no,
            'contactId' => $contactId,
        ]);

        if (!empty($checkResponse->result)) {
            log_info('TS Attendee: contact already registered for event, skipping');

            return;
        }

        $eventStartDatetime = $event->date_start.' '.$event->time_start;

        $requestBody = [
            'eventId' => $this->eventId,
            'eventNo' => $event->event_no,
            'eventShortName' => $event->cf_events_shorteventname,
            'eventStart' => $eventStartDatetime,
            'eventZoomLink' => $event->cf_events_zoomlink,
            'registrationName' => $contact->fullName().' | '.$event->event_no,
            'contactId' => $contactId,
            'source' => $sourceTag,
            'replyTo' => AssigneeRules::resolveRegistrationReplyTo($state),
        ];

        log_info('TS Attendee: registering contact for event', $requestBody);
        $this->client->post('registerContact', $requestBody);
    }

    /**
     * Replace `{STATE}` in the template with the request's state.
     */
    private function renderTag(string $template, string $state): string
    {
        return str_replace('{STATE}', $state, $template);
    }
}
