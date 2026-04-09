<?php

use ApiV2\Domain\ConfirmationRequest;
use ApiV2\Domain\Contact;
use ApiV2\Domain\Organisation;
use PHPUnit\Framework\TestCase;

class ConfirmationRequestTest extends TestCase
{
    private function makeFormData(array $overrides = []): array
    {
        return array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
            'address' => '45 Collins Street',
            'suburb' => 'Melbourne',
            'postcode' => '3000',
            'different_billing_contact' => 'No',
            'participating_num_of_students' => '320',
        ], $overrides);
    }

    public function test_from_form_data_extracts_required_fields(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData());

        $this->assertSame('jane@school.edu.au', $request->contactEmail);
        $this->assertSame('Jane', $request->contactFirstName);
        $this->assertSame('Smith', $request->contactLastName);
        $this->assertSame('VIC', $request->state);
        $this->assertSame('45 Collins Street', $request->address);
        $this->assertSame('Melbourne', $request->suburb);
        $this->assertSame('3000', $request->postcode);
    }

    public function test_from_form_data_extracts_participation_fields(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData([
            'participating_num_of_students' => '320',
            'num_of_students' => '500',
        ]));

        $this->assertSame(320, $request->participatingNumOfStudents);
        $this->assertSame(500, $request->numOfStudents);
    }

    public function test_calculates_participating_students_from_journal_and_planner(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData([
            'participating_num_of_students' => '',
            'journal' => '100',
            'planner' => '50',
        ]));

        $this->assertSame(150, $request->participatingNumOfStudents);
    }

    public function test_from_form_data_extracts_billing_fields(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData([
            'different_billing_contact' => 'Yes',
            'billing_contact_first_name' => 'Bob',
            'billing_contact_last_name' => 'Jones',
            'billing_contact_email' => 'bob@school.edu.au',
            'billing_contact_phone' => '0398765432',
        ]));

        $this->assertTrue($request->differentBillingContact);
        $this->assertSame('Bob', $request->billingContactFirstName);
        $this->assertSame('Jones', $request->billingContactLastName);
        $this->assertSame('bob@school.edu.au', $request->billingContactEmail);
        $this->assertSame('0398765432', $request->billingContactPhone);
    }

    public function test_billing_fields_default_when_no_billing_contact(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData([
            'different_billing_contact' => 'No',
        ]));

        $this->assertFalse($request->differentBillingContact);
        $this->assertNull($request->billingContactFirstName);
        $this->assertNull($request->billingContactLastName);
        $this->assertNull($request->billingContactEmail);
        $this->assertNull($request->billingContactPhone);
    }

    public function test_from_form_data_extracts_program_fields(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData([
            'engage' => 'Yes',
            'inspire' => 'No',
            'mental_health_funding' => 'Yes',
            'selected_year_levels' => ['Year 3', 'Year 4', 'Year 5'],
        ]));

        $this->assertSame('Yes', $request->engage);
        $this->assertSame('No', $request->inspire);
        $this->assertSame('Yes', $request->mentalHealthFunding);
        $this->assertSame(['Year 3', 'Year 4', 'Year 5'], $request->selectedYearLevels);
    }

    public function test_from_form_data_extracts_source_form(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData([
            'source_form' => 'Schools Confirmation 2026',
        ]));

        $this->assertSame('Schools Confirmation 2026', $request->sourceForm);
    }

    public function test_optional_fields_default_to_null(): void
    {
        $request = ConfirmationRequest::fromFormData([
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Test',
            'contact_last_name' => 'User',
            'state' => 'NSW',
            'address' => '1 George Street',
            'suburb' => 'Sydney',
            'postcode' => '2000',
        ]);

        $this->assertNull($request->contactPhone);
        $this->assertNull($request->schoolAccountNo);
        $this->assertFalse($request->schoolNameOtherSelected);
        $this->assertNull($request->numOfStudents);
        $this->assertNull($request->participatingNumOfStudents);
        $this->assertNull($request->sourceForm);
        $this->assertNull($request->engage);
        $this->assertNull($request->selectedYearLevels);
        $this->assertFalse($request->differentBillingContact);
    }

    public function test_throws_when_email_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact email must not be empty');

        ConfirmationRequest::fromFormData($this->makeFormData(['contact_email' => '']));
    }

    public function test_to_contact_creates_correct_contact(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData([
            'contact_phone' => '0412345678',
            'job_title' => 'Principal',
            'contact_type' => 'Decision Maker',
            'contact_newsletter' => 'Yes',
        ]));

        $contact = $request->toContact();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame('jane@school.edu.au', $contact->email);
        $this->assertSame('Jane', $contact->firstName);
        $this->assertSame('Smith', $contact->lastName);
        $this->assertSame('0412345678', $contact->phone);
        $this->assertSame('Principal', $contact->jobTitle);
        $this->assertSame('Decision Maker', $contact->type);
        $this->assertSame('Yes', $contact->newsletter);
    }

    public function test_to_organisation_creates_correct_organisation(): void
    {
        $request = ConfirmationRequest::fromFormData($this->makeFormData([
            'organisation_sub_type' => 'Primary',
            'num_of_students' => '500',
            'contact_lead_source' => 'Google',
        ]));

        $org = $request->toOrganisation();

        $this->assertInstanceOf(Organisation::class, $org);
        $this->assertSame('ACC123', $org->accountNo);
        $this->assertSame('VIC', $org->state);
        $this->assertSame('Primary', $org->subType);
        $this->assertSame(500, $org->numStudents);
        $this->assertSame('Google', $org->leadSource);
    }
}
