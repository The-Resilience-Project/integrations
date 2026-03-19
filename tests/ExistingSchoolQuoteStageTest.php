<?php

use PHPUnit\Framework\TestCase;

class ExistingSchoolQuoteStageTest extends TestCase
{
    private function makeController(string $freeTravel, array $extend): ExistingSchoolVTController
    {
        $controller = new ExistingSchoolVTController([]);

        $orgProp = new ReflectionProperty($controller, 'organisation_details');
        $orgProp->setValue($controller, ['cf_accounts_freetravel' => $freeTravel]);

        $extendProp = new ReflectionProperty($controller, 'extend');
        $extendProp->setValue($controller, $extend);

        return $controller;
    }

    public function test_delivered_when_free_travel(): void
    {
        $controller = $this->makeController('1', ['Workshop 1 (Self)']);
        $method = new ReflectionMethod($controller, 'get_quote_stage');

        $this->assertEquals('Delivered', $method->invoke($controller));
    }

    public function test_new_when_has_workshops_and_no_free_travel(): void
    {
        $controller = $this->makeController('0', [
            'Wellbeing Workshop 1 (Self)',
            'Wellbeing Webinar 2 (Others)',
        ]);
        $method = new ReflectionMethod($controller, 'get_quote_stage');

        $this->assertEquals('New', $method->invoke($controller));
    }

    public function test_delivered_when_only_webinars(): void
    {
        $controller = $this->makeController('0', [
            'Wellbeing Webinar 1 (Self)',
            'Wellbeing Webinar 2 (Others)',
        ]);
        $method = new ReflectionMethod($controller, 'get_quote_stage');

        $this->assertEquals('Delivered', $method->invoke($controller));
    }

    public function test_delivered_when_no_extends(): void
    {
        $controller = $this->makeController('0', []);
        $method = new ReflectionMethod($controller, 'get_quote_stage');

        $this->assertEquals('Delivered', $method->invoke($controller));
    }

    public function test_school_controller_always_delivered(): void
    {
        $controller = new SchoolVTController([]);
        $method = new ReflectionMethod($controller, 'get_quote_stage');

        $this->assertEquals('Delivered', $method->invoke($controller));
    }
}
