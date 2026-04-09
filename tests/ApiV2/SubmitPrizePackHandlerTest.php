<?php

use ApiV2\Application\Schools\SubmitPrizePackHandler;
use ApiV2\Domain\PrizePackRequest;
use PHPUnit\Framework\TestCase;

class SubmitPrizePackHandlerTest extends TestCase
{
    private function makeClient(): StubVtigerWebhookClient
    {
        $client = new StubVtigerWebhookClient();

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

        return $client;
    }

    private function makeRequest(array $overrides = []): PrizePackRequest
    {
        return PrizePackRequest::fromFormData(array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
        ], $overrides));
    }

    // ── Happy path ──────────────────────────────────────────────────

    public function test_successful_submission_returns_true(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $result = $handler->handle($this->makeRequest());

        $this->assertTrue($result);
    }

    // ── Step ordering ───────────────────────────────────────────────

    public function test_deactivates_contacts_first(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $sequence = $client->getCallSequence();
        $this->assertSame('setContactsInactive', $sequence[0]);
    }

    // ── Capture routing ─────────────────────────────────────────────

    public function test_uses_account_no_endpoint_when_no_school_name_selected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('captureCustomerInfoWithAccountNo'));
        $this->assertFalse($client->wasCalled('captureCustomerInfo'));
    }

    public function test_uses_name_endpoint_when_school_name_selected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest([
            'school_name_other_selected' => true,
            'school_name_other' => 'Brand New School',
        ]));

        $this->assertTrue($client->wasCalled('captureCustomerInfo'));
    }

    // ── Source form ─────────────────────────────────────────────────

    public function test_default_source_form_flows_through_to_capture_payload(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $captureBody = $client->getFirstCallBody('captureCustomerInfoWithAccountNo');
        $this->assertSame('Conference Delegate 2026', $captureBody['sourceForm']);
    }

    public function test_custom_source_form_overrides_default(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest([
            'source_form' => 'NSWPDPN Delegate 2026',
        ]));

        $captureBody = $client->getFirstCallBody('captureCustomerInfoWithAccountNo');
        $this->assertSame('NSWPDPN Delegate 2026', $captureBody['sourceForm']);
    }

    public function test_prize_pack_default_source_form(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Prize Pack 2026');

        $handler->handle($this->makeRequest());

        $captureBody = $client->getFirstCallBody('captureCustomerInfoWithAccountNo');
        $this->assertSame('Prize Pack 2026', $captureBody['sourceForm']);
    }

    // ── Source form tagging ─────────────────────────────────────────

    public function test_updates_org_sales_events_with_source_form(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(salesEvents: ''),
        );
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateOrganisation'));
        $orgCalls = $client->getCallsTo('updateOrganisation');
        $firstOrgCall = $orgCalls[0]['body'];
        $this->assertArrayHasKey('salesEvents2025', $firstOrgCall);
        $this->assertContains('Conference Delegate 2026', $firstOrgCall['salesEvents2025']);
    }

    public function test_updates_contact_forms_completed_with_source_form(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'captureCustomerInfoWithAccountNo',
            StubVtigerWebhookClient::makeCaptureResponse(formsCompleted: ''),
        );
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateContactById'));
        $contactBody = $client->getFirstCallBody('updateContactById');
        $this->assertArrayHasKey('contactLeadSource', $contactBody);
        $this->assertContains('Conference Delegate 2026', $contactBody['contactLeadSource']);
    }

    // ── Mark org as Lead ────────────────────────────────────────────

    public function test_marks_org_as_lead_when_2026_status_is_empty(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(confirmationStatus2026: ''),
        );
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $orgCalls = $client->getCallsTo('updateOrganisation');
        $this->assertGreaterThanOrEqual(2, count($orgCalls));
        $leadCall = end($orgCalls);
        $this->assertSame('Lead', $leadCall['body']['organisation2026Status']);
    }

    public function test_does_not_mark_org_as_lead_when_2026_status_already_set(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(confirmationStatus2026: 'Confirmed'),
        );
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $orgCalls = $client->getCallsTo('updateOrganisation');
        foreach ($orgCalls as $call) {
            $this->assertArrayNotHasKey('organisation2026Status', $call['body']);
        }
    }

    // ── No deal or enquiry creation ─────────────────────────────────

    public function test_does_not_create_deal(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    public function test_does_not_create_enquiry(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');

        $handler->handle($this->makeRequest());

        $this->assertFalse($client->wasCalled('createEnquiry'));
    }
}
