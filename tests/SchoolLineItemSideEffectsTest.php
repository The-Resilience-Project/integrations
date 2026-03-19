<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the side effects of ExistingSchoolVTController::get_line_items() —
 * specifically the $this->inspire, $this->engage, $this->extend, and
 * $this->billing_note properties set during line item calculation.
 */
class SchoolLineItemSideEffectsTest extends TestCase
{
    private function makeController(array $data, array $orgDetails = []): object
    {
        $defaultOrgDetails = [
            'assigned_user_id' => '19x99',
            'cf_accounts_2025inspire' => '',
            'cf_accounts_2025salesevents' => '',
            'cf_accounts_freetravel' => '0',
            'cf_accounts_yearswithtrp' => '',
            'cf_accounts_2024inspire' => '',
            'cf_accounts_2025confirmationstatus' => '',
            'cf_accounts_2024confirmationstatus' => '',
        ];
        $orgDetails = array_merge($defaultOrgDetails, $orgDetails);

        $controller = new class ($data) extends ExistingSchoolVTController {
            protected function get_services($codes, $ids = [])
            {
                $services = [];
                foreach ($codes as $code) {
                    $services[] = (object) [
                        'service_no' => $code,
                        'id' => 'fake_' . $code,
                        'unit_price' => '1000',
                        'cf_services_xerocode' => 'XERO_' . $code,
                    ];
                }
                return $services;
            }
        };

        $orgProp = new ReflectionProperty(SchoolVTController::class, 'organisation_details');
        $orgProp->setValue($controller, $orgDetails);

        return $controller;
    }

    // -- engage property --

    public function test_primary_school_engage_is_journals(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'engage');
        $this->assertEquals(['Journals'], $prop->getValue($controller));
    }

    public function test_secondary_planners_engage(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Secondary',
            'secondary_engage' => 'Planners',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'engage');
        $this->assertEquals(['Planners'], $prop->getValue($controller));
    }

    public function test_both_school_both_engage(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Both',
            'secondary_engage' => 'Planners',
            'participating_journal_students' => '100',
            'participating_planner_students' => '50',
            'inspire_added' => 'No',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'engage');
        $this->assertEquals(['Journals', 'Planners'], $prop->getValue($controller));
    }

    // -- inspire property --

    public function test_inspire_empty_when_not_added(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'inspire');
        $this->assertEquals('', $prop->getValue($controller));
    }

    public function test_inspire_2_when_added(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'inspire');
        $this->assertEquals('Inspire 2', $prop->getValue($controller));
    }

    public function test_inspire_3_preserved_from_2025(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
        ], [
            'cf_accounts_2025inspire' => 'Inspire 3',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'inspire');
        $this->assertEquals('Inspire 3', $prop->getValue($controller));
    }

    public function test_inspire_4_preserved_from_2025(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
        ], [
            'cf_accounts_2025inspire' => 'Inspire 4',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'inspire');
        $this->assertEquals('Inspire 4', $prop->getValue($controller));
    }

    // -- billing_note property --

    public function test_billing_note_empty_by_default(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'billing_note');
        $this->assertEquals('', $prop->getValue($controller));
    }

    public function test_billing_note_set_for_p12_inspire(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
            'mental_health_funding' => 'No',
            'inspire_year_levels' => 'Primary and Secondary',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'billing_note');
        $this->assertStringContainsString('$1000', $prop->getValue($controller));
        $this->assertStringContainsString('P-12', $prop->getValue($controller));
    }

    // -- extend property --

    public function test_extend_empty_when_none_selected(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'extend');
        $this->assertEquals([], $prop->getValue($controller));
    }

    public function test_extend_populated_with_teacher_wellbeing(): void
    {
        $controller = $this->makeController([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
            'teacher_wellbeing_program' => 'Teacher Wellbeing Program$500',
        ]);

        $controller->get_line_items();

        $prop = new ReflectionProperty(SchoolVTController::class, 'extend');
        $this->assertContains('Teacher Wellbeing Program', $prop->getValue($controller));
    }
}
