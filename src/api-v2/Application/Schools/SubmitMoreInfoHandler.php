<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\MoreInfoRequest;
use ApiV2\Domain\Schools\AssigneeRules;
use ApiV2\Domain\Schools\Deal;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitMoreInfoHandler
{
    private const EVENT_ID = '18x805253';

    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Handle a school more-info submission.
     *
     * @param MoreInfoRequest $request The validated more-info request
     */
    public function handle(MoreInfoRequest $request): bool
    {
        $sourceForm = $request->sourceForm ?? 'More Info 2027';

        $contact = $request->toContact();
        $organisation = $request->toOrganisation();

        $customerService = new CustomerService($this->client);
        log_info('Capturing and updating customer');
        $result = $customerService->captureAndUpdateCustomer($contact, $organisation, $sourceForm, $request->state);
        $captured = $result->captured;
        $orgDetails = $result->orgDetails;

        // 6. Branch on whether the school is new (no dedicated SPM) or existing.
        //    Existing schools route to an enquiry for their SPM; new schools fall
        //    through to the student-count branch (deal vs event registration).
        if (!AssigneeRules::isNewSchool($orgDetails->assignedUserId)) {
            log_info('Step 6: Existing school — creating enquiry for SPM');
            $this->createEnquiryForExistingSchool($contact, $captured->contactId, $orgDetails->name, $sourceForm, $orgDetails->assignedUserId, $request->state);
            log_info('All steps complete');

            return true;
        }

        // 7. New school: branch based on student count.
        $studentCount = $request->numOfStudents ?? 0;
        log_info('Step 7: New school — branching on student count', ['studentCount' => $studentCount]);

        if ($studentCount >= 500) {
            // 7a. Create deal for large new schools
            log_info('Step 7a: Creating deal for large new school (>= 500 students)');
            $deal = Deal::forSchoolEnquiry();

            $dealPayload = [
                'dealName' => $deal->name,
                'dealType' => $deal->type,
                'dealOrgType' => $deal->orgType,
                'dealStage' => $deal->stage,
                'dealCloseDate' => $deal->closeDate,
                'dealPipeline' => $deal->pipeline,
                'contactId' => $captured->contactId,
                'organisationId' => $captured->organisationId,
                'assignee' => AssigneeRules::resolveContactAssignee(
                    $orgDetails->assignedUserId,
                    $request->state,
                ),
            ];

            if ($studentCount > 0) {
                $dealPayload['dealNumOfParticipants'] = (string) $studentCount;
            }

            if ($request->state !== null) {
                $dealPayload['dealState'] = $request->state;
            }

            log_info('Step 7a: Creating deal', $dealPayload);
            $this->client->post('getOrCreateDeal', $dealPayload);
        } else {
            // 7b. Register contact for more-info event
            log_info('Step 7b: Registering contact for more-info event');
            $this->registerContactForEvent($contact, $captured->contactId, $sourceForm, $request->state);

            // 7c. Set contact lifecycle stage to Lead and status to Warm
            log_info('Step 7c: Updating contact lifecycle stage and status');
            $this->client->post('updateContactById', [
                'contactId' => $captured->contactId,
                'lifecycleStage' => 'Lead',
                'contactStatus' => 'Warm',
            ]);
        }

        log_info('All steps complete');

        return true;
    }

    /**
     * Create an enquiry for an existing school's SPM rather than registering
     * the contact for the more-info event. Mirrors v1's behaviour for
     * `Info Session Registration`/`Info Session Recording` when `is_new_school()`
     * is false (src/api/classes/school.php).
     */
    private function createEnquiryForExistingSchool(
        \ApiV2\Domain\Contact $contact,
        string $contactId,
        string $organisationName,
        string $sourceForm,
        ?string $orgAssigneeId,
        ?string $state,
    ): void {
        $enquirySubject = $contact->fullName();
        if ($organisationName !== '') {
            $enquirySubject .= ' | '.$organisationName;
        }

        $payload = [
            'enquirySubject' => $enquirySubject,
            'enquiryBody' => 'More Info Request ('.$sourceForm.')',
            'contactId' => $contactId,
            'assignee' => AssigneeRules::resolveEnquiryAssignee($orgAssigneeId, $state),
            'enquiryType' => 'School',
        ];

        log_info('Creating enquiry for existing school', $payload);
        $this->client->post('createEnquiry', $payload);
    }

    /**
     * Register the contact for the more-info event in Vtiger.
     */
    private function registerContactForEvent(\ApiV2\Domain\Contact $contact, string $contactId, string $sourceForm, ?string $state): void
    {
        // Fetch event details
        $eventResponse = $this->client->post('getEventDetails', [
            'eventId' => self::EVENT_ID,
        ], true);
        $event = $eventResponse->result[0];

        // Check if already registered
        $checkResponse = $this->client->post('checkContactRegisteredForEvent', [
            'eventNo' => $event->event_no,
            'contactId' => $contactId,
        ]);

        if (!empty($checkResponse->result)) {
            log_info('Contact already registered for event, skipping');

            return;
        }

        // Register the contact
        $eventStartDatetime = $event->date_start.' '.$event->time_start;

        $requestBody = [
            'eventId' => self::EVENT_ID,
            'eventNo' => $event->event_no,
            'eventShortName' => $event->cf_events_shorteventname,
            'eventStart' => $eventStartDatetime,
            'eventZoomLink' => $event->cf_events_zoomlink,
            'registrationName' => $contact->fullName().' | '.$event->event_no,
            'contactId' => $contactId,
            'source' => $sourceForm,
            'replyTo' => AssigneeRules::resolveRegistrationReplyTo($state),
        ];

        log_info('Registering contact for event', $requestBody);
        $this->client->post('registerContact', $requestBody);
    }
}
