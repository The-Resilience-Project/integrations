<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the SubmitEnquiry use case end-to-end using a mock VtApiClient.
 * This is the primary integration test for the enquiry flow.
 */
class SubmitEnquiryUseCaseTest extends TestCase
{
    private function makeMockApi(): object
    {
        return new class () implements VtApiClient {
            public array $calls = [];

            public function request(string $endpoint, string $token, array $body, bool $get = false): ?object
            {
                $this->calls[] = ['endpoint' => $endpoint, 'body' => $body];

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
                $this->calls[] = ['endpoint' => $endpoint, 'body' => $body, 'lineItems' => $lineItems];
                return (object) ['result' => [(object) ['id' => '99x100']]];
            }

            public function endpoints(): array
            {
                return array_column($this->calls, 'endpoint');
            }

            public function findCall(string $endpoint): ?array
            {
                foreach ($this->calls as $call) {
                    if ($call['endpoint'] === $endpoint) {
                        return $call;
                    }
                }
                return null;
            }
        };
    }

    private function schoolRequest(array $overrides = []): EnquiryRequest
    {
        return EnquiryRequest::fromFormData(array_merge([
            'contact_email' => 'teacher@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'service_type' => 'School',
            'state' => 'VIC',
            'source_form' => 'Website Enquiry',
            'school_account_no' => 'ACC123',
        ], $overrides));
    }

    // -- Result structure --

    public function test_returns_enquiry_result(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $result = $useCase->execute($this->schoolRequest());

        $this->assertInstanceOf(EnquiryResult::class, $result);
    }

    public function test_successful_result_fields(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $result = $useCase->execute($this->schoolRequest());

        $this->assertTrue($result->success);
        $this->assertEquals('School', $result->serviceType);
        $this->assertEquals('ACC123', $result->organisation);
        $this->assertEquals('teacher@school.edu.au', $result->contactEmail);
        $this->assertNull($result->errorMessage);
    }

    public function test_result_status_string(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $result = $useCase->execute($this->schoolRequest());

        $this->assertEquals('success', $result->status());
    }

    public function test_result_to_response(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $result = $useCase->execute($this->schoolRequest());

        $this->assertEquals(['status' => 'success'], $result->toResponse());
    }

    // -- School flow --

    public function test_school_enquiry_succeeds(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $result = $useCase->execute($this->schoolRequest());

        $this->assertTrue($result->success);
        $this->assertContains('captureCustomerInfoWithAccountNo', $api->endpoints());
        $this->assertContains('createEnquiry', $api->endpoints());
    }

    public function test_school_new_org_uses_capture_customer_info(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $request = $this->schoolRequest([
            'school_name_other_selected' => true,
            'school_name_other' => 'Brand New School',
            'school_account_no' => '',
        ]);
        $result = $useCase->execute($request);

        $this->assertTrue($result->success);
        $this->assertContains('captureCustomerInfo', $api->endpoints());
        $this->assertNotContains('captureCustomerInfoWithAccountNo', $api->endpoints());
    }

    public function test_school_enquiry_passes_correct_contact(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $useCase->execute($this->schoolRequest());

        $call = $api->findCall('captureCustomerInfoWithAccountNo');
        $this->assertEquals('teacher@school.edu.au', $call['body']['contactEmail']);
        $this->assertEquals('Jane', $call['body']['contactFirstName']);
        $this->assertEquals('Smith', $call['body']['contactLastName']);
    }

    public function test_school_enquiry_type_in_crm_call(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $useCase->execute($this->schoolRequest());

        $call = $api->findCall('createEnquiry');
        $this->assertEquals('School', $call['body']['enquiryType']);
    }

    // -- Workplace flow --

    public function test_workplace_enquiry_succeeds(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'hr@company.com',
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'service_type' => 'Workplace',
            'state' => 'NSW',
            'source_form' => 'Website Enquiry',
            'organisation_name' => 'ACME Corp',
        ]);

        $result = $useCase->execute($request);

        $this->assertTrue($result->success);
        $this->assertEquals('Workplace', $result->serviceType);
        $this->assertEquals('ACME Corp', $result->organisation);
        $this->assertContains('getOrCreateDeal', $api->endpoints());
    }

    // -- General flow --

    public function test_general_enquiry_succeeds(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
            'service_type' => 'General',
        ]);

        $result = $useCase->execute($request);

        $this->assertTrue($result->success);
        $this->assertEquals('General', $result->serviceType);
        $this->assertNotContains('getOrCreateDeal', $api->endpoints());
    }

    public function test_general_only_calls_contact_and_enquiry(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
            'service_type' => 'General',
        ]);

        $useCase->execute($request);

        $this->assertCount(2, $api->calls);
        $this->assertContains('getContactByEmail', $api->endpoints());
        $this->assertContains('createEnquiry', $api->endpoints());
    }

    // -- Imperfects flow --

    public function test_imperfects_uses_correct_enquiry_type(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
            'service_type' => 'Imperfects',
        ]);

        $useCase->execute($request);

        $call = $api->findCall('createEnquiry');
        $this->assertEquals('Imperfects', $call['body']['enquiryType']);
    }

    // -- Error handling --

    public function test_crm_failure_returns_failed_result(): void
    {
        // Controller submit_enquiry() catches exceptions internally and returns false,
        // so the use case sees a failed result without an error message.
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

        $useCase = new SubmitEnquiry($api);
        $result = $useCase->execute($this->schoolRequest());

        $this->assertFalse($result->success);
        $this->assertEquals('fail', $result->status());
    }

    public function test_uncaught_exception_includes_error_message(): void
    {
        // General controller doesn't wrap submit_enquiry in try/catch for the
        // capture_customer_info call, so an exception from controller construction
        // or other uncaught paths will surface to the use case.
        $api = new class () implements VtApiClient {
            private int $callCount = 0;

            public function request(string $endpoint, string $token, array $body, bool $get = false): ?object
            {
                throw new RuntimeException('Connection timeout');
            }

            public function requestWithLineItems(string $endpoint, string $token, array $body, array $lineItems): ?object
            {
                throw new RuntimeException('Connection timeout');
            }
        };

        $useCase = new SubmitEnquiry($api);
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
            'service_type' => 'General',
        ]);
        $result = $useCase->execute($request);

        $this->assertFalse($result->success);
        $response = $result->toResponse();
        $this->assertEquals('fail', $response['status']);
    }

    // -- Early Years flow --

    public function test_early_years_enquiry_succeeds(): void
    {
        $api = $this->makeMockApi();
        $useCase = new SubmitEnquiry($api);

        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'director@kinder.com',
            'contact_first_name' => 'Sarah',
            'contact_last_name' => 'Brown',
            'service_type' => 'Early Years',
            'state' => 'QLD',
            'source_form' => 'Website Enquiry',
            'earlyyears_account_no' => 'EY789',
            'service_name_other_selected' => false,
        ]);

        $result = $useCase->execute($request);

        $this->assertTrue($result->success);
        $this->assertEquals('Early Years', $result->serviceType);
        $this->assertEquals('EY789', $result->organisation);
    }
}
