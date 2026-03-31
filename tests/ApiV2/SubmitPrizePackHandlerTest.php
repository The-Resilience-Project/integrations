<?php

use ApiV2\Application\Schools\SubmitPrizePackHandler;
use PHPUnit\Framework\TestCase;

class SubmitPrizePackHandlerTest extends TestCase
{
    private function makeClient(): StubVtigerWebhookClient
    {
        $client = new StubVtigerWebhookClient();

        $client->setResponse('setContactsInactive', (object) ['result' => []]);
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
        $client->setResponse('updateContactById', (object) ['result' => []]);

        return $client;
    }

    private function makeSchoolData(array $overrides = []): array
    {
        return array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
            'source_form' => 'Prize Pack',
        ], $overrides);
    }

    public function test_successful_prize_pack_returns_true(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client);

        $result = $handler->handle($this->makeSchoolData());

        $this->assertTrue($result);
    }

    public function test_captures_customer_info(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client);

        $handler->handle($this->makeSchoolData());

        $this->assertTrue($client->wasCalled('setContactsInactive'));
        $this->assertTrue($client->wasCalled('captureCustomerInfoWithAccountNo'));
    }

    public function test_marks_org_as_2026_lead_when_status_empty(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(confirmationStatus2026: ''),
        );
        $handler = new SubmitPrizePackHandler($client);

        $handler->handle($this->makeSchoolData());

        // updateOrganisation is called twice: once by captureCustomerInfo, once by markOrgAs2026Lead
        $calls = $client->getCallsTo('updateOrganisation');
        $leadUpdateFound = false;
        foreach ($calls as $call) {
            if (isset($call['body']['organisation2026Status']) && $call['body']['organisation2026Status'] === 'Lead') {
                $leadUpdateFound = true;
            }
        }
        $this->assertTrue($leadUpdateFound, 'Expected updateOrganisation call with organisation2026Status = Lead');
    }

    public function test_does_not_mark_lead_when_status_already_set(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(confirmationStatus2026: 'Confirmed'),
        );
        $handler = new SubmitPrizePackHandler($client);

        $handler->handle($this->makeSchoolData());

        // updateOrganisation should only be called by captureCustomerInfo, not by markOrgAs2026Lead
        $calls = $client->getCallsTo('updateOrganisation');
        foreach ($calls as $call) {
            $this->assertArrayNotHasKey(
                'organisation2026Status',
                $call['body'],
                'Should not update 2026 status when already set',
            );
        }
    }

    public function test_does_not_create_deal(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client);

        $handler->handle($this->makeSchoolData());

        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    public function test_does_not_create_enquiry(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitPrizePackHandler($client);

        $handler->handle($this->makeSchoolData());

        $this->assertFalse($client->wasCalled('createEnquiry'));
    }
}
