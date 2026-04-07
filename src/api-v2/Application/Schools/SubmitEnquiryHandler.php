<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\Enquiry;
use ApiV2\Domain\EnquiryRequest;
use ApiV2\Domain\Schools\AssigneeRules;
use ApiV2\Domain\Schools\Deal;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitEnquiryHandler
{
    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Handle a school enquiry submission.
     *
     * @param EnquiryRequest $request The validated enquiry request
     */
    public function handle(EnquiryRequest $request): bool
    {
        $sourceForm = $request->sourceForm ?? 'Enquiry 2026';

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

        // 6. Create deal if this is a new school
        log_info('Step 6: Checking if new school for deal creation', [
            'orgAssignee' => $orgDetails->assignedUserId,
            'isNewSchool' => AssigneeRules::isNewSchool($orgDetails->assignedUserId),
        ]);
        if (AssigneeRules::isNewSchool($orgDetails->assignedUserId)) {
            $deal = Deal::forSchoolEnquiry();

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
                    $request->state,
                ),
            ];

            if ($request->participatingNumOfStudents !== null) {
                $dealPayload['dealNumOfParticipants'] = $request->participatingNumOfStudents;
            } elseif ($request->numOfStudents !== null) {
                $dealPayload['dealNumOfParticipants'] = $request->numOfStudents;
            }

            if ($request->state !== null) {
                $dealPayload['dealState'] = $request->state;
            }

            log_info('Step 6: Creating deal', $dealPayload);
            $this->client->post('getOrCreateDeal', $dealPayload);
        }

        // 7. Create the enquiry
        $orgName = $orgDetails->name;
        $enquirySubject = $contact->fullName();
        if ($orgName !== null) {
            $enquirySubject .= ' | '.$orgName;
        }

        $enquiry = new Enquiry(
            subject: $enquirySubject,
            body: $request->enquiry !== null ? $request->enquiry : 'Conference Enquiry',
            type: 'School',
            contactId: $captured->contactId,
            assigneeId: AssigneeRules::resolveEnquiryAssignee(
                $orgDetails->assignedUserId,
                $request->state,
            ),
        );

        log_info('Step 7: Creating enquiry', [
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

        log_info('All steps complete');

        return true;
    }
}
