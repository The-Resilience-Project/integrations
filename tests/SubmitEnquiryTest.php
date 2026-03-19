<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the full submit_enquiry() flow using a mock VtApiClient,
 * verifying the correct CRM endpoints are called with expected payloads.
 */
class SubmitEnquiryTest extends TestCase
{
    private function makeMockApi(array $responses = []): VtApiClient
    {
        return new class ($responses) implements VtApiClient {
            public array $calls = [];
            private array $responses;

            public function __construct(array $responses)
            {
                $this->responses = $responses;
            }

            public function request(string $endpoint, string $token, array $body, bool $get = false): ?object
            {
                $this->calls[] = [
                    'endpoint' => $endpoint,
                    'body' => $body,
                    'get' => $get,
                ];

                if (isset($this->responses[$endpoint])) {
                    return $this->responses[$endpoint];
                }

                // Default response with common fields
                return (object) [
                    'result' => [
                        (object) [
                            'id' => '4x1000',
                            'account_id' => '3x2000',
                            'accountname' => 'Test Org',
                            'assigned_user_id' => '19x99',
                            'cf_contacts_formscompleted' => '',
                            'firstname' => 'Test',
                            'lastname' => 'User',
                            'cf_accounts_2025salesevents' => '',
                            'cf_accounts_freetravel' => '0',
                            'cf_accounts_yearswithtrp' => '',
                            'cf_accounts_2024inspire' => '',
                            'cf_accounts_2025inspire' => '',
                            'cf_accounts_2025confirmationstatus' => '',
                            'cf_accounts_2024confirmationstatus' => '',
                            'sales_stage' => 'New',
                            'cf_potentials_firstinfosessiondate' => '',
                            'description' => '',
                            'cf_potentials_billingnote' => '',
                        ],
                    ],
                ];
            }

            public function requestWithLineItems(string $endpoint, string $token, array $body, array $lineItems): ?object
            {
                $this->calls[] = [
                    'endpoint' => $endpoint,
                    'body' => $body,
                    'lineItems' => $lineItems,
                ];
                return (object) ['result' => [(object) ['id' => '99x100']]];
            }
        };
    }

    private function schoolData(): array
    {
        return [
            'service_type' => 'School',
            'contact_email' => 'teacher@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'state' => 'VIC',
            'source_form' => 'Website Enquiry',
            'school_account_no' => 'ACC123',
        ];
    }

    // -- School enquiry --

    public function test_school_enquiry_returns_true(): void
    {
        $api = $this->makeMockApi();
        $controller = new SchoolVTController($this->schoolData(), $api);

        $result = $controller->submit_enquiry();

        $this->assertTrue($result);
    }

    public function test_school_enquiry_calls_capture_customer_info(): void
    {
        $api = $this->makeMockApi();
        $controller = new SchoolVTController($this->schoolData(), $api);

        $controller->submit_enquiry();

        $endpoints = array_column($api->calls, 'endpoint');
        $this->assertContains('captureCustomerInfoWithAccountNo', $endpoints);
    }

    public function test_school_enquiry_calls_create_enquiry(): void
    {
        $api = $this->makeMockApi();
        $controller = new SchoolVTController($this->schoolData(), $api);

        $controller->submit_enquiry();

        $endpoints = array_column($api->calls, 'endpoint');
        $this->assertContains('createEnquiry', $endpoints);
    }

    public function test_school_enquiry_passes_correct_contact_data(): void
    {
        $api = $this->makeMockApi();
        $controller = new SchoolVTController($this->schoolData(), $api);

        $controller->submit_enquiry();

        // Find the captureCustomerInfoWithAccountNo call
        $capture_call = null;
        foreach ($api->calls as $call) {
            if ($call['endpoint'] === 'captureCustomerInfoWithAccountNo') {
                $capture_call = $call;
                break;
            }
        }

        $this->assertNotNull($capture_call);
        $this->assertEquals('teacher@school.edu.au', $capture_call['body']['contactEmail']);
        $this->assertEquals('Jane', $capture_call['body']['contactFirstName']);
        $this->assertEquals('Smith', $capture_call['body']['contactLastName']);
        $this->assertEquals('ACC123', $capture_call['body']['organisationAccountNo']);
    }

