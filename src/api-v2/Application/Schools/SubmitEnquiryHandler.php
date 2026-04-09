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

        $customerService = new CustomerService($this->client);
        log_info('Capturing and updating customer');
        $result = $customerService->captureAndUpdateCustomer($contact, $organisation, $sourceForm, $request->state);
        $captured = $result->captured;
        $orgDetails = $result->orgDetails;

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
                'dealPipeline' => $deal->pipeline,
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
