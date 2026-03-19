<?php

use PHPUnit\Framework\TestCase;

class DealTraitTest extends TestCase
{
    private function makeController(array $data = []): SchoolVTController
    {
        return new SchoolVTController($data);
    }

    public function test_add_one_day_standard_date(): void
    {
        $controller = $this->makeController();
        $method = new ReflectionMethod($controller, 'add_one_day');

        $this->assertEquals('02/01/2026', $method->invoke($controller, '2026-01-01'));
    }

    public function test_add_one_day_end_of_month(): void
    {
        $controller = $this->makeController();
        $method = new ReflectionMethod($controller, 'add_one_day');

        $this->assertEquals('01/02/2026', $method->invoke($controller, '2026-01-31'));
    }

    public function test_add_one_day_end_of_year(): void
    {
        $controller = $this->makeController();
        $method = new ReflectionMethod($controller, 'add_one_day');

        $this->assertEquals('01/01/2027', $method->invoke($controller, '2026-12-31'));
    }

    public function test_add_one_day_leap_year(): void
    {
        $controller = $this->makeController();
        $method = new ReflectionMethod($controller, 'add_one_day');

        $this->assertEquals('29/02/2028', $method->invoke($controller, '2028-02-28'));
    }

    public function test_add_one_day_non_leap_year(): void
    {
        $controller = $this->makeController();
        $method = new ReflectionMethod($controller, 'add_one_day');

        $this->assertEquals('01/03/2026', $method->invoke($controller, '2026-02-28'));
    }

    public function test_build_deal_payload_basic(): void
    {
        $controller = $this->makeController(['state' => 'VIC']);
        $method = new ReflectionMethod($controller, 'build_deal_payload');

        // Set required properties
        $contactProp = new ReflectionProperty($controller, 'contact_id');
        $contactProp->setValue($controller, '4x100');
        $orgProp = new ReflectionProperty($controller, 'organisation_id');
        $orgProp->setValue($controller, '3x200');
        $orgDetails = new ReflectionProperty($controller, 'organisation_details');
        $orgDetails->setValue($controller, ['assigned_user_id' => '19x99']);

        $result = $method->invoke($controller, 'New', '01/01/2026');

        $this->assertEquals('2026 School Partnership Program', $result['dealName']);
        $this->assertEquals('School', $result['dealType']);
        $this->assertEquals('School - New', $result['dealOrgType']);
        $this->assertEquals('New', $result['dealStage']);
        $this->assertEquals('01/01/2026', $result['dealCloseDate']);
        $this->assertEquals('4x100', $result['contactId']);
        $this->assertEquals('3x200', $result['organisationId']);
    }

    public function test_build_deal_payload_with_participating_students(): void
    {
        $controller = $this->makeController([
            'participating_num_of_students' => '500',
            'state' => 'VIC',
        ]);
        $method = new ReflectionMethod($controller, 'build_deal_payload');

        $contactProp = new ReflectionProperty($controller, 'contact_id');
        $contactProp->setValue($controller, '4x100');
        $orgProp = new ReflectionProperty($controller, 'organisation_id');
        $orgProp->setValue($controller, '3x200');
        $orgDetails = new ReflectionProperty($controller, 'organisation_details');
        $orgDetails->setValue($controller, ['assigned_user_id' => '19x99']);

        $result = $method->invoke($controller, 'New', '01/01/2026');

        $this->assertEquals('500', $result['dealNumOfParticipants']);
    }

    public function test_build_deal_payload_employees_overrides_students(): void
    {
        $controller = $this->makeController([
            'num_of_students' => '300',
            'num_of_employees' => '50',
            'state' => 'VIC',
        ]);

        $method = new ReflectionMethod($controller, 'build_deal_payload');
        $contactProp = new ReflectionProperty($controller, 'contact_id');
        $contactProp->setValue($controller, '4x100');
        $orgProp = new ReflectionProperty($controller, 'organisation_id');
        $orgProp->setValue($controller, '3x200');
        $orgDetails = new ReflectionProperty($controller, 'organisation_details');
        $orgDetails->setValue($controller, ['assigned_user_id' => '19x1']);

        $result = $method->invoke($controller, 'New', '01/01/2026');

        // num_of_employees is set last so it overwrites
        $this->assertEquals('50', $result['dealNumOfParticipants']);
    }
}
