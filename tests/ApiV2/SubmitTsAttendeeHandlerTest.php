<?php

use ApiV2\Application\Schools\SubmitTsAttendeeHandler;
use ApiV2\Config\UserIds;
use ApiV2\Domain\TsAttendeeRequest;
use PHPUnit\Framework\TestCase;

class SubmitTsAttendeeHandlerTest extends TestCase
{
    private const TEST_EVENT_ID = '18xTEST';

    private function makeClient(string $orgAssigneeAfterUpdate = UserIds::LAURA): StubVtigerWebhookClient
    {
        $client = new StubVtigerWebhookClient();

        $client->setResponse('setContactsInactive', (object) ['result' => []]);
        $client->setResponse('captureCustomerInfo', StubVtigerWebhookClient::makeCaptureResponse());
        $client->setResponse('captureCustomerInfoWithAccountNo', StubVtigerWebhookClient::makeCaptureResponse());
        $client->setResponse('getOrgDetails', StubVtigerWebhookClient::makeOrgDetailsResponse());
        $client->setResponse(
            'updateOrganisation',
            StubVtigerWebhookClient::makeUpdateOrgResponse($orgAssigneeAfterUpdate),
        );
        $client->setResponse('updateContactById', (object) ['result' => []]);
        $client->setResponse('getOrCreateDeal', StubVtigerWebhookClient::makeDealResponse());
        $client->setResponse('getEventDetails', self::makeEventDetailsResponse());
        $client->setResponse('checkContactRegisteredForEvent', (object) ['result' => []]);
        $client->setResponse('registerContact', (object) ['result' => []]);

        return $client;
    }

    private function makeHandler(StubVtigerWebhookClient $client): SubmitTsAttendeeHandler
    {
        return new SubmitTsAttendeeHandler(
            $client,
            contactTagTemplate: '2026 {STATE} TS Attendee',
            orgTagTemplate: '2027 {STATE} TS Attendee',
            eventId: self::TEST_EVENT_ID,
        );
    }

    private function makeRequest(array $overrides = []): TsAttendeeRequest
    {
        return TsAttendeeRequest::fromFormData(array_merge([
            'contact_email' => 'attendee@school.edu.au',
            'contact_first_name' => 'Alice',
            'contact_last_name' => 'Nguyen',
            'school_name' => 'Springvale Secondary College',
            'state' => 'VIC',
            'num_of_students' => 1677,
        ], $overrides));
    }

    private static function makeEventDetailsResponse(): object
    {
        return (object) [
            'result' => [
                (object) [
                    'event_no' => 'EVT-TS-001',
                    'date_start' => '2026-08-15',
                    'time_start' => '10:00',
                    'cf_events_shorteventname' => 'TS Attendee Follow-up',
                    'cf_events_zoomlink' => 'https://zoom.us/j/ts-attendee',
                ],
            ],
        ];
    }

    // ── Happy path ──────────────────────────────────────────────────

    public function test_successful_submission_returns_true(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $this->assertTrue($handler->handle($this->makeRequest()));
    }

    // ── Tag application (runs on every branch) ──────────────────────

