<?php

use PHPUnit\Framework\TestCase;

class LineItemCalculatorTest extends TestCase
{
    private LineItemCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new LineItemCalculator();
    }

    private function defaultOrgDetails(): array
    {
        return [
            'cf_accounts_2025inspire' => '',
        ];
    }

    // -- New school items --

    public function test_new_school_default_inspire_and_engage(): void
    {
        $result = $this->calculator->calculateNewSchoolItems([
            'participating_num_of_students' => '300',
            'num_of_students' => '300',
        ]);

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER157', $codes);
        $this->assertContains('SER12', $codes);
        $this->assertEquals('Inspire 1', $result['inspire']);
    }

    public function test_new_school_small_101_200_students(): void
    {
        $result = $this->calculator->calculateNewSchoolItems([
            'participating_num_of_students' => '150',
            'num_of_students' => '150',
            'mental_health_funding' => 'No',
        ]);

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER158', $codes);
    }

    public function test_new_school_small_under_100_students(): void
    {
        $result = $this->calculator->calculateNewSchoolItems([
            'participating_num_of_students' => '80',
            'num_of_students' => '80',
            'mental_health_funding' => 'No',
        ]);

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER159', $codes);
    }

    public function test_new_school_mhf_overrides_small_school(): void
    {
        $result = $this->calculator->calculateNewSchoolItems([
            'participating_num_of_students' => '80',
            'num_of_students' => '80',
            'mental_health_funding' => 'Yes',
        ]);

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER157', $codes);
    }

    public function test_new_school_engage_quantity_matches_students(): void
    {
        $result = $this->calculator->calculateNewSchoolItems([
            'participating_num_of_students' => '450',
            'num_of_students' => '450',
        ]);

        $engage_item = null;
        foreach ($result['items'] as $item) {
            if ($item['code'] === 'SER12') {
                $engage_item = $item;
            }
        }
        $this->assertEquals('450', $engage_item['qty']);
    }

    // -- Existing school items --

    public function test_existing_primary_journals_only(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
        ], $this->defaultOrgDetails());

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER12', $codes);
        $this->assertNotContains('SER65', $codes);
        $this->assertEquals(['Journals'], $result['engage']);
    }

    public function test_existing_secondary_planners(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Secondary',
            'secondary_engage' => 'Planners',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
        ], $this->defaultOrgDetails());

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER65', $codes);
        $this->assertNotContains('SER12', $codes);
        $this->assertEquals(['Planners'], $result['engage']);
    }

    public function test_existing_both_school_journals_and_planners(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Both',
            'secondary_engage' => 'Planners',
            'participating_journal_students' => '100',
            'participating_planner_students' => '50',
            'inspire_added' => 'No',
        ], $this->defaultOrgDetails());

        $this->assertEquals(['Journals', 'Planners'], $result['engage']);
    }

    public function test_existing_inspire_not_added(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
        ], $this->defaultOrgDetails());

        $this->assertEquals('', $result['inspire']);
        $this->assertEquals('', $result['billing_note']);
    }

    public function test_existing_inspire_added_default_level(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
        ], $this->defaultOrgDetails());

        $this->assertEquals('Inspire 2', $result['inspire']);
        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER147', $codes);
    }

    public function test_existing_inspire_preserves_level_3(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
        ], ['cf_accounts_2025inspire' => 'Inspire 3']);

        $this->assertEquals('Inspire 3', $result['inspire']);
    }

    public function test_existing_inspire_preserves_level_4(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
        ], ['cf_accounts_2025inspire' => 'Inspire 4']);

        $this->assertEquals('Inspire 4', $result['inspire']);
    }

    public function test_existing_inspire_mhf_code(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '100',
            'inspire_added' => 'Yes',
            'mental_health_funding' => 'Yes',
        ], $this->defaultOrgDetails());

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER146', $codes);
    }

    public function test_existing_inspire_small_101_200(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '150',
            'inspire_added' => 'Yes',
            'mental_health_funding' => 'No',
            'num_of_students_1' => '150',
        ], $this->defaultOrgDetails());

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER148', $codes);
    }

    public function test_existing_inspire_small_under_100(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '80',
            'inspire_added' => 'Yes',
            'mental_health_funding' => 'No',
            'num_of_students_1' => '80',
        ], $this->defaultOrgDetails());

        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER149', $codes);
    }

    public function test_existing_p12_billing_note(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '300',
            'inspire_added' => 'Yes',
            'mental_health_funding' => 'No',
            'inspire_year_levels' => 'Primary and Secondary',
        ], $this->defaultOrgDetails());

        $this->assertStringContainsString('$1000', $result['billing_note']);
        $this->assertStringContainsString('P-12', $result['billing_note']);
    }

    public function test_existing_extend_empty_when_none_selected(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
        ], $this->defaultOrgDetails());

        $this->assertEquals([], $result['extend']);
    }

    public function test_existing_extend_with_teacher_wellbeing(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
            'teacher_wellbeing_program' => 'Teacher Wellbeing Program$500',
        ], $this->defaultOrgDetails());

        $this->assertContains('Teacher Wellbeing Program', $result['extend']);
        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER23', $codes);
    }

    public function test_existing_extend_number_replacement(): void
    {
        $result = $this->calculator->calculateExistingSchoolItems([
            'school_type' => 'Primary',
            'participating_num_of_students' => '200',
            'inspire_added' => 'No',
            'twb_1_online_only' => 'Wellbeing Webinar One (Self)$500',
        ], $this->defaultOrgDetails());

        $this->assertContains('Wellbeing Webinar 1 (Self)', $result['extend']);
        $codes = array_column($result['items'], 'code');
        $this->assertContains('SER26', $codes);
    }
}
