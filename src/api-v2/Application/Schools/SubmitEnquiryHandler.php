<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\Contact;
use ApiV2\Domain\Enquiry;
use ApiV2\Domain\Organisation;
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

        // 3. Fetch organisation details (assignee, sales events, etc.)
        $orgDetails = $customerService->fetchOrganisationDetails($captured->organisationId);

        // 4. Update org assignee routing and sales event tracking
        $orgDetails = $customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $data);

        // 5. Update contact assignee routing and forms-completed tracking
        $customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $data);

        // 6. Create deal if this is a new school
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

            if (!empty($data['participating_num_of_students'])) {
                $dealPayload['dealNumOfParticipants'] = $data['participating_num_of_students'];
            } elseif (!empty($data['num_of_students'])) {
                $dealPayload['dealNumOfParticipants'] = $data['num_of_students'];
            }

            if (!empty($data['state'])) {
                $dealPayload['dealState'] = $data['state'];
            }

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
            body: !empty($data['enquiry']) ? $data['enquiry'] : 'Conference Enquiry',
            type: 'School',
            contactId: $captured->contactId,
            assigneeId: AssigneeRules::resolveEnquiryAssignee(
                $orgDetails->assignedUserId,
                $data['state'] ?? null,
            ),
        );

        $this->client->post('createEnquiry', [
            'enquirySubject' => $enquiry->subject,
            'enquiryBody' => $enquiry->body,
            'contactId' => $enquiry->contactId,
            'assignee' => $enquiry->assigneeId,
            'enquiryType' => $enquiry->type,
        ]);

        return true;
    }
}
