<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\Contact;
use ApiV2\Domain\Enquiry;
use ApiV2\Domain\RegistrationRequest;
use ApiV2\Domain\Schools\AssigneeRules;
use ApiV2\Domain\Schools\Deal;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitRegistrationHandler
{
    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Handle a school registration submission.
     *
     * @param RegistrationRequest $request The validated registration request
     */
    public function handle(RegistrationRequest $request): bool
    {
        $sourceForm = $request->sourceForm ?? 'Info Session Registration';

        $contact = $request->toContact();
        $organisation = $request->toOrganisation();

        // 1. Fetch event details
        log_info('Step 1: Fetching event details', ['eventId' => $request->eventId]);
        $eventResponse = $this->client->post('getEventDetails', ['eventId' => $request->eventId], true);
        $event = $eventResponse->result[0];
        $eventStartDate = $event->date_start;
        $eventStartDatetime = $eventStartDate . ' ' . $event->time_start;
        log_info('Step 1 complete: Event details fetched', [
            'eventStartDate' => $eventStartDate,
            'eventStartDatetime' => $eventStartDatetime,
        ]);

        // 2. Deactivate existing contacts with this email
        $customerService = new CustomerService($this->client);
        log_info('Step 2: Deactivating existing contacts', ['email' => $contact->email]);
        $customerService->deactivateExistingContacts($contact->email);

        // 3. Create/update contact and organisation in CRM
        log_info('Step 3: Capturing contact and organisation');
        $captured = $customerService->captureContact($contact, $organisation, $sourceForm);
        log_info('Step 3 complete: Contact captured', [
            'contactId' => $captured->contactId,
            'organisationId' => $captured->organisationId,
            'assignedUserId' => $captured->assignedUserId,
            'formsCompleted' => $captured->formsCompleted,
        ]);

        // 4. Fetch organisation details
        log_info('Step 4: Fetching organisation details', ['organisationId' => $captured->organisationId]);
        $orgDetails = $customerService->fetchOrganisationDetails($captured->organisationId);
        log_info('Step 4 complete: Organisation details fetched', [
            'orgName' => $orgDetails->name,
            'assignedUserId' => $orgDetails->assignedUserId,
        ]);

        // 5. Update org assignee routing and sales event tracking
        log_info('Step 5: Updating org assignee and sales events', [
            'sourceForm' => $sourceForm,
            'state' => $request->state ?? '',
        ]);
        $orgDetails = $customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $sourceForm, $request->state);
        log_info('Step 5 complete: Org updated', [
            'assignedUserId' => $orgDetails->assignedUserId,
        ]);

        // 6. Update contact assignee routing and forms-completed tracking
        log_info('Step 6: Updating contact assignee and forms completed', [
            'sourceForm' => $sourceForm,
            'capturedAssignee' => $captured->assignedUserId,
            'capturedFormsCompleted' => $captured->formsCompleted,
            'orgAssignee' => $orgDetails->assignedUserId,
        ]);
        $customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $sourceForm, $request->state);

        // 7. Branch on new/existing school
        log_info('Step 7: Checking if new school', [
            'orgAssignee' => $orgDetails->assignedUserId,
            'isNewSchool' => AssigneeRules::isNewSchool($orgDetails->assignedUserId),
        ]);

        if (AssigneeRules::isNewSchool($orgDetails->assignedUserId)) {
            // 7a. Create/get deal
            $closeDate = $this->addOneDay($eventStartDate);
            $deal = Deal::forSchoolRegistration($closeDate);

            $dealPayload = [
                'dealName' => $deal->name,
                'dealType' => $deal->type,
                'dealOrgType' => $deal->orgType,
                'dealStage' => $deal->stage,
                'dealCloseDate' => $deal->closeDate,
                'dealPipeline' => $deal->pipeline,
                'contactId' => $captured->contactId,
                'organisationId' => $captured->organisationId,
                'assignee' => AssigneeRules::resolveContactAssignee($orgDetails->assignedUserId, $request->state),
            ];

            if ($request->numOfStudents !== null) {
                $dealPayload['dealNumOfParticipants'] = $request->numOfStudents;
            }
            if ($request->state !== null) {
                $dealPayload['dealState'] = $request->state;
            }

            log_info('Step 7a: Creating/getting deal', $dealPayload);
            $dealResponse = $this->client->post('getOrCreateDeal', $dealPayload);
            $dealData = $dealResponse->result[0];
            $dealId = $dealData->id;
            log_info('Step 7a complete: Deal retrieved', ['dealId' => $dealId]);

            // 7b. Update deal with info session date
            $firstInfoSessionDate = $dealData->cf_potentials_firstinfosessiondate ?? null;
            if (empty($firstInfoSessionDate) || strcmp($eventStartDatetime, $firstInfoSessionDate) < 0) {
                $firstInfoSessionDate = $eventStartDatetime;
            }

            $updateDealPayload = [
                'dealId' => $dealId,
                'dealCloseDate' => $this->addOneDay($firstInfoSessionDate),
            ];
            $updateDealPayload['firstInfoSessionDate'] = $firstInfoSessionDate;

            if (($dealData->sales_stage ?? '') === 'New') {
                $updateDealPayload['dealStage'] = 'Considering';
            }

            log_info('Step 7b: Updating deal with info session date', $updateDealPayload);
            $this->client->post('updateDeal', $updateDealPayload);

            // 7c. Register contact for event
            $replyTo = AssigneeRules::resolveRegistrationReplyTo($request->state);
            log_info('Step 7c: Registering contact for event', [
                'contactId' => $captured->contactId,
                'eventId' => $request->eventId,
                'replyTo' => $replyTo,
            ]);
            $this->registerContactForEvent($contact, $captured->contactId, $event, $request->eventId, $sourceForm, $dealId, $replyTo);
        } else {
            // 7d. Existing school — create enquiry instead
            log_info('Step 7d: Existing school, creating enquiry');
            $orgName = $orgDetails->name;
            $enquirySubject = $contact->fullName();
            if ($orgName !== null) {
                $enquirySubject .= ' | ' . $orgName;
            }

            $enquiry = new Enquiry(
                subject: $enquirySubject,
                body: 'Request for live Info Session',
                type: 'School',
                contactId: $captured->contactId,
                assigneeId: AssigneeRules::resolveEnquiryAssignee($orgDetails->assignedUserId, $request->state),
            );

            log_info('Step 7d: Creating enquiry', [
                'subject' => $enquiry->subject,
                'type' => $enquiry->type,
                'assignee' => $enquiry->assigneeId,
            ]);
            $this->client->post('createEnquiry', [
                'enquirySubject' => $enquiry->subject,
                'enquiryBody' => $enquiry->body,
                'contactId' => $enquiry->contactId,
                'assignee' => $enquiry->assigneeId,
                'enquiryType' => $enquiry->type,
            ]);
        }

        log_info('All steps complete');

        return true;
    }

    private function registerContactForEvent(
        Contact $contact,
        string $contactId,
        object $event,
        string $eventId,
        string $sourceForm,
        string $dealId,
        string $replyTo,
    ): void {
        // Check if already registered
        $checkResponse = $this->client->post('checkContactRegisteredForEvent', [
            'eventNo' => $event->event_no,
            'contactId' => $contactId,
        ]);

        if (!empty($checkResponse->result)) {
            log_info('Contact already registered for event, skipping');

            return;
        }

        $eventStartDatetime = $event->date_start . ' ' . $event->time_start;

        $requestBody = [
            'eventId' => $eventId,
            'eventNo' => $event->event_no,
            'eventShortName' => $event->cf_events_shorteventname,
            'eventStart' => $eventStartDatetime,
            'eventZoomLink' => $event->cf_events_zoomlink,
            'registrationName' => $contact->fullName() . ' | ' . $event->event_no,
            'contactId' => $contactId,
            'dealId' => $dealId,
            'source' => $sourceForm,
            'replyTo' => $replyTo,
        ];

        log_info('Registering contact for event', $requestBody);
        $this->client->post('registerContact', $requestBody);
    }

    private function addOneDay(string $dateString): string
    {
        $date = new \DateTime($dateString);
        $date->add(new \DateInterval('P1D'));

        return $date->format('d/m/Y');
    }
}
