<?php

use ApiV2\Application\Schools\SubmitMoreInfoHandler;
use ApiV2\Config\UserIds;
use ApiV2\Domain\MoreInfoRequest;
use PHPUnit\Framework\TestCase;

class SubmitMoreInfoHandlerTest extends TestCase
{
    private function makeClient(): StubVtigerWebhookClient
    {
        $client = new StubVtigerWebhookClient();

        // Standard responses for capture + org details + update org flow
        $client->setResponse(
            'setContactsInactive',
            (object) ['result' => []],
        );
        $client->setResponse(
            'captureCustomerInfoWithAccountNo',
            StubVtigerWebhookClient::makeCaptureResponse(),
        );
        $client->setResponse(
            'captureCustomerInfo',
            StubVtigerWebhookClient::makeCaptureResponse(),
        );
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(),
        );
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse(),
        );
        $client->setResponse(
            'updateContactById',
            (object) ['result' => []],
        );
        $client->setResponse(
            'getOrCreateDeal',
            StubVtigerWebhookClient::makeDealResponse(),
        );

        // Event registration responses
        $client->setResponse(
            'getEventDetails',
            self::makeEventDetailsResponse(),
        );
        $client->setResponse(
            'checkContactRegisteredForEvent',
            (object) ['result' => []],
        );
        $client->setResponse(
            'registerContact',
            (object) ['result' => []],
        );

        return $client;
    }

    private function makeRequest(array $overrides = []): MoreInfoRequest
    {
        return MoreInfoRequest::fromFormData(array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
        ], $overrides));
    }

    private static function makeEventDetailsResponse(): object
    {
        return (object) [
            'result' => [
                (object) [
                    'event_no' => 'EVT-001',
                    'date_start' => '2026-04-15',
                    'time_start' => '10:00',
                    'cf_events_shorteventname' => 'More Info Session',
                    'cf_events_zoomlink' => 'https://zoom.us/j/123456',
                ],
            ],
        ];
    }

    // ── Happy path ──────────────────────────────────────────────────

    public function test_successful_more_info_returns_true(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $result = $handler->handle($this->makeRequest());

        $this->assertTrue($result);
    }

    // ── Step ordering ───────────────────────────────────────────────

    public function test_deactivates_contacts_first(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $sequence = $client->getCallSequence();
        $this->assertSame('setContactsInactive', $sequence[0]);
    }

    // ── Capture routing ─────────────────────────────────────────────

    public function test_uses_account_no_endpoint_when_no_school_name_selected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('captureCustomerInfoWithAccountNo'));
        $this->assertFalse($client->wasCalled('captureCustomerInfo'));
    }

    public function test_uses_name_endpoint_when_school_name_selected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest([
            'school_name_other_selected' => true,
            'school_name_other' => 'Brand New School',
        ]));

        $this->assertTrue($client->wasCalled('captureCustomerInfo'));
    }

    // ── Source form tagging ─────────────────────────────────────────

    public function test_capture_payload_includes_source_form(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $captureBody = $client->getFirstCallBody('captureCustomerInfoWithAccountNo');
        $this->assertArrayHasKey('sourceForm', $captureBody);
        $this->assertSame('More Info 2026', $captureBody['sourceForm']);
    }

    public function test_updates_org_sales_events_with_source_form(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(salesEvents: ''),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateOrganisation'));
        $orgBody = $client->getFirstCallBody('updateOrganisation');
        $this->assertArrayHasKey('salesEvents2025', $orgBody);
        $this->assertContains('More Info 2026', $orgBody['salesEvents2025']);
    }

    public function test_appends_source_form_to_existing_org_sales_events(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(salesEvents: 'Enquiry'),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $orgBody = $client->getFirstCallBody('updateOrganisation');
        $this->assertContains('Enquiry', $orgBody['salesEvents2025']);
        $this->assertContains('More Info 2026', $orgBody['salesEvents2025']);
    }

    public function test_does_not_duplicate_source_form_in_org_sales_events(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(
                assignedUserId: UserIds::LAURA,
                salesEvents: 'More Info 2026',
            ),
        );
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse(UserIds::LAURA),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        // Org update should not be called since assignee is already LAURA and form already exists
        $this->assertFalse($client->wasCalled('updateOrganisation'));
    }

    public function test_updates_contact_forms_completed_with_source_form(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'captureCustomerInfoWithAccountNo',
            StubVtigerWebhookClient::makeCaptureResponse(formsCompleted: ''),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateContactById'));
        $contactBody = $client->getFirstCallBody('updateContactById');
        $this->assertArrayHasKey('contactLeadSource', $contactBody);
        $this->assertContains('More Info 2026', $contactBody['contactLeadSource']);
    }

    public function test_appends_source_form_to_existing_contact_forms_completed(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'captureCustomerInfoWithAccountNo',
            StubVtigerWebhookClient::makeCaptureResponse(formsCompleted: 'Enquiry'),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $contactBody = $client->getFirstCallBody('updateContactById');
        $this->assertContains('Enquiry', $contactBody['contactLeadSource']);
        $this->assertContains('More Info 2026', $contactBody['contactLeadSource']);
    }

    public function test_does_not_duplicate_source_form_in_contact_forms_completed(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'captureCustomerInfoWithAccountNo',
            StubVtigerWebhookClient::makeCaptureResponse(
                assignedUserId: UserIds::LAURA,
                formsCompleted: 'More Info 2026',
            ),
        );
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::LAURA),
        );
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse(UserIds::LAURA),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        // Contact update should not be called since assignee matches and form already exists
        $this->assertFalse($client->wasCalled('updateContactById'));
    }

    // ── Deal creation (>= 500 students) ─────────────────────────────

    public function test_creates_deal_for_new_school_with_500_or_more_students(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::MADDIE),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => '500']));

        $this->assertTrue($client->wasCalled('getOrCreateDeal'));
        $dealBody = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertSame('2027 School Partnership Program', $dealBody['dealName']);
        $this->assertSame('New', $dealBody['dealStage']);
        $this->assertSame('500', $dealBody['dealNumOfParticipants']);
    }

    public function test_does_not_create_deal_for_existing_school_with_500_or_more_students(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::EMMA),
        );
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse(UserIds::EMMA),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => '500']));

        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    public function test_deal_includes_state_when_provided(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::MADDIE),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest([
            'num_of_students' => '600',
            'state' => 'NSW',
        ]));

        $dealBody = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertSame('NSW', $dealBody['dealState']);
    }

    // ── Event registration (< 500 students) ─────────────────────────

    public function test_registers_contact_for_event_with_fewer_than_500_students(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => '200']));

        $this->assertTrue($client->wasCalled('getEventDetails'));
        $this->assertTrue($client->wasCalled('checkContactRegisteredForEvent'));
        $this->assertTrue($client->wasCalled('registerContact'));
        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    public function test_registers_contact_for_event_when_no_student_count(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('registerContact'));
        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    public function test_registration_payload_is_correct(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $regBody = $client->getFirstCallBody('registerContact');
        $this->assertSame('18x805253', $regBody['eventId']);
        $this->assertSame('EVT-001', $regBody['eventNo']);
        $this->assertSame('More Info Session', $regBody['eventShortName']);
        $this->assertSame('2026-04-15 10:00', $regBody['eventStart']);
        $this->assertSame('https://zoom.us/j/123456', $regBody['eventZoomLink']);
        $this->assertSame('Jane Smith | EVT-001', $regBody['registrationName']);
        $this->assertSame('4x100', $regBody['contactId']);
        $this->assertSame('More Info 2026', $regBody['source']);
    }

    public function test_skips_registration_when_contact_already_registered(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'checkContactRegisteredForEvent',
            (object) ['result' => [(object) ['id' => '99x1']]],
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('checkContactRegisteredForEvent'));
        $this->assertFalse($client->wasCalled('registerContact'));
    }

    // ── No enquiry record ───────────────────────────────────────────

    public function test_does_not_create_enquiry_for_large_school(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::MADDIE),
        );
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => '500']));

        $this->assertFalse($client->wasCalled('createEnquiry'));
    }

    public function test_does_not_create_enquiry_for_small_school(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitMoreInfoHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => '100']));

        $this->assertFalse($client->wasCalled('createEnquiry'));
    }
}