    public function test_school_enquiry_creates_deal_for_new_school(): void
    {
        // MADDIE (19x1) is in the not_spms list, so is_new_school() returns true.
        // We override all default responses to return MADDIE as the assignee so
        // the org details reflect a new (non-SPM-managed) school.
        $maddieOrg = (object) [
            'accountname' => 'New School',
            'assigned_user_id' => '19x1',
            'cf_accounts_2025salesevents' => '',
            'cf_accounts_freetravel' => '0',
            'cf_accounts_yearswithtrp' => '',
            'cf_accounts_2024inspire' => '',
            'cf_accounts_2025inspire' => '',
            'cf_accounts_2025confirmationstatus' => '',
            'cf_accounts_2024confirmationstatus' => '',
        ];
        $maddieResponse = (object) [
            'id' => '4x1000',
            'account_id' => '3x2000',
            'assigned_user_id' => '19x1',
            'cf_contacts_formscompleted' => '',
            'firstname' => 'Test',
            'lastname' => 'User',
        ];
        $api = $this->makeMockApi([
            'getOrgDetails' => (object) ['result' => [$maddieOrg]],
            'captureCustomerInfoWithAccountNo' => (object) ['result' => [$maddieResponse]],
            'updateOrganisation' => (object) ['result' => [$maddieOrg]],
        ]);
        $controller = new SchoolVTController($this->schoolData(), $api);

        $controller->submit_enquiry();

        $endpoints = array_column($api->calls, 'endpoint');
        $this->assertContains('getOrCreateDeal', $endpoints);
    }

    public function test_school_enquiry_skips_deal_for_existing_school(): void
    {
        $api = $this->makeMockApi([
            'getOrgDetails' => (object) [
                'result' => [
                    (object) [
                        'accountname' => 'Existing School',
                        'assigned_user_id' => '19x99', // SPM = existing school
                        'cf_accounts_2025salesevents' => '',
                        'cf_accounts_freetravel' => '0',
                        'cf_accounts_yearswithtrp' => '',
                        'cf_accounts_2024inspire' => '',
                        'cf_accounts_2025inspire' => '',
                        'cf_accounts_2025confirmationstatus' => '',
                        'cf_accounts_2024confirmationstatus' => '',
                    ],
                ],
            ],
        ]);
        $controller = new SchoolVTController($this->schoolData(), $api);

        $controller->submit_enquiry();

        $endpoints = array_column($api->calls, 'endpoint');
        $this->assertNotContains('getOrCreateDeal', $endpoints);
    }

    public function test_school_new_org_uses_capture_customer_info(): void
    {
        $data = $this->schoolData();
        unset($data['school_account_no']);
        $data['school_name_other_selected'] = true;
        $data['school_name_other'] = 'Brand New School';

        $api = $this->makeMockApi();
        $controller = new SchoolVTController($data, $api);

        $controller->submit_enquiry();

        $endpoints = array_column($api->calls, 'endpoint');
        $this->assertContains('captureCustomerInfo', $endpoints);
        $this->assertNotContains('captureCustomerInfoWithAccountNo', $endpoints);
    }

    // -- Workplace enquiry --

    public function test_workplace_enquiry_returns_true(): void
    {
        $api = $this->makeMockApi();
        $data = [
            'service_type' => 'Workplace',
            'contact_email' => 'hr@company.com',
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'state' => 'NSW',
            'source_form' => 'Website Enquiry',
            'organisation_name' => 'ACME Corp',
        ];
        $controller = new WorkplaceVTController($data, $api);

        $result = $controller->submit_enquiry();

        $this->assertTrue($result);
    }

