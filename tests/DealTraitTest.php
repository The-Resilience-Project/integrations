<?php

use PHPUnit\Framework\TestCase;

class DealTraitTest extends TestCase
{
    private function callProtected(object $obj, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($obj, $method);
        return $reflection->invoke($obj, ...$args);
    }

    public function test_add_one_day_standard_date(): void
    {
        $controller = new SchoolVTController([]);
        $this->assertSame('16/03/2026', $this->callProtected($controller, 'add_one_day', ['2026-03-15']));
    }

    public function test_add_one_day_end_of_month(): void
    {
        $controller = new SchoolVTController([]);
        $this->assertSame('01/04/2026', $this->callProtected($controller, 'add_one_day', ['2026-03-31']));
    }

    public function test_add_one_day_end_of_year(): void
    {
        $controller = new SchoolVTController([]);
        $this->assertSame('01/01/2027', $this->callProtected($controller, 'add_one_day', ['2026-12-31']));
    }

    public function test_add_one_day_leap_year(): void
    {
        $controller = new SchoolVTController([]);
        $this->assertSame('29/02/2028', $this->callProtected($controller, 'add_one_day', ['2028-02-28']));
    }

    public function test_add_one_day_with_datetime_input(): void
    {
        $controller = new SchoolVTController([]);
        $result = $this->callProtected($controller, 'add_one_day', ['2026-03-15 10:30:00']);
        $this->assertSame('16/03/2026', $result);
    }
}
