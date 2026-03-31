<?php

use PHPUnit\Framework\TestCase;

class GeneralAssigneeTest extends TestCase
{
    private function callProtected(object $obj, string $method): mixed
    {
        $reflection = new ReflectionMethod($obj, $method);
        return $reflection->invoke($obj);
    }

    public function test_enquiry_assignee_always_ashlee(): void
    {
        $controller = new GeneralVTController([]);
        $this->assertSame('19x29', $this->callProtected($controller, 'get_enquiry_assignee'));
    }

    public function test_contact_assignee_always_maddie(): void
    {
        $controller = new GeneralVTController([]);
        $this->assertSame('19x1', $this->callProtected($controller, 'get_contact_assignee'));
    }

    public function test_org_assignee_always_maddie(): void
    {
        $controller = new GeneralVTController([]);
        $this->assertSame('19x1', $this->callProtected($controller, 'get_org_assignee'));
    }

    public function test_imperfects_enquiry_type(): void
    {
        $controller = new ImperfectsVTController([]);
        $reflection = new ReflectionClass($controller);
        $prop = $reflection->getProperty('enquiry_type');
        $this->assertSame('Imperfects', $prop->getValue($controller));
    }
}
