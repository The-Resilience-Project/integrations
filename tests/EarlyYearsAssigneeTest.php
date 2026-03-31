<?php

use PHPUnit\Framework\TestCase;

class EarlyYearsAssigneeTest extends TestCase
{
    private function createController(array $data, array $orgDetails): EarlyYearsVTController
    {
        $controller = new EarlyYearsVTController($data);
        $reflection = new ReflectionClass($controller);

        $prop = $reflection->getProperty('organisation_details');
        $prop->setValue($controller, $orgDetails);

        return $controller;
    }

    private function callProtected(object $obj, string $method): mixed
    {
        $reflection = new ReflectionMethod($obj, $method);
        return $reflection->invoke($obj);
    }

    public function test_enquiry_assignee_always_brendan(): void
    {
        $controller = $this->createController([], ['assigned_user_id' => '19x1']);
        $this->assertSame('19x57', $this->callProtected($controller, 'get_enquiry_assignee'));
    }

    public function test_contact_assignee_returns_org_assignee_when_not_maddie(): void
    {
        $controller = $this->createController([], ['assigned_user_id' => '19x33']); // VICTOR
        $this->assertSame('19x33', $this->callProtected($controller, 'get_contact_assignee'));
    }

    public function test_contact_assignee_returns_brendan_when_maddie(): void
    {
        $controller = $this->createController([], ['assigned_user_id' => '19x1']); // MADDIE
        $this->assertSame('19x57', $this->callProtected($controller, 'get_contact_assignee'));
    }

    public function test_org_assignee_returns_org_assignee_when_not_maddie(): void
    {
        $controller = $this->createController([], ['assigned_user_id' => '19x15']); // EMMA
        $this->assertSame('19x15', $this->callProtected($controller, 'get_org_assignee'));
    }

    public function test_org_assignee_returns_brendan_when_maddie(): void
    {
        $controller = $this->createController([], ['assigned_user_id' => '19x1']);
        $this->assertSame('19x57', $this->callProtected($controller, 'get_org_assignee'));
    }
}
