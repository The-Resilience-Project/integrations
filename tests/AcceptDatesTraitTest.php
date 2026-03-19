<?php

use PHPUnit\Framework\TestCase;

class AcceptDatesTraitTest extends TestCase
{
    private function makeController(array $data): SchoolVTController
    {
        return new SchoolVTController($data);
    }

    public function test_date_acceptance_body_empty_when_no_events(): void
    {
        $controller = $this->makeController([]);
        $method = new ReflectionMethod($controller, 'get_date_acceptance_body');

        $result = $method->invoke($controller);

        $this->assertEquals('<ul></ul>', $result);
    }

    public function test_date_acceptance_body_single_event(): void
    {
        $controller = $this->makeController([
            'twb_1_web' => '15/03/2026',
        ]);
        $method = new ReflectionMethod($controller, 'get_date_acceptance_body');

        $result = $method->invoke($controller);

        $this->assertStringContainsString('Teacher Wellbeing 1: Looking After Yourself (Webinar)', $result);
        $this->assertStringContainsString('15/03/2026', $result);
        $this->assertStringStartsWith('<ul>', $result);
        $this->assertStringEndsWith('</ul>', $result);
    }

    public function test_date_acceptance_body_multiple_events(): void
    {
        $controller = $this->makeController([
            'twb_1_web' => '15/03/2026',
            'brh_web' => '20/04/2026',
            'cp' => '01/05/2026',
        ]);
        $method = new ReflectionMethod($controller, 'get_date_acceptance_body');

        $result = $method->invoke($controller);

        $this->assertStringContainsString('Teacher Wellbeing 1: Looking After Yourself (Webinar)', $result);
        $this->assertStringContainsString('Building Resilience at Home for Parents/Carers (Webinar)', $result);
        $this->assertStringContainsString('Connected Parenting with Lael Stone (Webinar)', $result);
        $this->assertEquals(3, substr_count($result, '<li>'));
    }

    public function test_date_acceptance_body_ignores_unknown_keys(): void
    {
        $controller = $this->makeController([
            'twb_1_web' => '15/03/2026',
            'unknown_event' => '01/01/2026',
        ]);
        $method = new ReflectionMethod($controller, 'get_date_acceptance_body');

        $result = $method->invoke($controller);

        $this->assertEquals(1, substr_count($result, '<li>'));
    }
}
