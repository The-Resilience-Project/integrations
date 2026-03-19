<?php

use PHPUnit\Framework\TestCase;

class TimeAndTokenTest extends TestCase
{
    // -- timetoampm --

    public function test_timetoampm_morning(): void
    {
        $this->assertEquals('09:30AM', timetoampm('09:30'));
    }

    public function test_timetoampm_afternoon(): void
    {
        $this->assertEquals('02:00PM', timetoampm('14:00'));
    }

    public function test_timetoampm_midnight(): void
    {
        $this->assertEquals('12:00AM', timetoampm('00:00'));
    }

    public function test_timetoampm_noon(): void
    {
        $this->assertEquals('12:00PM', timetoampm('12:00'));
    }

    // -- get_token --

    public function test_get_token_known_endpoint(): void
    {
        $controller = new SchoolVTController([]);
        $method = new ReflectionMethod($controller, 'get_token');

        $this->assertEquals('8di4F24NumqITmuAky325Vj3', $method->invoke($controller, 'createEnquiry'));
    }

    public function test_get_token_another_endpoint(): void
    {
        $controller = new SchoolVTController([]);
        $method = new ReflectionMethod($controller, 'get_token');

        $this->assertEquals('jMgenBKJZxTi0mpz4Ga4rQom', $method->invoke($controller, 'getServices'));
    }

    // -- Teacher planner code mapping --

    public function test_teacher_planner_code_lookup(): void
    {
        $controller = new SchoolVTController([
            'teacher_planner_qty' => '5',
            'teacher_planner_type' => '7 Period Week to a View - $25.00',
        ]);

        // The teacher_planner_codes constant maps type to product code
        $ref = new ReflectionClass($controller);
        $codes = $ref->getConstant('teacher_planner_codes');

        $this->assertEquals('PRO59', $codes['7 Period Week to a View']);
        $this->assertEquals('PRO55', $codes['7 Period Day to a Page']);
        $this->assertEquals('PRO46', $codes['4 Period Day to a Page']);
        $this->assertEquals('PRO62', $codes['Admin Week to a View']);
    }

    // -- Senior planner code mapping --

    public function test_senior_planner_code_lookup(): void
    {
        $controller = new SchoolVTController([]);
        $ref = new ReflectionClass($controller);
        $codes = $ref->getConstant('senior_planner_codes');

        $this->assertEquals('PRO60', $codes['Small']);
        $this->assertEquals('PRO63', $codes['Large']);
    }

    // -- Teacher seminar code mapping --

    public function test_teacher_seminar_city_codes(): void
    {
        $controller = new SchoolVTController([]);
        $ref = new ReflectionClass($controller);
        $codes = $ref->getConstant('teacher_seminar_codes');

        $this->assertEquals('SER163', $codes['Melbourne']);
        $this->assertEquals('SER164', $codes['Sydney']);
        $this->assertEquals('SER162', $codes['Brisbane']);
        $this->assertEquals('SER165', $codes['Perth']);
    }
}