    public function test_org_gets_2027_state_ts_attendee_tag(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['state' => 'VIC']));

        $orgUpdate = $client->getFirstCallBody('updateOrganisation');
        $this->assertNotNull($orgUpdate);
        $this->assertContains('2027 VIC TS Attendee', $orgUpdate['salesEvents2025']);
    }

    public function test_contact_gets_2026_state_ts_attendee_tag(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['state' => 'NSW']));

        $contactUpdate = $client->getFirstCallBody('updateContactById');
        $this->assertNotNull($contactUpdate);
        $this->assertContains('2026 NSW TS Attendee', $contactUpdate['contactLeadSource']);
    }

    public function test_contact_and_org_tags_differ_by_year(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['state' => 'QLD', 'num_of_students' => 600]));

        $orgUpdate = $client->getFirstCallBody('updateOrganisation');
        $contactUpdate = $client->getFirstCallBody('updateContactById');

        $this->assertContains('2027 QLD TS Attendee', $orgUpdate['salesEvents2025']);
        $this->assertContains('2026 QLD TS Attendee', $contactUpdate['contactLeadSource']);
        $this->assertNotContains('2026 QLD TS Attendee', $orgUpdate['salesEvents2025']);
        $this->assertNotContains('2027 QLD TS Attendee', $contactUpdate['contactLeadSource']);
    }

    // ── Capture step ────────────────────────────────────────────────

    public function test_capture_does_not_set_source_form(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest());

        $capture = $client->getFirstCallBody('captureCustomerInfo');
        $this->assertNotNull($capture);
        $this->assertArrayNotHasKey('sourceForm', $capture);
    }

    public function test_capture_sends_school_name_and_state(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest([
            'school_name' => 'Brisbane State High School',
            'state' => 'QLD',
        ]));

        $capture = $client->getFirstCallBody('captureCustomerInfo');
        $this->assertSame('Brisbane State High School', $capture['organisationName']);
        $this->assertSame('QLD', $capture['state']);
    }

    public function test_capture_includes_num_of_students(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 850]));

        $capture = $client->getFirstCallBody('captureCustomerInfo');
        $this->assertSame(850, $capture['organisationNumOfStudents']);
    }

    // ── 500+ branch: deal creation ──────────────────────────────────

    public function test_large_school_creates_deal(): void
    {
        // Stub keeps org assignee at LAURA — a non-SPM, so isNewSchool() is true.
        $client = $this->makeClient(orgAssigneeAfterUpdate: UserIds::LAURA);
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 600]));

        $this->assertTrue($client->wasCalled('getOrCreateDeal'));
    }

    public function test_large_school_deal_uses_school_enquiry_shape(): void
    {
        $client = $this->makeClient(orgAssigneeAfterUpdate: UserIds::LAURA);
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 1677, 'state' => 'VIC']));

        $deal = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertSame('2027 School Partnership Program', $deal['dealName']);
        $this->assertSame('New', $deal['dealStage']);
        $this->assertSame('New Schools', $deal['dealPipeline']);
        // +1 week from "now" — just assert the shape is dd/mm/yyyy.
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4}$#', $deal['dealCloseDate']);
        $this->assertSame('VIC', $deal['dealState']);
        $this->assertSame('1677', $deal['dealNumOfParticipants']);
    }

    public function test_large_school_skips_deal_when_school_has_dedicated_spm(): void
    {
        // EMMA is a real SPM (not in isNewSchool's nonSpm list) — deal should be skipped.
        $client = $this->makeClient(orgAssigneeAfterUpdate: UserIds::EMMA);
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 1200]));

        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    public function test_large_school_first_attendee_links_contact_to_deal(): void
    {
        // No prior TS Attendee tag on the org → first attendee → contactId set.
        $client = $this->makeClient(orgAssigneeAfterUpdate: UserIds::LAURA);
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(salesEvents: ''),
        );
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 800, 'state' => 'VIC']));

        $deal = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertNotNull($deal);
        $this->assertArrayHasKey('contactId', $deal);
        $this->assertNotSame('', $deal['contactId']);
    }

    public function test_large_school_subsequent_attendee_omits_contact_from_deal(): void
    {
        // Org already has the "2027 VIC TS Attendee" tag → a prior attendee
        // from this school has been processed → no single contact represents
        // the school, so contactId must be omitted from the deal payload.
        $client = $this->makeClient(orgAssigneeAfterUpdate: UserIds::LAURA);
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(
                salesEvents: '2027 VIC TS Attendee',
            ),
        );
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 800, 'state' => 'VIC']));

        $deal = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertNotNull($deal);
        $this->assertArrayNotHasKey('contactId', $deal);
    }

    public function test_large_school_unrelated_existing_tag_still_links_contact(): void
    {
        // Org has a different sales-event tag (not a prior TS Attendee for this
        // state) → should still be treated as the first attendee.
        $client = $this->makeClient(orgAssigneeAfterUpdate: UserIds::LAURA);
        $client->setResponse(
            'getOrgDetails',
            StubVtigerWebhookClient::makeOrgDetailsResponse(
                salesEvents: '2027 NSW TS Attendee |##| 2026 More Info',
            ),
        );
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 800, 'state' => 'VIC']));

        $deal = $client->getFirstCallBody('getOrCreateDeal');
        $this->assertNotNull($deal);
        $this->assertArrayHasKey('contactId', $deal);
    }

    public function test_large_school_does_not_register_for_event(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 1000]));

        $this->assertFalse($client->wasCalled('getEventDetails'));
        $this->assertFalse($client->wasCalled('registerContact'));
    }

    // ── Sub-500 branch: event registration + lead nurture ───────────

    public function test_small_school_registers_for_event(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 200]));

        $this->assertTrue($client->wasCalled('getEventDetails'));
        $this->assertTrue($client->wasCalled('registerContact'));
    }

    public function test_small_school_uses_configured_event_id(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 200]));

        $eventLookup = $client->getFirstCallBody('getEventDetails');
        $registration = $client->getFirstCallBody('registerContact');
        $this->assertSame(self::TEST_EVENT_ID, $eventLookup['eventId']);
        $this->assertSame(self::TEST_EVENT_ID, $registration['eventId']);
    }

    public function test_small_school_skips_registration_when_already_registered(): void
    {
        $client = $this->makeClient();
        $client->setResponse('checkContactRegisteredForEvent', (object) [
            'result' => [(object) ['id' => '17x999']],
        ]);
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 200]));

        $this->assertFalse($client->wasCalled('registerContact'));
    }

    public function test_small_school_marks_contact_lead_hot(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 200]));

        // First updateContactById call writes the tag; the lifecycle update is the second.
        $contactUpdates = $client->getCallsTo('updateContactById');
        $this->assertCount(2, $contactUpdates);
        $this->assertSame('Lead', $contactUpdates[1]['body']['lifecycleStage']);
        $this->assertSame('Hot', $contactUpdates[1]['body']['contactStatus']);
    }

    public function test_small_school_does_not_create_deal(): void
    {
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $handler->handle($this->makeRequest(['num_of_students' => 200]));

        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
    }

    public function test_missing_student_count_falls_into_small_school_path(): void
    {
        // Defensive: prep step should always populate this, but if it's missing
        // we want lead-nurture (event reg) over deal creation.
        $client = $this->makeClient();
        $handler = $this->makeHandler($client);

        $request = TsAttendeeRequest::fromFormData([
            'contact_email' => 'attendee@school.edu.au',
            'contact_first_name' => 'Alice',
            'contact_last_name' => 'Nguyen',
            'school_name' => 'Some School',
            'state' => 'VIC',
            // num_of_students intentionally omitted
        ]);
        $handler->handle($request);

        $this->assertFalse($client->wasCalled('getOrCreateDeal'));
        $this->assertTrue($client->wasCalled('registerContact'));
    }

    // ── Tag template override ───────────────────────────────────────

    public function test_custom_tag_templates_are_respected(): void
    {
        $client = $this->makeClient();
        $handler = new SubmitTsAttendeeHandler(
            $client,
            contactTagTemplate: 'SHANNON TEST',
            orgTagTemplate: 'SHANNON TEST',
            eventId: self::TEST_EVENT_ID,
        );

        $handler->handle($this->makeRequest(['state' => 'VIC']));

        $orgUpdate = $client->getFirstCallBody('updateOrganisation');
        $contactUpdate = $client->getFirstCallBody('updateContactById');

        $this->assertContains('SHANNON TEST', $orgUpdate['salesEvents2025']);
        $this->assertContains('SHANNON TEST', $contactUpdate['contactLeadSource']);
    }
}
