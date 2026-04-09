<?php

use ApiV2\Application\Schools\SubmitConfirmationHandler;
use ApiV2\Config\UserIds;
use ApiV2\Domain\ConfirmationRequest;
use PHPUnit\Framework\TestCase;

class SubmitConfirmationHandlerTest extends TestCase
{
    private function makeClient(): StubVtigerWebhookClient
    {
        $client = new StubVtigerWebhookClient();

        // Step 1: Deactivate + capture customer
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

        // Step 2: Fetch org details + update org/contact
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

        // Step 3: Create deal (Deal Won)
        $client->setResponse(
            'getOrCreateDeal',
            StubVtigerWebhookClient::makeDealResponse(salesStage: 'Deal Won'),
        );

        // Step 4: Get services
        $client->setResponse(
            'getServices',
            self::makeServicesResponse(),
        );

        // Step 6: Update deal
        $client->setResponse(
            'updateDeal',
            (object) ['result' => []],
        );

        // Step 7: Set deal line items
        $client->setResponse(
            'setDealLineItems',
            (object) ['result' => []],
        );

        // Step 8: Create quote
        $client->setResponse(
            'createQuote',
            (object) ['result' => (object) ['id' => '5x400']],
        );

        // Step 10: Create SEIP
        $client->setResponse(
            'createOrUpdateSEIP',
            (object) ['result' => [(object) ['id' => '99x500']]],
        );

        return $client;
    }

