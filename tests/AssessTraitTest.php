<?php

use PHPUnit\Framework\TestCase;

class AssessTraitTest extends TestCase
{
    private function createController(array $data): SchoolVTController
    {
        return new SchoolVTController($data);
    }

    private function callProtected(object $obj, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($obj, $method);
        return $reflection->invoke($obj, ...$args);
    }

    // --- convert_data_to_bool ---

    public function test_convert_data_to_bool_yes(): void
    {
        $controller = $this->createController(['VP01' => 'Yes']);
        $this->assertTrue($this->callProtected($controller, 'convert_data_to_bool', ['VP01']));
    }

    public function test_convert_data_to_bool_no(): void
    {
        $controller = $this->createController(['VP01' => 'No']);
        $this->assertFalse($this->callProtected($controller, 'convert_data_to_bool', ['VP01']));
    }

    public function test_convert_data_to_bool_missing_key(): void
    {
        $controller = $this->createController([]);
        $this->assertFalse($this->callProtected($controller, 'convert_data_to_bool', ['VP01']));
    }

    public function test_convert_data_to_bool_empty_string(): void
    {
        $controller = $this->createController(['VP01' => '']);
        $this->assertFalse($this->callProtected($controller, 'convert_data_to_bool', ['VP01']));
    }

    // --- get_score ---

    public function test_get_score_emerging_when_zeroset_has_no(): void
    {
        $data = ['Z1' => 'Yes', 'Z2' => 'No', 'O1' => 'Yes', 'O2' => 'Yes'];
        $controller = $this->createController($data);

        $result = $this->callProtected($controller, 'get_score', [['Z1', 'Z2'], ['O1', 'O2']]);
        $this->assertSame('Emerging', $result);
    }

    public function test_get_score_established_when_oneset_has_no(): void
    {
        $data = ['Z1' => 'Yes', 'Z2' => 'Yes', 'O1' => 'No', 'O2' => 'Yes'];
        $controller = $this->createController($data);

        $result = $this->callProtected($controller, 'get_score', [['Z1', 'Z2'], ['O1', 'O2']]);
        $this->assertSame('Established', $result);
    }

    public function test_get_score_excelling_when_all_yes(): void
    {
        $data = ['Z1' => 'Yes', 'Z2' => 'Yes', 'O1' => 'Yes', 'O2' => 'Yes'];
        $controller = $this->createController($data);

        $result = $this->callProtected($controller, 'get_score', [['Z1', 'Z2'], ['O1', 'O2']]);
        $this->assertSame('Excelling', $result);
    }

    public function test_get_score_emerging_takes_priority_over_established(): void
    {
        $data = ['Z1' => 'No', 'O1' => 'No'];
        $controller = $this->createController($data);

        $result = $this->callProtected($controller, 'get_score', [['Z1'], ['O1']]);
        $this->assertSame('Emerging', $result);
    }
}
