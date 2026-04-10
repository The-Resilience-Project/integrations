<?php

use ApiV2\Domain\Contact;
use ApiV2\Domain\MoreInfoRequest;
use ApiV2\Domain\Organisation;
use PHPUnit\Framework\TestCase;

class MoreInfoRequestTest extends TestCase
{
    private function makeFormData(array $overrides = []): array
    {
        return array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
            'num_of_students' => '300',
        ], $overrides);
    }

    public function test_from_form_data_extracts_required_fields(): void
    {
        $request = MoreInfoRequest::fromFormData($this->makeFormData());

        $this->assertSame('jane@school.edu.au', $request->contactEmail);
        $this->assertSame('Jane', $request->contactFirstName);
        $this->assertSame('Smith', $request->contactLastName);
    }

    public function test_from_form_data_extracts_optional_fields(): void
    {
        $request = MoreInfoRequest::fromFormData($this->makeFormData([
            'contact_phone' => '0412345678',
            'job_title' => 'Principal',
            'organisation_sub_type' => 'Primary',
            'num_of_employees' => '30',
            'contact_lead_source' => 'Google',
        ]));

        $this->assertSame('0412345678', $request->contactPhone);
        $this->assertSame('Principal', $request->jobTitle);
        $this->assertSame('Primary', $request->organisationSubType);
        $this->assertSame(30, $request->numOfEmployees);
        $this->assertSame('Google', $request->contactLeadSource);
    }

    public function test_from_form_data_extracts_num_of_students(): void
    {
        $request = MoreInfoRequest::fromFormData($this->makeFormData());

        $this->assertSame(300, $request->numOfStudents);
    }

    public function test_from_form_data_extracts_source_form(): void
    {
        $request = MoreInfoRequest::fromFormData($this->makeFormData([
            'source_form' => 'VACPSP More Info 2027',
        ]));

        $this->assertSame('VACPSP More Info 2027', $request->sourceForm);
    }

    public function test_optional_fields_default_to_null(): void
    {
        $request = MoreInfoRequest::fromFormData([
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Test',
            'contact_last_name' => 'User',
        ]);

        $this->assertNull($request->contactPhone);
        $this->assertNull($request->schoolAccountNo);
        $this->assertFalse($request->schoolNameOtherSelected);
        $this->assertNull($request->numOfStudents);
        $this->assertNull($request->sourceForm);
    }

    public function test_throws_when_email_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact email must not be empty');

        MoreInfoRequest::fromFormData($this->makeFormData(['contact_email' => '']));
    }

    public function test_to_contact_creates_correct_contact(): void
    {
        $request = MoreInfoRequest::fromFormData($this->makeFormData([
            'contact_phone' => '0412345678',
            'job_title' => 'Principal',
        ]));

        $contact = $request->toContact();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame('jane@school.edu.au', $contact->email);
        $this->assertSame('Jane', $contact->firstName);
        $this->assertSame('Smith', $contact->lastName);
        $this->assertSame('0412345678', $contact->phone);
        $this->assertSame('Principal', $contact->jobTitle);
    }

    public function test_to_organisation_creates_correct_organisation(): void
    {
        $request = MoreInfoRequest::fromFormData($this->makeFormData([
            'organisation_sub_type' => 'Primary',
        ]));

        $org = $request->toOrganisation();

        $this->assertInstanceOf(Organisation::class, $org);
        $this->assertSame('ACC123', $org->accountNo);
        $this->assertSame('VIC', $org->state);
        $this->assertSame(300, $org->numStudents);
        $this->assertSame('Primary', $org->subType);
    }
}
