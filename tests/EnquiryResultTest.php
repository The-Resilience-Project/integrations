<?php

use PHPUnit\Framework\TestCase;

class EnquiryResultTest extends TestCase
{
    public function test_successful_result(): void
    {
        $result = new EnquiryResult(
            success: true,
            serviceType: 'School',
            organisation: 'Test School',
            contactEmail: 'test@school.edu.au',
        );

        $this->assertTrue($result->success);
        $this->assertEquals('success', $result->status());
        $this->assertNull($result->errorMessage);
    }

    public function test_failed_result_with_error(): void
    {
        $result = new EnquiryResult(
            success: false,
            serviceType: 'Workplace',
            organisation: 'ACME',
            contactEmail: 'hr@acme.com',
            errorMessage: 'CRM timeout',
        );

        $this->assertFalse($result->success);
        $this->assertEquals('fail', $result->status());
        $this->assertEquals('CRM timeout', $result->errorMessage);
    }

    public function test_to_response_success(): void
    {
        $result = new EnquiryResult(true, 'School', 'Test', 'a@b.com');

        $this->assertEquals(['status' => 'success'], $result->toResponse());
    }

    public function test_to_response_failure_includes_message(): void
    {
        $result = new EnquiryResult(false, 'School', 'Test', 'a@b.com', 'Something broke');

        $response = $result->toResponse();
        $this->assertEquals('fail', $response['status']);
        $this->assertEquals('Something broke', $response['message']);
    }

    public function test_to_response_failure_without_message(): void
    {
        $result = new EnquiryResult(false, 'School', 'Test', 'a@b.com');

        $this->assertEquals(['status' => 'fail'], $result->toResponse());
    }
}
