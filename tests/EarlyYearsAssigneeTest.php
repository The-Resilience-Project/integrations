<?php

use PHPUnit\Framework\TestCase;

class EarlyYearsAssigneeTest extends TestCase
{
    private function makeController(?string $org_assignee): EarlyYearsVTController
    {
        $controller = new EarlyYearsVTController([]);

        $prop = new ReflectionProperty($controller, 'organisation_details');
        $prop->setValue($controller, ['assigned_user_id' => $org_assignee]);

        return $controller;
    }

    public function test_enquiry_assignee_always_brendan(): void
    {
        $controller = $this->makeController('19x99');
        $method = new ReflectionMethod($controller, 'get_enquiry_assignee');

        $this->assertEquals('19x57', $method->invoke($controller)); // BRENDAN
    }

    public function test_contact_assignee_returns_org_assignee_when_not_maddie(): void
    {
        $controller = $this->makeController('19x99');
        $method = new ReflectionMethod($controller, 'get_contact_assignee');

        $this->assertEquals('19x99', $method->invoke($controller));
    }

    public function test_contact_assignee_returns_brendan_when_maddie(): void
    {
        $controller = $this->makeController('19x1'); // MADDIE
        $method = new ReflectionMethod($controller, 'get_contact_assignee');

        $this->assertEquals('19x57', $method->invoke($controller)); // BRENDAN
    }

    public function test_org_assignee_returns_brendan_when_maddie(): void
    {
        $controller = $this->makeController('19x1');
        $method = new ReflectionMethod($controller, 'get_org_assignee');

        $this->assertEquals('19x57', $method->invoke($controller));
    }
}