    private function makeRequest(array $overrides = []): ConfirmationRequest
    {
        return ConfirmationRequest::fromFormData(array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
            'address' => '45 Collins Street',
            'suburb' => 'Melbourne',
            'postcode' => '3000',
            'different_billing_contact' => 'No',
            'participating_num_of_students' => '320',
        ], $overrides));
    }

    private static function makeServicesResponse(): object
    {
        return (object) [
            'result' => [
                (object) [
                    'id' => '25x1',
                    'service_no' => 'SER157',
                    'unit_price' => '1500.00',
                    'cf_services_xerocode' => 'INSPIRE-STD',
                ],
                (object) [
                    'id' => '25x2',
                    'service_no' => 'SER12',
                    'unit_price' => '25.00',
                    'cf_services_xerocode' => 'ENGAGE-JOURNALS',
                ],
            ],
        ];
    }

    // ── Happy path ──────────────────────────────────────────────────

    public function test_successful_confirmation_returns_true(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $result = $handler->handle($this->makeRequest());

        $this->assertTrue($result);
    }

    // ── Step ordering ───────────────────────────────────────────────

    public function test_deactivates_contacts_first(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $sequence = $client->getCallSequence();
        $deactivateIndex = array_search('setContactsInactive', $sequence);
        $captureIndex = array_search('captureCustomerInfoWithAccountNo', $sequence);
        $this->assertNotFalse($deactivateIndex);
        $this->assertNotFalse($captureIndex);
        $this->assertLessThan($captureIndex, $deactivateIndex);
    }

    // ── Deal creation ───────────────────────────────────────────────

    public function test_creates_deal_with_deal_won_stage(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('getOrCreateDeal'));
        $dealBody = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertSame('Deal Won', $dealBody['dealStage']);
    }

    // ── Billing contact ─────────────────────────────────────────────

    public function test_captures_billing_contact_when_different(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest([
            'different_billing_contact' => 'Yes',
            'billing_contact_email' => 'billing@school.edu.au',
            'billing_contact_first_name' => 'Bob',
            'billing_contact_last_name' => 'Jones',
        ]));

        $captureCalls = $client->getCallsTo('captureCustomerInfoWithAccountNo');
        $this->assertCount(2, $captureCalls, 'Expected two captureCustomerInfoWithAccountNo calls (primary + billing)');
    }

    public function test_skips_billing_contact_when_same(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $captureCalls = $client->getCallsTo('captureCustomerInfoWithAccountNo');
        $this->assertCount(1, $captureCalls, 'Expected only one captureCustomerInfoWithAccountNo call (primary only)');
    }

    // ── Line items ──────────────────────────────────────────────────

    public function test_fetches_services_for_line_items(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('getServices'));
    }

    // ── Deal update ─────────────────────────────────────────────────

    public function test_updates_deal_with_confirmation_details(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('updateDeal'));
        $updateBody = $client->getFirstCallBody('updateDeal');
        $this->assertSame('Deal Won', $updateBody['dealStage']);
        $this->assertSame('45 Collins Street', $updateBody['address']);
        $this->assertSame('VIC', $updateBody['state']);
        $this->assertSame(9500.0, $updateBody['total']);
    }

    // ── Deal line items ─────────────────────────────────────────────

    public function test_sets_deal_line_items(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('setDealLineItems'));
    }

    // ── Quote creation ──────────────────────────────────────────────

    public function test_creates_quote_with_tax_calculation(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('createQuote'));
        $quoteBody = $client->getFirstCallBody('createQuote');
        // 1 inspire at $1500 + 320 engage at $25 = 1500 + 8000 = 9500
        $this->assertSame(9500.0, $quoteBody['preTaxTotal']);
        $this->assertSame(950.0, $quoteBody['taxTotal']);
        $this->assertSame(10450.0, $quoteBody['grandTotal']);
    }

    // ── Organisation update ─────────────────────────────────────────

    public function test_updates_organisation_with_years_with_trp(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        // Find the updateOrganisation call that includes yearsWithTrp (step 9)
        $updateOrgCalls = $client->getCallsTo('updateOrganisation');
        $yearUpdateCall = null;
        foreach ($updateOrgCalls as $call) {
            if (isset($call['body']['yearsWithTrp'])) {
                $yearUpdateCall = $call['body'];
                break;
            }
        }
        $this->assertNotNull($yearUpdateCall, 'Expected an updateOrganisation call with yearsWithTrp');
        $this->assertContains('2027', $yearUpdateCall['yearsWithTrp']);
        $this->assertSame('45 Collins Street', $yearUpdateCall['address']);
        $this->assertSame('Melbourne', $yearUpdateCall['suburb']);
        $this->assertSame('3000', $yearUpdateCall['postcode']);
        $this->assertSame('VIC', $yearUpdateCall['state']);
    }

    public function test_appends_year_to_existing_years_with_trp(): void
    {
        $client = $this->makeClient();
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(
                assignedUserId: UserIds::MADDIE,
                yearsWithTrp: '2025 |##| 2026',
            ),
        );
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $updateOrgCalls = $client->getCallsTo('updateOrganisation');
        $yearUpdateCall = null;
        foreach ($updateOrgCalls as $call) {
            if (isset($call['body']['yearsWithTrp'])) {
                $yearUpdateCall = $call['body'];
                break;
            }
        }
        $this->assertNotNull($yearUpdateCall, 'Expected an updateOrganisation call with yearsWithTrp');
        $this->assertSame(['2025', '2026', '2027'], $yearUpdateCall['yearsWithTrp']);
    }

    // ── SEIP creation ───────────────────────────────────────────────

    public function test_creates_seip_record(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $this->assertTrue($client->wasCalled('createOrUpdateSEIP'));
        $seipBody = $client->getFirstCallBody('createOrUpdateSEIP');
        $this->assertSame('2027 SEIP', $seipBody['seipName']);
        $this->assertSame('1st year', $seipBody['yearsWithTrp']);
        $this->assertSame(320, $seipBody['participants']);
    }

    // ── SEIP linking ────────────────────────────────────────────────

    public function test_links_seip_to_contact(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        // updateContactById is called for both contact assignee update and SEIP linking
        $updateContactCalls = $client->getCallsTo('updateContactById');
        $seipLinkCall = null;
        foreach ($updateContactCalls as $call) {
            if (isset($call['body']['seipId'])) {
                $seipLinkCall = $call['body'];
                break;
            }
        }
        $this->assertNotNull($seipLinkCall, 'Expected an updateContactById call with seipId');
        $this->assertSame('99x500', $seipLinkCall['seipId']);
        $this->assertSame('4x100', $seipLinkCall['contactId']);
    }

    // ── Source form ─────────────────────────────────────────────────

    public function test_source_form_defaults_to_new_schools_confirmation(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest());

        $captureBody = $client->getFirstCallBody('captureCustomerInfoWithAccountNo');
        $this->assertArrayHasKey('sourceForm', $captureBody);
        $this->assertSame('New Schools Confirmation 2027', $captureBody['sourceForm']);
    }

    // ── Small school inspire code ───────────────────────────────────

    public function test_small_school_uses_different_inspire_code(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitConfirmationHandler($client);

        $handler->handle($this->makeRequest([
            'num_of_students' => '150',
        ]));

        $this->assertTrue($client->wasCalled('getServices'));
        $servicesBody = $client->getFirstCallBody('getServices');
        $this->assertContains('SER158', $servicesBody['serviceCodes']);
        $this->assertNotContains('SER157', $servicesBody['serviceCodes']);
    }
}
