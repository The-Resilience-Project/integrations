<?php

use ApiV2\Domain\Contact;
use ApiV2\Domain\EnquiryRequest;
use ApiV2\Domain\Organisation;
use PHPUnit\Framework\TestCase;

class EnquiryRequestTest extends TestCase
{
    private function makeFormData(array $overrides = []): array
    {
        return array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'contact_phone' => '0412345678',
            'org_phone' => '0398765432',
            'job_title' => 'Principal',
            'contact_type' => 'Teacher',
            'contact_newsletter' => 'Yes',
            'school_account_no' => 'ACC123',
            'school_name_other' => 'New School',
            'school_name_other_selected' => true,
            'state' => 'VIC',
            'organisation_sub_type' => 'Primary',
            'num_of_students' => '500',
            'num_of_employees' => '50',
            'contact_lead_source' => 'Website',
            'enquiry' => 'Interested in the program',
            'participating_num_of_students' => '200',
        ], $overrides);
    }

    public function test_from_form_data_extracts_required_fields(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData());

        $this->assertSame('jane@school.edu.au', $request->contactEmail);
        $this->assertSame('Jane', $request->contactFirstName);
        $this->assertSame('Smith', $request->contactLastName);
    }

    public function test_from_form_data_extracts_optional_contact_fields(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData());

        $this->assertSame('0412345678', $request->contactPhone);
        $this->assertSame('0398765432', $request->orgPhone);
        $this->assertSame('Principal', $request->jobTitle);
        $this->assertSame('Teacher', $request->contactType);
        $this->assertSame('Yes', $request->contactNewsletter);
    }

    public function test_from_form_data_extracts_optional_organisation_fields(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData());

        $this->assertSame('ACC123', $request->schoolAccountNo);
        $this->assertSame('New School', $request->schoolNameOther);
        $this->assertTrue($request->schoolNameOtherSelected);
        $this->assertSame('VIC', $request->state);
        $this->assertSame('Primary', $request->organisationSubType);
        $this->assertSame(500, $request->numOfStudents);
        $this->assertSame(50, $request->numOfEmployees);
        $this->assertSame('Website', $request->contactLeadSource);
    }

    public function test_from_form_data_extracts_enquiry_specific_fields(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData());

        $this->assertSame('Interested in the program', $request->enquiry);
        $this->assertSame(200, $request->participatingNumOfStudents);
    }

    public function test_optional_fields_default_to_null(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
        ]);

        $this->assertNull($request->contactPhone);
        $this->assertNull($request->orgPhone);
        $this->assertNull($request->jobTitle);
        $this->assertNull($request->contactType);
        $this->assertNull($request->contactNewsletter);
        $this->assertNull($request->schoolAccountNo);
        $this->assertNull($request->schoolNameOther);
        $this->assertFalse($request->schoolNameOtherSelected);
        $this->assertNull($request->state);
        $this->assertNull($request->organisationSubType);
        $this->assertNull($request->numOfStudents);
        $this->assertNull($request->numOfEmployees);
        $this->assertNull($request->contactLeadSource);
        $this->assertNull($request->enquiry);
        $this->assertNull($request->participatingNumOfStudents);
        $this->assertNull($request->sourceForm);
    }

    public function test_throws_when_email_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EnquiryRequest::fromFormData($this->makeFormData([
            'contact_email' => '',
        ]));
    }

    public function test_throws_when_email_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = $this->makeFormData();
        unset($data['contact_email']);
        EnquiryRequest::fromFormData($data);
    }

    public function test_from_form_data_extracts_source_form(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData([
            'source_form' => 'VACPSP Enquiry 2026',
        ]));

        $this->assertSame('VACPSP Enquiry 2026', $request->sourceForm);
    }

    public function test_to_contact_creates_correct_contact(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData());
        $contact = $request->toContact();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame('jane@school.edu.au', $contact->email);
        $this->assertSame('Jane', $contact->firstName);
        $this->assertSame('Smith', $contact->lastName);
        $this->assertSame('0412345678', $contact->phone);
        $this->assertSame('0398765432', $contact->orgPhone);
        $this->assertSame('Principal', $contact->jobTitle);
        $this->assertSame('Teacher', $contact->type);
        $this->assertSame('Yes', $contact->newsletter);
    }

    public function test_to_organisation_creates_correct_organisation(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData());
        $org = $request->toOrganisation();

        $this->assertInstanceOf(Organisation::class, $org);
        $this->assertSame('ACC123', $org->accountNo);
        $this->assertSame('New School', $org->name);
        $this->assertSame('VIC', $org->state);
        $this->assertSame('Primary', $org->subType);
        $this->assertSame(500, $org->numStudents);
        $this->assertSame(50, $org->numEmployees);
        $this->assertSame('Website', $org->leadSource);
    }
}
