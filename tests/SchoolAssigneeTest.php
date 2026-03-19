<?php

use PHPUnit\Framework\TestCase;

class SchoolAssigneeTest extends TestCase
{
    private function makeController(array $data, ?string $org_assignee): SchoolVTController
    {
        $controller = new SchoolVTController($data);

        // Set organisation_details via reflection
        $prop = new ReflectionProperty($controller, 'organisation_details');
        $prop->setValue($controller, ['assigned_user_id' => $org_assignee]);

        return $controller;
    }

    // -- get_enquiry_assignee --

    public function test_enquiry_assignee_returns_laura_when_org_assignee_null(): void
    {
        $controller = $this->makeController(['state' => 'VIC'], null);
        $method = new ReflectionMethod($controller, 'get_enquiry_assignee');

        $this->assertEquals('19x8', $method->invoke($controller)); // LAURA
    }

    public function test_enquiry_assignee_returns_org_assignee_when_not_maddie(): void
    {
        $controller = $this->makeController(['state' => 'VIC'], '19x99');
        $method = new ReflectionMethod($controller, 'get_enquiry_assignee');

        $this->assertEquals('19x99', $method->invoke($controller));
    }

    public function test_enquiry_assignee_returns_brendan_for_nsw_when_maddie(): void
    {
        $controller = $this->makeController(['state' => 'NSW'], '19x1'); // MADDIE
        $method = new ReflectionMethod($controller, 'get_enquiry_assignee');

        $this->assertEquals('19x57', $method->invoke($controller)); // BRENDAN
    }

    public function test_enquiry_assignee_returns_brendan_for_qld_when_maddie(): void
    {
        $controller = $this->makeController(['state' => 'QLD'], '19x1');
        $method = new ReflectionMethod($controller, 'get_enquiry_assignee');

        $this->assertEquals('19x57', $method->invoke($controller));
    }

    public function test_enquiry_assignee_returns_laura_for_vic_when_maddie(): void
    {
        $controller = $this->makeController(['state' => 'VIC'], '19x1');
        $method = new ReflectionMethod($controller, 'get_enquiry_assignee');

        $this->assertEquals('19x8', $method->invoke($controller)); // LAURA
    }

    public function test_enquiry_assignee_returns_laura_for_sa_when_maddie(): void
    {
        $controller = $this->makeController(['state' => 'SA'], '19x1');
        $method = new ReflectionMethod($controller, 'get_enquiry_assignee');

        $this->assertEquals('19x8', $method->invoke($controller)); // LAURA
    }

    // -- get_contact_assignee / get_org_assignee (both delegate to get_assignee_by_state) --

    public function test_contact_assignee_returns_org_assignee_when_not_maddie(): void
    {
        $controller = $this->makeController(['state' => 'VIC'], '19x99');
        $method = new ReflectionMethod($controller, 'get_contact_assignee');

        $this->assertEquals('19x99', $method->invoke($controller));
    }

    public function test_contact_assignee_returns_brendan_for_nsw_when_maddie(): void
    {
        $controller = $this->makeController(['state' => 'NSW'], '19x1');
        $method = new ReflectionMethod($controller, 'get_contact_assignee');

        $this->assertEquals('19x57', $method->invoke($controller));
    }

    public function test_contact_assignee_returns_laura_for_vic_when_maddie(): void
    {
        $controller = $this->makeController(['state' => 'VIC'], '19x1');
        $method = new ReflectionMethod($controller, 'get_contact_assignee');

        $this->assertEquals('19x8', $method->invoke($controller));
    }

    public function test_org_assignee_matches_contact_assignee(): void
    {
        $controller = $this->makeController(['state' => 'NSW'], '19x1');
        $contact_method = new ReflectionMethod($controller, 'get_contact_assignee');
        $org_method = new ReflectionMethod($controller, 'get_org_assignee');

        $this->assertEquals(
            $contact_method->invoke($controller),
            $org_method->invoke($controller)
        );
    }

    // -- is_new_school --

    public function test_is_new_school_true_for_maddie(): void
    {
        $controller = $this->makeController([], '19x1'); // MADDIE
        $method = new ReflectionMethod($controller, 'is_new_school');

        $this->assertTrue($method->invoke($controller));
    }

    public function test_is_new_school_true_for_laura(): void
    {
        $controller = $this->makeController([], '19x8'); // LAURA
        $method = new ReflectionMethod($controller, 'is_new_school');

        $this->assertTrue($method->invoke($controller));
    }

    public function test_is_new_school_true_for_brendan(): void
    {
        $controller = $this->makeController([], '19x57'); // BRENDAN
        $method = new ReflectionMethod($controller, 'is_new_school');

        $this->assertTrue($method->invoke($controller));
    }

    public function test_is_new_school_false_for_spm(): void
    {
        $controller = $this->makeController([], '19x99'); // Unknown SPM
        $method = new ReflectionMethod($controller, 'is_new_school');

        $this->assertFalse($method->invoke($controller));
    }

    // -- get_registration_reply_to --

    public function test_registration_reply_to_brendan_for_nsw(): void
    {
        $controller = $this->makeController(['state' => 'NSW'], null);
        $method = new ReflectionMethod($controller, 'get_registration_reply_to');

        $this->assertEquals('19x57', $method->invoke($controller));
    }

    public function test_registration_reply_to_laura_for_vic(): void
    {
        $controller = $this->makeController(['state' => 'VIC'], null);
        $method = new ReflectionMethod($controller, 'get_registration_reply_to');

        $this->assertEquals('19x8', $method->invoke($controller));
    }
}
