<?php

use ApiV2\Application\Schools\SubmitEnquiryHandler;
use ApiV2\Config\UserIds;
use ApiV2\Domain\EnquiryRequest;
use PHPUnit\Framework\TestCase;

class SubmitEnquiryHandlerTest extends TestCase
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
        $client->setResponse(
            'createEnquiry',
            (object) ['result' => []],
        );

        return $client;
    }

    private function makeRequest(array $overrides = []): EnquiryRequest
    {
        return EnquiryRequest::fromFormData(array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
            'enquiry' => 'Interested in the program',
        ], $overrides));
    }

    public function test_successful_enquiry_returns_true(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitEnquiryHandler($client);

        $result = $handler->handle($this->makeRequest());

        $this->assertTrue($result);
    }

    public function test_deactivates_contacts_first(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $sequence = $client->getCallSequence();
        $this->assertSame('setContactsInactive', $sequence[0]);
    }

    public function test_uses_account_no_endpoint_when_no_school_name_selected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('captureCustomerInfoWithAccountNo'));
        $this->assertFalse($client->wasCalled('captureCustomerInfo'));
    }

    public function test_uses_name_endpoint_when_school_name_selected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest([
            'school_name_other_selected' => true,
            'school_name_other' => 'Brand New School',
        ]));

        $this->assertTrue($client->wasCalled('captureCustomerInfo'));
    }

    public function test_creates_deal_for_new_school(): void
    {
        $client = $this->makeClient();
        // MADDIE = new school
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::MADDIE),
        );
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('getOrCreateDeal'));
        $dealBody = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertSame('2027 School Partnership Program', $dealBody['dealName']);
        $this->assertSame('New', $dealBody['dealStage']);
    }

    public function test_does_not_create_deal_for_existing_school(): void
    {
        $client = $this->makeClient();
        // EMMA = dedicated SPM = existing school
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::EMMA),
        );
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse(UserIds::EMMA),
        );
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    public function test_creates_enquiry_with_correct_payload(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('createEnquiry'));
        $enquiryBody = $client->getFirstCallBody('createEnquiry');
        $this->assertStringContainsString('Jane Smith', $enquiryBody['enquirySubject']);
        $this->assertSame('Interested in the program', $enquiryBody['enquiryBody']);
        $this->assertSame('School', $enquiryBody['enquiryType']);
    }

    public function test_enquiry_assignee_is_laura_for_vic_new_school(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::MADDIE),
        );
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest(['state' => 'VIC']));

        $enquiryBody = $client->getFirstCallBody('createEnquiry');
        $this->assertSame(UserIds::LAURA, $enquiryBody['assignee']);
    }

    public function test_enquiry_assignee_is_brendan_for_nsw_new_school(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::MADDIE),
        );
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse(UserIds::BRENDAN),
        );
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest(['state' => 'NSW']));

        $enquiryBody = $client->getFirstCallBody('createEnquiry');
        $this->assertSame(UserIds::BRENDAN, $enquiryBody['assignee']);
    }

    public function test_enquiry_body_defaults_to_conference_enquiry(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitEnquiryHandler($client);

        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
        ]);
        $handler->handle($request);

        $enquiryBody = $client->getFirstCallBody('createEnquiry');
        $this->assertSame('Conference Enquiry', $enquiryBody['enquiryBody']);
    }

    public function test_updates_org_sales_events_with_source_form(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(salesEvents: ''),
        );
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateOrganisation'));
        $orgBody = $client->getFirstCallBody('updateOrganisation');
        $this->assertArrayHasKey('salesEvents2025', $orgBody);
        $this->assertContains('Enquiry', $orgBody['salesEvents2025']);
        $this->assertNotContains('', $orgBody['salesEvents2025'], 'Should not contain empty string');
    }

    public function test_appends_source_form_to_existing_org_sales_events(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(salesEvents: 'Registration Form'),
        );
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $orgBody = $client->getFirstCallBody('updateOrganisation');
        $this->assertContains('Registration Form', $orgBody['salesEvents2025']);
        $this->assertContains('Enquiry', $orgBody['salesEvents2025']);
    }

    public function test_does_not_duplicate_source_form_in_org_sales_events(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(
                assignedUserId: UserIds::LAURA,
                salesEvents: 'Enquiry',
            ),
        );
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse(UserIds::LAURA),
        );
        $handler = new SubmitEnquiryHandler($client);

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
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateContactById'));
        $contactBody = $client->getFirstCallBody('updateContactById');
        $this->assertArrayHasKey('contactLeadSource', $contactBody);
        $this->assertContains('Enquiry', $contactBody['contactLeadSource']);
        $this->assertNotContains('', $contactBody['contactLeadSource'], 'Should not contain empty string');
    }

    public function test_appends_source_form_to_existing_contact_forms_completed(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'captureCustomerInfoWithAccountNo',
            StubVtigerWebhookClient::makeCaptureResponse(formsCompleted: 'Registration Form'),
        );
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $contactBody = $client->getFirstCallBody('updateContactById');
        $this->assertContains('Registration Form', $contactBody['contactLeadSource']);
        $this->assertContains('Enquiry', $contactBody['contactLeadSource']);
    }

    public function test_does_not_duplicate_source_form_in_contact_forms_completed(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'captureCustomerInfoWithAccountNo',
            StubVtigerWebhookClient::makeCaptureResponse(
                assignedUserId: UserIds::LAURA,
                formsCompleted: 'Enquiry',
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
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        // Contact update should not be called since assignee matches and form already exists
        $this->assertFalse($client->wasCalled('updateContactById'));
    }

    public function test_capture_payload_includes_source_form(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitEnquiryHandler($client);

        $handler->handle($this->makeRequest());

        $captureBody = $client->getFirstCallBody('captureCustomerInfoWithAccountNo');
        $this->assertArrayHasKey('sourceForm', $captureBody);
        $this->assertSame('Enquiry', $captureBody['sourceForm']);
    }
}
