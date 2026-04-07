<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\Contact;
use ApiV2\Domain\Organisation;
use ApiV2\Domain\Schools\AssigneeRules;
use ApiV2\Domain\Schools\Deal;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitMoreInfoHandler
{
    private const EVENT_ID = '18x556914';

    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Handle a school more-info submission.
     *
     * @param array<string, mixed> $data Raw request data
     */
    public function handle(array $data): bool
    {
        $sourceForm = 'More Info 2026';

        $contact = Contact::fromFormData($data);
        $organisation = Organisation::fromFormData($data);

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
            'state' => $data['state'] ?? '',
        ]);
        $orgDetails = $customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $sourceForm, $data['state'] ?? null);
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
        $customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $sourceForm, $data['state'] ?? null);

        // 6. Branch based on student count
        $studentCount = $this->getStudentCount($data);
        log_info('Step 6: Branching on student count', ['studentCount' => $studentCount]);

        if ($studentCount >= 500) {
            // 6a. Create deal for large schools
            log_info('Step 6a: Creating deal for large school (>= 500 students)');
            if (AssigneeRules::isNewSchool($orgDetails->assignedUserId)) {
                $deal = Deal::forSchoolEnquiry();
                $state = $data['state'] ?? null;

                $dealPayload = [
                    'dealName' => $deal->name,
                    'dealType' => $deal->type,
                    'dealOrgType' => $deal->orgType,
                    'dealStage' => $deal->stage,
                    'dealCloseDate' => $deal->closeDate,
                    'contactId' => $captured->contactId,
                    'organisationId' => $captured->organisationId,
                    'assignee' => AssigneeRules::resolveContactAssignee(
                        $orgDetails->assignedUserId,
                        $state,
                    ),
                ];

                if ($studentCount > 0) {
                    $dealPayload['dealNumOfParticipants'] = (string) $studentCount;
                }

                if (!empty($data['state'])) {
                    $dealPayload['dealState'] = $data['state'];
                }

                log_info('Step 6a: Creating deal', $dealPayload);
                $this->client->post('getOrCreateDeal', $dealPayload);
            }
        } else {
            // 6b. Register contact for more-info event
            log_info('Step 6b: Registering contact for more-info event');
            $this->registerContactForEvent($contact, $captured->contactId, $data, $sourceForm);
        }

        log_info('All steps complete');

        return true;
    }

    /**
     * Get the student count from form data.
     */
    private function getStudentCount(array $data): int
    {
        if (!empty($data['num_of_students'])) {
            return (int) $data['num_of_students'];
        }

        return 0;
    }

    /**
     * Register the contact for the more-info event in Vtiger.
     */
    private function registerContactForEvent(Contact $contact, string $contactId, array $data, string $sourceForm): void
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
        ];

        log_info('Registering contact for event', $requestBody);
        $this->client->post('registerContact', $requestBody);
    }
}
