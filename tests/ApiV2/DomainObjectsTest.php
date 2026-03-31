<?php

use ApiV2\Domain\Contact;
use ApiV2\Domain\Schools\Deal;
use ApiV2\Domain\Enquiry;
use ApiV2\Domain\Organisation;
use PHPUnit\Framework\TestCase;

class DomainObjectsTest extends TestCase
{
    // --- Contact ---

    public function test_contact_from_form_data(): void
    {
        $data = [
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'contact_phone' => '0400000000',
            'job_title' => 'Principal',
        ];

        $contact = Contact::fromFormData($data);

        $this->assertSame('jane@school.edu.au', $contact->email);
        $this->assertSame('Jane', $contact->firstName);
        $this->assertSame('Smith', $contact->lastName);
        $this->assertSame('0400000000', $contact->phone);
        $this->assertSame('Principal', $contact->jobTitle);
    }

    public function test_contact_full_name(): void
    {
        $contact = new Contact(
            email: 'test@test.com',
            firstName: 'Jane',
            lastName: 'Smith',
        );

        $this->assertSame('Jane Smith', $contact->fullName());
    }

    public function test_contact_throws_on_empty_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Contact(email: '', firstName: 'Jane', lastName: 'Smith');
    }

    public function test_contact_from_form_data_with_optional_fields_empty(): void
    {
        $data = [
            'contact_email' => 'test@test.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
        ];

        $contact = Contact::fromFormData($data);

        $this->assertNull($contact->phone);
        $this->assertNull($contact->orgPhone);
        $this->assertNull($contact->jobTitle);
        $this->assertNull($contact->type);
        $this->assertNull($contact->newsletter);
    }

    // --- Organisation ---

    public function test_organisation_from_form_data_with_account_no(): void
    {
        $data = [
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
            'num_of_students' => '450',
        ];

        $org = Organisation::fromFormData($data);

        $this->assertSame('ACC123', $org->accountNo);
        $this->assertNull($org->name);
        $this->assertSame('VIC', $org->state);
        $this->assertSame(450, $org->numStudents);
    }

    public function test_organisation_from_form_data_with_school_name(): void
    {
        $data = [
            'school_name_other' => 'New School Name',
            'state' => 'NSW',
        ];

        $org = Organisation::fromFormData($data);

        $this->assertNull($org->accountNo);
        $this->assertSame('New School Name', $org->name);
        $this->assertSame('NSW', $org->state);
    }

    public function test_organisation_from_form_data_with_empty_data(): void
    {
        $org = Organisation::fromFormData([]);

        $this->assertNull($org->accountNo);
        $this->assertNull($org->name);
        $this->assertNull($org->state);
        $this->assertNull($org->numStudents);
    }

    // --- Deal ---

    public function test_deal_for_school_enquiry(): void
    {
        $deal = Deal::forSchoolEnquiry();

        $this->assertSame('2027 School Partnership Program', $deal->name);
        $this->assertSame('School', $deal->type);
        $this->assertSame('School - New', $deal->orgType);
        $this->assertSame('New', $deal->stage);
        $expectedCloseDate = date('d/m/Y', strtotime('+1 Week'));
        $this->assertSame($expectedCloseDate, $deal->closeDate);
    }

    // --- Enquiry ---

    public function test_enquiry_construction(): void
    {
        $enquiry = new Enquiry(
            subject: 'Jane Smith | Test School',
            body: 'Test enquiry',
            type: 'School',
            contactId: '4x123',
            assigneeId: '19x8',
        );

        $this->assertSame('Jane Smith | Test School', $enquiry->subject);
        $this->assertSame('Test enquiry', $enquiry->body);
        $this->assertSame('School', $enquiry->type);
        $this->assertSame('4x123', $enquiry->contactId);
        $this->assertSame('19x8', $enquiry->assigneeId);
    }

    public function test_captured_contact_construction(): void
    {
        $captured = new \ApiV2\Domain\CapturedContact(
            contactId: '4x100',
            organisationId: '3x200',
            assignedUserId: '19x1',
            formsCompleted: 'Form A |##| Form B',
        );

        $this->assertSame('4x100', $captured->contactId);
        $this->assertSame('3x200', $captured->organisationId);
        $this->assertSame('19x1', $captured->assignedUserId);
        $this->assertSame('Form A |##| Form B', $captured->formsCompleted);
    }

    public function test_organisation_details_construction(): void
    {
        $details = new \ApiV2\Domain\OrganisationDetails(
            organisationId: '3x200',
            name: 'Test School',
            assignedUserId: '19x1',
            salesEvents2025: 'Form A',
            confirmationStatus2024: '',
            confirmationStatus2025: '',
            confirmationStatus2026: 'Lead',
        );

        $this->assertSame('3x200', $details->organisationId);
        $this->assertSame('Test School', $details->name);
        $this->assertSame('19x1', $details->assignedUserId);
        $this->assertSame('Lead', $details->confirmationStatus2026);
    }

    public function test_organisation_details_with_assigned_user_id(): void
    {
        $details = new \ApiV2\Domain\OrganisationDetails(
            organisationId: '3x200',
            name: 'Test School',
            assignedUserId: '19x1',
            salesEvents2025: '',
            confirmationStatus2024: '',
            confirmationStatus2025: '',
            confirmationStatus2026: '',
        );

        $updated = $details->withAssignedUserId('19x8');

        $this->assertSame('19x8', $updated->assignedUserId);
        $this->assertSame('3x200', $updated->organisationId);
        $this->assertNotSame($details, $updated);
    }
}
