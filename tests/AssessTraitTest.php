<?php

use PHPUnit\Framework\TestCase;

class AssessTraitTest extends TestCase
{
    private function makeController(array $data): SchoolVTController
    {
        return new SchoolVTController($data);
    }

    // -- convert_data_to_bool --

    public function test_convert_data_to_bool_yes(): void
    {
        $controller = $this->makeController(['VP01' => 'Yes']);
        $method = new ReflectionMethod($controller, 'convert_data_to_bool');

        $this->assertTrue($method->invoke($controller, 'VP01'));
    }

    public function test_convert_data_to_bool_no(): void
    {
        $controller = $this->makeController(['VP01' => 'No']);
        $method = new ReflectionMethod($controller, 'convert_data_to_bool');

        $this->assertFalse($method->invoke($controller, 'VP01'));
    }

    public function test_convert_data_to_bool_missing_key(): void
    {
        $controller = $this->makeController([]);
        $method = new ReflectionMethod($controller, 'convert_data_to_bool');

        $this->assertFalse($method->invoke($controller, 'VP01'));
    }

    public function test_convert_data_to_bool_other_value(): void
    {
        $controller = $this->makeController(['VP01' => 'Maybe']);
        $method = new ReflectionMethod($controller, 'convert_data_to_bool');

        $this->assertFalse($method->invoke($controller, 'VP01'));
    }

    // -- get_score --

    public function test_score_emerging_when_zeroset_has_no(): void
    {
        $controller = $this->makeController([
            'VP01' => 'Yes',
            'VP02' => 'No',
            'VP11' => 'Yes',
            'VP12' => 'Yes',
        ]);
        $method = new ReflectionMethod($controller, 'get_score');

        $result = $method->invoke($controller, ['VP01', 'VP02'], ['VP11', 'VP12']);

        $this->assertEquals('Emerging', $result);
    }

    public function test_score_established_when_oneset_has_no(): void
    {
        $controller = $this->makeController([
            'VP01' => 'Yes',
            'VP02' => 'Yes',
            'VP11' => 'Yes',
            'VP12' => 'No',
        ]);
        $method = new ReflectionMethod($controller, 'get_score');

        $result = $method->invoke($controller, ['VP01', 'VP02'], ['VP11', 'VP12']);

        $this->assertEquals('Established', $result);
    }

    public function test_score_excelling_when_all_yes(): void
    {
        $controller = $this->makeController([
            'VP01' => 'Yes',
            'VP02' => 'Yes',
            'VP11' => 'Yes',
            'VP12' => 'Yes',
        ]);
        $method = new ReflectionMethod($controller, 'get_score');

        $result = $method->invoke($controller, ['VP01', 'VP02'], ['VP11', 'VP12']);

        $this->assertEquals('Excelling', $result);
    }

    public function test_score_emerging_takes_priority_over_established(): void
    {
        // Both zeroset and oneset have 'No' — zeroset wins (Emerging)
        $controller = $this->makeController([
            'VP01' => 'No',
            'VP02' => 'Yes',
            'VP11' => 'No',
            'VP12' => 'Yes',
        ]);
        $method = new ReflectionMethod($controller, 'get_score');

        $result = $method->invoke($controller, ['VP01', 'VP02'], ['VP11', 'VP12']);

        $this->assertEquals('Emerging', $result);
    }
}
