<?php

use PHPUnit\Framework\TestCase;

class VTControllerBaseTest extends TestCase
{
    public function test_calculate_close_date_returns_correct_format(): void
    {
        $controller = new SchoolVTController(['state' => 'VIC']);
        $reflection = new ReflectionMethod($controller, 'calculate_close_date');

        $result = $reflection->invoke($controller, '+2 Weeks');

        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4}$/', $result);
    }

    public function test_calculate_close_date_two_weeks_ahead(): void
    {
        $controller = new SchoolVTController(['state' => 'VIC']);
        $reflection = new ReflectionMethod($controller, 'calculate_close_date');

        $result = $reflection->invoke($controller, '+2 Weeks');
        $expected = date('d/m/Y', strtotime('+2 Weeks'));

        $this->assertEquals($expected, $result);
    }

    public function test_calculate_close_date_ten_days_ahead(): void
    {
        $controller = new WorkplaceVTController(['state' => 'VIC']);
        $reflection = new ReflectionMethod($controller, 'calculate_close_date');

        $result = $reflection->invoke($controller, '+10 Days');
        $expected = date('d/m/Y', strtotime('+10 Days'));

        $this->assertEquals($expected, $result);
    }

    public function test_find_service_by_code(): void
    {
        $controller = new SchoolVTController([]);
        $reflection = new ReflectionMethod($controller, 'find_service_by_code');

        $services = [
            (object) ['service_no' => 'SER12', 'id' => '100', 'unit_price' => '500'],
            (object) ['service_no' => 'SER65', 'id' => '200', 'unit_price' => '300'],
            (object) ['service_no' => 'SER23', 'id' => '300', 'unit_price' => '700'],
        ];

        $result = $reflection->invoke($controller, $services, 'SER65');

        $this->assertEquals('200', $result->id);
        $this->assertEquals('300', $result->unit_price);
    }

    public function test_find_service_by_code_first_element(): void
    {
        $controller = new SchoolVTController([]);
        $reflection = new ReflectionMethod($controller, 'find_service_by_code');

        $services = [
            (object) ['service_no' => 'SER12', 'id' => '100'],
            (object) ['service_no' => 'SER65', 'id' => '200'],
        ];

        $result = $reflection->invoke($controller, $services, 'SER12');

        $this->assertEquals('100', $result->id);
    }

    public function test_isset_data_returns_true_for_non_empty(): void
    {
        $controller = new SchoolVTController(['name' => 'Test School']);
        $reflection = new ReflectionMethod($controller, 'isset_data');

        $this->assertTrue($reflection->invoke($controller, 'name'));
    }

    public function test_isset_data_returns_false_for_missing_key(): void
    {
        $controller = new SchoolVTController([]);
        $reflection = new ReflectionMethod($controller, 'isset_data');

        $this->assertFalse($reflection->invoke($controller, 'name'));
    }

    public function test_isset_data_returns_false_for_empty_string(): void
    {
        $controller = new SchoolVTController(['name' => '']);
        $reflection = new ReflectionMethod($controller, 'isset_data');

        $this->assertFalse($reflection->invoke($controller, 'name'));
    }
}
