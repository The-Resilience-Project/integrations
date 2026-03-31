<?php

use PHPUnit\Framework\TestCase;

class AcceptDatesTraitTest extends TestCase
{
    private function createController(array $data): SchoolVTController
    {
        return new SchoolVTController($data);
    }

    private function callProtected(object $obj, string $method): mixed
    {
        $reflection = new ReflectionMethod($obj, $method);
        return $reflection->invoke($obj);
    }

    public function test_date_acceptance_body_includes_matching_events(): void
    {
        $controller = $this->createController([
            'twb_1_web' => 'Monday 15 March 2026',
            'brh_web' => 'Friday 20 March 2026',
        ]);

        $body = $this->callProtected($controller, 'get_date_acceptance_body');

        $this->assertStringContainsString('<ul>', $body);
        $this->assertStringContainsString('</ul>', $body);
        $this->assertStringContainsString('Teacher Wellbeing 1: Looking After Yourself (Webinar)', $body);
        $this->assertStringContainsString('Monday 15 March 2026', $body);
        $this->assertStringContainsString('Building Resilience at Home for Parents/Carers (Webinar)', $body);
        $this->assertStringContainsString('Friday 20 March 2026', $body);
    }

    public function test_date_acceptance_body_excludes_missing_events(): void
    {
        $controller = $this->createController([
            'twb_1_web' => 'Monday 15 March 2026',
        ]);

        $body = $this->callProtected($controller, 'get_date_acceptance_body');

        $this->assertStringContainsString('Teacher Wellbeing 1', $body);
        $this->assertStringNotContainsString('Connected Parenting', $body);
    }

    public function test_date_acceptance_body_empty_when_no_data(): void
    {
        $controller = $this->createController([]);
        $body = $this->callProtected($controller, 'get_date_acceptance_body');

        $this->assertSame('<ul></ul>', $body);
    }
}
