<?php

use PHPUnit\Framework\TestCase;

class EnquiryRequestTest extends TestCase
{
    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'service_type' => 'School',
            'state' => 'VIC',
            'source_form' => 'Website Enquiry',
            'school_account_no' => 'ACC123',
        ], $overrides);
    }

    // -- Basic creation --

    public function test_creates_from_school_data(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData());

        $this->assertEquals('School', $request->serviceType);
        $this->assertEquals('test@example.com', $request->contact->email);
        $this->assertEquals('VIC', $request->state);
        $this->assertEquals('ACC123', $request->accountNo);
        $this->assertNull($request->organisationName);
        $this->assertFalse($request->isNewOrganisation);
    }

    public function test_creates_from_workplace_data(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'hr@company.com',
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'service_type' => 'Workplace',
            'organisation_name' => 'ACME Corp',
            'state' => 'NSW',
        ]);

        $this->assertEquals('Workplace', $request->serviceType);
        $this->assertEquals('ACME Corp', $request->organisationName);
        $this->assertNull($request->accountNo);
    }

    public function test_creates_from_early_years_data(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'director@kindergarten.com',
            'contact_first_name' => 'Sarah',
            'contact_last_name' => 'Brown',
            'service_type' => 'Early Years',
            'earlyyears_account_no' => 'EY456',
            'state' => 'QLD',
        ]);

        $this->assertEquals('Early Years', $request->serviceType);
        $this->assertEquals('EY456', $request->accountNo);
    }

    public function test_creates_from_general_data(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
            'service_type' => 'General',
        ]);

        $this->assertEquals('General', $request->serviceType);
        $this->assertNull($request->accountNo);
        $this->assertNull($request->organisationName);
    }

    public function test_defaults_to_general_when_no_service_type(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
        ]);

        $this->assertEquals('General', $request->serviceType);
    }

    // -- New organisation detection --

    public function test_school_new_org(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData([
            'school_name_other_selected' => true,
            'school_name_other' => 'Brand New School',
            'school_account_no' => '',
        ]));

        $this->assertTrue($request->isNewOrganisation);
        $this->assertEquals('Brand New School', $request->organisationName);
        $this->assertNull($request->accountNo);
    }

    public function test_workplace_new_org(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'hr@company.com',
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'service_type' => 'Workplace',
            'workplace_name_other_selected' => true,
            'workplace_name_other' => 'New Corp',
        ]);

        $this->assertTrue($request->isNewOrganisation);
        $this->assertEquals('New Corp', $request->organisationName);
    }

    public function test_early_years_new_org(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'director@kinder.com',
            'contact_first_name' => 'Sarah',
            'contact_last_name' => 'Brown',
            'service_type' => 'Early Years',
            'service_name_other_selected' => true,
            'earlyyears_name_other' => 'New Kinder',
        ]);

        $this->assertTrue($request->isNewOrganisation);
        $this->assertEquals('New Kinder', $request->organisationName);
    }

    // -- Controller class resolution --

    public function test_controller_class_school(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData());
        $this->assertEquals('SchoolVTController', $request->controllerClass());
    }

    public function test_controller_class_workplace(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData(['service_type' => 'Workplace', 'organisation_name' => 'Test']));
        $this->assertEquals('WorkplaceVTController', $request->controllerClass());
    }

    public function test_controller_class_early_years(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData(['service_type' => 'Early Years']));
        $this->assertEquals('EarlyYearsVTController', $request->controllerClass());
    }

    public function test_controller_class_imperfects(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData(['service_type' => 'Imperfects']));
        $this->assertEquals('ImperfectsVTController', $request->controllerClass());
    }

    public function test_controller_class_general_fallback(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData(['service_type' => 'Unknown']));
        $this->assertEquals('GeneralVTController', $request->controllerClass());
    }

    // -- Organisation display name --

    public function test_display_name_from_org_name(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData([
            'service_type' => 'Workplace',
            'organisation_name' => 'ACME Corp',
        ]));

        $this->assertEquals('ACME Corp', $request->organisationDisplayName());
    }

    public function test_display_name_from_account_no(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData());

        $this->assertEquals('ACC123', $request->organisationDisplayName());
    }

    public function test_display_name_unknown_fallback(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'person@email.com',
            'contact_first_name' => 'Alex',
            'contact_last_name' => 'Jones',
            'service_type' => 'General',
        ]);

        $this->assertEquals('unknown', $request->organisationDisplayName());
    }

    // -- Backward compatibility --

    public function test_to_array_returns_raw_data(): void
    {
        $data = $this->baseData();
        $request = EnquiryRequest::fromFormData($data);

        $this->assertEquals($data, $request->toArray());
    }

    // -- Validation --

    public function test_throws_on_missing_contact_email(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EnquiryRequest::fromFormData([
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'service_type' => 'School',
        ]);
    }

    public function test_throws_on_missing_contact_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EnquiryRequest::fromFormData([
            'contact_email' => 'test@example.com',
            'service_type' => 'School',
        ]);
    }

    // -- Optional fields --

    public function test_optional_fields_populated(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData([
            'num_of_students' => '500',
            'enquiry' => 'Interested in the program',
            'contact_lead_source' => 'Google',
        ]));

        $this->assertEquals('500', $request->numOfStudents);
        $this->assertEquals('Interested in the program', $request->enquiryBody);
        $this->assertEquals('Google', $request->contactLeadSource);
    }

    public function test_optional_fields_null_when_missing(): void
    {
        $request = EnquiryRequest::fromFormData($this->baseData());

        $this->assertNull($request->numOfStudents);
        $this->assertNull($request->enquiryBody);
        $this->assertNull($request->numOfEmployees);
    }
}