    public function test_workplace_enquiry_always_creates_deal(): void
    {
        $api = $this->makeMockApi();
        $data = [
            'service_type' => 'Workplace',
            'contact_email' => 'hr@company.com',
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'state' => 'NSW',
            'source_form' => 'Website Enquiry',
            'organisation_name' => 'ACME Corp',
        ];
        $controller = new WorkplaceVTController($data, $api);

        $controller->submit_enquiry();

        $endpoints = array_column($api->calls, 'endpoint');
        $this->assertContains('getOrCreateDeal', $endpoints);
    }

    // -- General enquiry --

    public function test_general_enquiry_returns_true(): void
    {
        $api = $this->makeMockApi();
        $data = [
            'service_type' => 'General',
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
        ];
        $controller = new GeneralVTController($data, $api);

        $result = $controller->submit_enquiry();

        $this->assertTrue($result);
    }

    public function test_general_enquiry_does_not_create_deal(): void
    {
        $api = $this->makeMockApi();
        $data = [
            'service_type' => 'General',
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
        ];
        $controller = new GeneralVTController($data, $api);

        $controller->submit_enquiry();

        $endpoints = array_column($api->calls, 'endpoint');
        $this->assertNotContains('getOrCreateDeal', $endpoints);
    }

    public function test_general_enquiry_only_calls_contact_lookup_and_create(): void
    {
        $api = $this->makeMockApi();
        $data = [
            'service_type' => 'General',
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
        ];
        $controller = new GeneralVTController($data, $api);

        $controller->submit_enquiry();

        $endpoints = array_column($api->calls, 'endpoint');
        $this->assertContains('getContactByEmail', $endpoints);
        $this->assertContains('createEnquiry', $endpoints);
        $this->assertCount(2, $api->calls);
    }

    // -- Error handling --

    public function test_submit_enquiry_returns_false_on_exception(): void
    {
        $api = new class () implements VtApiClient {
            public function request(string $endpoint, string $token, array $body, bool $get = false): ?object
            {
                throw new Exception('CRM is down');
            }
            public function requestWithLineItems(string $endpoint, string $token, array $body, array $lineItems): ?object
            {
                throw new Exception('CRM is down');
            }
        };

        $controller = new SchoolVTController($this->schoolData(), $api);

        $result = $controller->submit_enquiry();

        $this->assertFalse($result);
    }

    // -- Enquiry type and assignee in createEnquiry payload --

    public function test_school_enquiry_type_is_school(): void
    {
        $api = $this->makeMockApi();
        $controller = new SchoolVTController($this->schoolData(), $api);

        $controller->submit_enquiry();

        $enquiry_call = null;
        foreach ($api->calls as $call) {
            if ($call['endpoint'] === 'createEnquiry') {
                $enquiry_call = $call;
            }
        }

        $this->assertNotNull($enquiry_call);
        $this->assertEquals('School', $enquiry_call['body']['enquiryType']);
    }

    public function test_general_enquiry_type_is_general(): void
    {
        $api = $this->makeMockApi();
        $data = [
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
        ];
        $controller = new GeneralVTController($data, $api);

        $controller->submit_enquiry();

        $enquiry_call = null;
        foreach ($api->calls as $call) {
            if ($call['endpoint'] === 'createEnquiry') {
                $enquiry_call = $call;
            }
        }

        $this->assertEquals('General', $enquiry_call['body']['enquiryType']);
    }

    public function test_imperfects_enquiry_type(): void
    {
        $api = $this->makeMockApi();
        $data = [
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
        ];
        $controller = new ImperfectsVTController($data, $api);

        $controller->submit_enquiry();

        $enquiry_call = null;
        foreach ($api->calls as $call) {
            if ($call['endpoint'] === 'createEnquiry') {
                $enquiry_call = $call;
            }
        }

        $this->assertEquals('Imperfects', $enquiry_call['body']['enquiryType']);
    }
}
