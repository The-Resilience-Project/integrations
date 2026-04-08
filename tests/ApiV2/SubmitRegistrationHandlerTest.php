<?php

use ApiV2\Application\Schools\SubmitRegistrationHandler;
use ApiV2\Config\UserIds;
use ApiV2\Domain\RegistrationRequest;
use PHPUnit\Framework\TestCase;

class SubmitRegistrationHandlerTest extends TestCase
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
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::MADDIE),
        );
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse(),
        );
        $client->setResponse(
            'updateContactById',
            (object) ['result' => []],
        );

        // Event details
        $client->setResponse(
            'getEventDetails',
            self::makeEventDetailsResponse(),
        );

        // Deal responses (new school path)
        $client->setResponse(
            'getOrCreateDeal',
            StubVtigerWebhookClient::makeDealResponse(),
        );
        $client->setResponse(
            'updateDeal',
            (object) ['result' => []],
        );

        // Event registration responses
        $client->setResponse(
            'checkContactRegisteredForEvent',
            (object) ['result' => []],
        );
        $client->setResponse(
            'registerContact',
            (object) ['result' => []],
        );

        // More-info registration update
        $client->setResponse(
            'updateRegistration',
            (object) ['result' => []],
        );

        // Existing school path
        $client->setResponse(
            'createEnquiry',
            (object) ['result' => []],
        );

        return $client;
    }

    private function makeRequest(array $overrides = []): RegistrationRequest
    {
        return RegistrationRequest::fromFormData(array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'event_id' => '18x556914',
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
                    'cf_events_shorteventname' => 'Info Session',
                    'cf_events_zoomlink' => 'https://zoom.us/j/123456',
                ],
            ],
        ];
    }

    // ── Happy path ──────────────────────────────────────────────────

    public function test_successful_registration_returns_true(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $result = $handler->handle($this->makeRequest());

        $this->assertTrue($result);
    }

    // ── Step ordering ───────────────────────────────────────────────

    public function test_fetches_event_details_first(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $sequence = $client->getCallSequence();
        $this->assertSame('getEventDetails', $sequence[0]);
    }

    public function test_deactivates_contacts_before_capture(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $sequence = $client->getCallSequence();
        $deactivateIndex = array_search('setContactsInactive', $sequence);
        $captureIndex = array_search('captureCustomerInfoWithAccountNo', $sequence);
        $this->assertNotFalse($deactivateIndex);
        $this->assertNotFalse($captureIndex);
        $this->assertLessThan($captureIndex, $deactivateIndex);
    }

    // ── Capture routing ─────────────────────────────────────────────

    public function test_uses_account_no_endpoint_when_no_school_name_selected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('captureCustomerInfoWithAccountNo'));
        $this->assertFalse($client->wasCalled('captureCustomerInfo'));
    }

    public function test_uses_name_endpoint_when_school_name_selected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest([
            'school_name_other_selected' => true,
            'school_name_other' => 'Brand New School',
        ]));

        $this->assertTrue($client->wasCalled('captureCustomerInfo'));
    }

    // ── New school deal creation ────────────────────────────────────

    public function test_creates_deal_for_new_school(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(assignedUserId: UserIds::MADDIE),
        );
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('getOrCreateDeal'));
        $dealBody = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertSame('In Campaign', $dealBody['dealStage']);
        $this->assertSame('New Schools', $dealBody['dealPipeline']);
        $this->assertSame('Hot', $dealBody['dealInCampaignRating']);
    }

    public function test_updates_deal_with_info_session_date(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateDeal'));
        $updateBody = $client->getFirstCallBody('updateDeal');
        $this->assertArrayHasKey('firstInfoSessionDate', $updateBody);
        $this->assertArrayHasKey('dealCloseDate', $updateBody);
        $this->assertSame('2026-04-15 10:00', $updateBody['firstInfoSessionDate']);
    }

    public function test_advances_deal_stage_from_new_to_in_campaign(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrCreateDeal',
            StubVtigerWebhookClient::makeDealResponse(salesStage: 'New'),
        );
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $updateBody = $client->getFirstCallBody('updateDeal');
        $this->assertSame('In Campaign', $updateBody['dealStage']);
        $this->assertSame('Hot', $updateBody['dealInCampaignRating']);
    }

    public function test_advances_deal_stage_from_considering_to_in_campaign(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrCreateDeal',
            StubVtigerWebhookClient::makeDealResponse(salesStage: 'Considering'),
        );
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $updateBody = $client->getFirstCallBody('updateDeal');
        $this->assertSame('In Campaign', $updateBody['dealStage']);
        $this->assertSame('Hot', $updateBody['dealInCampaignRating']);
    }

    public function test_does_not_change_deal_stage_when_already_in_campaign(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrCreateDeal',
            StubVtigerWebhookClient::makeDealResponse(salesStage: 'In Campaign'),
        );
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $updateBody = $client->getFirstCallBody('updateDeal');
        $this->assertArrayNotHasKey('dealStage', $updateBody);
        $this->assertSame('Hot', $updateBody['dealInCampaignRating']);
    }

    // ── Event registration (new school) ─────────────────────────────

    public function test_registers_contact_for_event_new_school(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('registerContact'));
        $regBody = $client->getFirstCallBody('registerContact');
        $this->assertArrayHasKey('replyTo', $regBody);
        $this->assertSame('2x300', $regBody['dealId']);
        $this->assertSame('Info Session Registration 2026', $regBody['source']);
    }

    public function test_registration_payload_includes_reply_to(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest(['state' => 'VIC']));

        $regBody = $client->getFirstCallBody('registerContact');
        $this->assertArrayHasKey('replyTo', $regBody);
        $this->assertSame(UserIds::LAURA, $regBody['replyTo']);
    }

    public function test_skips_registration_when_already_registered(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'checkContactRegisteredForEvent',
            (object) ['result' => [(object) ['id' => '99x1']]],
        );
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('checkContactRegisteredForEvent'));
        $this->assertFalse($client->wasCalled('registerContact'));
    }

    // ── More-info registration update ─────────────────────────────

    public function test_attaches_deal_to_existing_more_info_registration(): void
    {
        $client = $this->makeClient();
        // Contact is registered for more-info event
        $client->setResponse(
            'checkContactRegisteredForEvent',
            (object) ['result' => [(object) ['id' => '50x999']]],
        );
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateRegistration'));
        $updateBody = $client->getFirstCallBody('updateRegistration');
        $this->assertSame('50x999', $updateBody['registrationId']);
        $this->assertSame('2x300', $updateBody['dealId']);
    }

    public function test_does_not_update_registration_when_no_more_info_registration(): void
    {
        $client = $this->makeClient();
        // Default: checkContactRegisteredForEvent returns empty
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertFalse($client->wasCalled('updateRegistration'));
    }

    // ── Existing school path ────────────────────────────────────────

    public function test_creates_enquiry_for_existing_school(): void
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
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('createEnquiry'));
        $enquiryBody = $client->getFirstCallBody('createEnquiry');
        $this->assertSame('Request for live Info Session', $enquiryBody['enquiryBody']);
    }

    public function test_does_not_register_for_event_for_existing_school(): void
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
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertFalse($client->wasCalled('registerContact'));
        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    // ── Source form tagging ─────────────────────────────────────────

    public function test_capture_payload_includes_source_form(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest());

        $captureBody = $client->getFirstCallBody('captureCustomerInfoWithAccountNo');
        $this->assertArrayHasKey('sourceForm', $captureBody);
        $this->assertSame('Info Session Registration 2026', $captureBody['sourceForm']);
    }

    public function test_custom_source_form_flows_through(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitRegistrationHandler($client);

        $handler->handle($this->makeRequest(['source_form' => 'Custom Registration Form']));

        $captureBody = $client->getFirstCallBody('captureCustomerInfoWithAccountNo');
        $this->assertSame('Custom Registration Form', $captureBody['sourceForm']);
    }
}
