<?php

use PHPUnit\Framework\TestCase;

class SchoolAssigneeTest extends TestCase
{
    private function createController(array $data, array $orgDetails): SchoolVTController
    {
        $controller = new SchoolVTController($data);
        $reflection = new ReflectionClass($controller);

        $prop = $reflection->getProperty('organisation_details');
        $prop->setValue($controller, $orgDetails);

        return $controller;
    }

    private function callProtected(object $obj, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($obj, $method);
        return $reflection->invoke($obj, ...$args);
    }

    // --- get_enquiry_assignee ---

    public function test_enquiry_assignee_returns_laura_when_org_assignee_is_null(): void
    {
        $controller = $this->createController(
            ['state' => 'VIC'],
            ['assigned_user_id' => null]
        );

        $result = $this->callProtected($controller, 'get_enquiry_assignee');
        $this->assertSame('19x8', $result); // LAURA
    }

    public function test_enquiry_assignee_returns_org_assignee_when_not_maddie(): void
    {
        $controller = $this->createController(
            ['state' => 'VIC'],
            ['assigned_user_id' => '19x33'] // VICTOR
        );

        $result = $this->callProtected($controller, 'get_enquiry_assignee');
        $this->assertSame('19x33', $result);
    }

    public function test_enquiry_assignee_returns_brendan_for_nsw_when_maddie(): void
    {
        $controller = $this->createController(
            ['state' => 'NSW'],
            ['assigned_user_id' => '19x1'] // MADDIE
        );

        $result = $this->callProtected($controller, 'get_enquiry_assignee');
        $this->assertSame('19x57', $result); // BRENDAN
    }

    public function test_enquiry_assignee_returns_brendan_for_qld_when_maddie(): void
    {
        $controller = $this->createController(
            ['state' => 'QLD'],
            ['assigned_user_id' => '19x1'] // MADDIE
        );

        $result = $this->callProtected($controller, 'get_enquiry_assignee');
        $this->assertSame('19x57', $result); // BRENDAN
    }

    public function test_enquiry_assignee_returns_laura_for_vic_when_maddie(): void
    {
        $controller = $this->createController(
            ['state' => 'VIC'],
            ['assigned_user_id' => '19x1'] // MADDIE
        );

        $result = $this->callProtected($controller, 'get_enquiry_assignee');
        $this->assertSame('19x8', $result); // LAURA
    }

    // --- get_contact_assignee ---

    public function test_contact_assignee_returns_org_assignee_when_not_maddie(): void
    {
        $controller = $this->createController(
            ['state' => 'VIC'],
            ['assigned_user_id' => '19x15'] // EMMA
        );

        $result = $this->callProtected($controller, 'get_contact_assignee');
        $this->assertSame('19x15', $result);
    }

    public function test_contact_assignee_routes_by_state_when_maddie(): void
    {
        $nsw = $this->createController(['state' => 'NSW'], ['assigned_user_id' => '19x1']);
        $vic = $this->createController(['state' => 'VIC'], ['assigned_user_id' => '19x1']);

        $this->assertSame('19x57', $this->callProtected($nsw, 'get_contact_assignee')); // BRENDAN
        $this->assertSame('19x8', $this->callProtected($vic, 'get_contact_assignee')); // LAURA
    }

    // --- is_new_school ---

    public function test_is_new_school_returns_true_for_known_assignees(): void
    {
        $knownIds = ['19x1', '19x8', '19x33', '19x24', '19x57']; // MADDIE, LAURA, VICTOR, HELENOR, BRENDAN
        foreach ($knownIds as $id) {
            $controller = $this->createController([], ['assigned_user_id' => $id]);
            $this->assertTrue(
                $this->callProtected($controller, 'is_new_school'),
                "Expected is_new_school=true for assignee $id"
            );
        }
    }

    public function test_is_new_school_returns_false_for_spm(): void
    {
        $controller = $this->createController([], ['assigned_user_id' => '19x99']);
        $this->assertFalse($this->callProtected($controller, 'is_new_school'));
    }

    // --- get_registration_reply_to ---

    public function test_registration_reply_to_brendan_for_nsw(): void
    {
        $controller = $this->createController(['state' => 'NSW'], ['assigned_user_id' => '19x1']);
        $this->assertSame('19x57', $this->callProtected($controller, 'get_registration_reply_to'));
    }

    public function test_registration_reply_to_brendan_for_qld(): void
    {
        $controller = $this->createController(['state' => 'QLD'], ['assigned_user_id' => '19x1']);
        $this->assertSame('19x57', $this->callProtected($controller, 'get_registration_reply_to'));
    }

    public function test_registration_reply_to_laura_for_other_states(): void
    {
        foreach (['VIC', 'SA', 'WA', 'TAS', 'NT', 'ACT'] as $state) {
            $controller = $this->createController(['state' => $state], ['assigned_user_id' => '19x1']);
            $this->assertSame(
                '19x8',
                $this->callProtected($controller, 'get_registration_reply_to'),
                "Expected LAURA for state $state"
            );
        }
    }

    // --- get_quote_stage (ExistingSchoolVTController) ---

    public function test_existing_school_quote_stage_delivered_when_free_travel(): void
    {
        $controller = new ExistingSchoolVTController([]);
        $reflection = new ReflectionClass($controller);

        $orgProp = $reflection->getProperty('organisation_details');
        $orgProp->setValue($controller, ['cf_accounts_freetravel' => '1']);

        $extendProp = $reflection->getProperty('extend');
        $extendProp->setValue($controller, []);

        $this->assertSame('Delivered', $this->callProtected($controller, 'get_quote_stage'));
    }

    public function test_existing_school_quote_stage_new_when_workshops(): void
    {
        $controller = new ExistingSchoolVTController([]);
        $reflection = new ReflectionClass($controller);

        $orgProp = $reflection->getProperty('organisation_details');
        $orgProp->setValue($controller, ['cf_accounts_freetravel' => '0']);

        $extendProp = $reflection->getProperty('extend');
        $extendProp->setValue($controller, ['Wellbeing Workshop 1 (Self)']);

        $this->assertSame('New', $this->callProtected($controller, 'get_quote_stage'));
    }

    public function test_existing_school_quote_stage_delivered_when_webinars_only(): void
    {
        $controller = new ExistingSchoolVTController([]);
        $reflection = new ReflectionClass($controller);

        $orgProp = $reflection->getProperty('organisation_details');
        $orgProp->setValue($controller, ['cf_accounts_freetravel' => '0']);

        $extendProp = $reflection->getProperty('extend');
        $extendProp->setValue($controller, ['Wellbeing Webinar 1 (Self)', 'Building Resilience at Home Webinar']);

        $this->assertSame('Delivered', $this->callProtected($controller, 'get_quote_stage'));
    }
}
