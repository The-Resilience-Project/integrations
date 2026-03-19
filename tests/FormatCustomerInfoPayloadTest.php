<?php

use PHPUnit\Framework\TestCase;

class FormatCustomerInfoPayloadTest extends TestCase
{
    private function makeController(array $data): SchoolVTController
    {
        return new SchoolVTController($data);
    }

    public function test_basic_payload_fields(): void
    {
        $controller = $this->makeController([]);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
        ]);

        $this->assertEquals('test@example.com', $result['contactEmail']);
        $this->assertEquals('Jane', $result['contactFirstName']);
        $this->assertEquals('Doe', $result['contactLastName']);
        $this->assertEquals('School', $result['organisationType']);
    }

    public function test_includes_state_when_present(): void
    {
        $controller = $this->makeController(['state' => 'VIC']);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
        ]);

        $this->assertEquals('VIC', $result['state']);
    }

    public function test_excludes_state_when_missing(): void
    {
        $controller = $this->makeController([]);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
        ]);

        $this->assertArrayNotHasKey('state', $result);
    }

    public function test_includes_optional_contact_fields(): void
    {
        $controller = $this->makeController([]);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
            'contact_phone' => '0412345678',
            'org_phone' => '0398765432',
            'job_title' => 'Principal',
            'contact_type' => 'Primary',
            'contact_newsletter' => 'Yes',
        ]);

        $this->assertEquals('0412345678', $result['contactPhone']);
        $this->assertEquals('0398765432', $result['orgPhone']);
        $this->assertEquals('Principal', $result['jobTitle']);
        $this->assertEquals('Primary', $result['contactType']);
        $this->assertEquals('Yes', $result['newsletter']);
    }

    public function test_excludes_optional_fields_when_not_provided(): void
    {
        $controller = $this->makeController([]);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
        ]);

        $this->assertArrayNotHasKey('contactPhone', $result);
        $this->assertArrayNotHasKey('orgPhone', $result);
        $this->assertArrayNotHasKey('jobTitle', $result);
        $this->assertArrayNotHasKey('contactType', $result);
        $this->assertArrayNotHasKey('newsletter', $result);
    }

    public function test_includes_num_of_students(): void
    {
        $controller = $this->makeController(['num_of_students' => '500']);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
        ]);

        $this->assertEquals('500', $result['organisationNumOfStudents']);
    }

    public function test_includes_num_of_ey_children_as_students(): void
    {
        $controller = $this->makeController(['num_of_ey_children' => '80']);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
        ]);

        $this->assertEquals('80', $result['organisationNumOfStudents']);
    }

    public function test_includes_num_of_employees(): void
    {
        $controller = $this->makeController(['num_of_employees' => '200']);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
        ]);

        $this->assertEquals('200', $result['organisationNumOfEmployees']);
    }

    public function test_workplace_organisation_type(): void
    {
        $controller = new WorkplaceVTController([]);
        $method = new ReflectionMethod($controller, 'format_customer_info_payload');

        $result = $method->invoke($controller, [
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Doe',
        ]);

        $this->assertEquals('Workplace', $result['organisationType']);
    }
}
