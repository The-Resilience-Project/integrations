<?php

use PHPUnit\Framework\TestCase;

class GeneralAssigneeTest extends TestCase
{
    public function test_enquiry_assignee_returns_ashlee(): void
    {
        $controller = new GeneralVTController([]);
        $method = new ReflectionMethod($controller, 'get_enquiry_assignee');

        $this->assertEquals('19x29', $method->invoke($controller)); // ASHLEE
    }

    public function test_contact_assignee_returns_maddie(): void
    {
        $controller = new GeneralVTController([]);
        $method = new ReflectionMethod($controller, 'get_contact_assignee');

        $this->assertEquals('19x1', $method->invoke($controller)); // MADDIE
    }

    public function test_org_assignee_returns_maddie(): void
    {
        $controller = new GeneralVTController([]);
        $method = new ReflectionMethod($controller, 'get_org_assignee');

        $this->assertEquals('19x1', $method->invoke($controller)); // MADDIE
    }

    public function test_imperfects_enquiry_type(): void
    {
        $controller = new ImperfectsVTController([]);
        $prop = new ReflectionProperty($controller, 'enquiry_type');

        $this->assertEquals('Imperfects', $prop->getValue($controller));
    }
}
