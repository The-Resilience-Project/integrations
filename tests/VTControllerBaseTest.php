<?php

use PHPUnit\Framework\TestCase;

class VTControllerBaseTest extends TestCase
{
    private function callProtected(object $obj, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($obj, $method);
        return $reflection->invoke($obj, ...$args);
    }

    // --- isset_data ---

    public function test_isset_data_returns_true_for_present_key(): void
    {
        $controller = new SchoolVTController(['name' => 'Test School']);
        $this->assertTrue($this->callProtected($controller, 'isset_data', ['name']));
    }

    public function test_isset_data_returns_false_for_missing_key(): void
    {
        $controller = new SchoolVTController([]);
        $this->assertFalse($this->callProtected($controller, 'isset_data', ['name']));
    }

    public function test_isset_data_returns_false_for_empty_value(): void
    {
        $controller = new SchoolVTController(['name' => '']);
        $this->assertFalse($this->callProtected($controller, 'isset_data', ['name']));
    }

    public function test_isset_data_returns_false_for_null_value(): void
    {
        $controller = new SchoolVTController(['name' => null]);
        $this->assertFalse($this->callProtected($controller, 'isset_data', ['name']));
    }

    // --- get_token ---

    public function test_get_token_returns_correct_token(): void
    {
        $controller = new SchoolVTController([]);
        $result = $this->callProtected($controller, 'get_token', ['createEnquiry']);
        $this->assertSame('8di4F24NumqITmuAky325Vj3', $result);
    }

    public function test_get_token_returns_correct_token_for_different_endpoints(): void
    {
        $controller = new SchoolVTController([]);

        $this->assertSame('j2bXkMP4TaPmKjTBFXVIsq1K', $this->callProtected($controller, 'get_token', ['captureCustomerInfo']));
        $this->assertSame('r8ZUEYcio6VpH0O54jDtE55L', $this->callProtected($controller, 'get_token', ['createDeal']));
    }

    // --- constructor ---

    public function test_constructor_stores_data(): void
    {
        $data = ['contact_email' => 'test@example.com', 'state' => 'VIC'];
        $controller = new SchoolVTController($data);

        $reflection = new ReflectionClass($controller);
        $prop = $reflection->getProperty('data');

        $this->assertSame($data, $prop->getValue($controller));
    }
}
