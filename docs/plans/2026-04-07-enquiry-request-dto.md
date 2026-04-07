# EnquiryRequest DTO Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the raw `array $data` flowing through the enquiry endpoint with a typed `EnquiryRequest` DTO, making request fields explicit and removing raw array access from `CustomerService`.

**Architecture:** Create an `EnquiryRequest` value object in Domain with a `fromFormData()` factory (matching existing `Contact`/`Organisation` pattern). Refactor `CustomerService` to accept explicit parameters instead of `array $data`, then update both `SubmitEnquiryHandler` and `SubmitMoreInfoHandler` to pass typed values.

**Tech Stack:** PHP 8.2, PHPUnit, PSR-12 (PHP-CS-Fixer)

---

### Task 1: Create `EnquiryRequest` DTO

**Files:**
- Create: `src/api-v2/Domain/EnquiryRequest.php`

**Step 1: Create the DTO**

```php
<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class EnquiryRequest
{
    public function __construct(
        // Contact fields (required)
        public readonly string $contactEmail,
        public readonly string $contactFirstName,
        public readonly string $contactLastName,

        // Contact fields (optional)
        public readonly ?string $contactPhone = null,
        public readonly ?string $orgPhone = null,
        public readonly ?string $jobTitle = null,
        public readonly ?string $contactType = null,
        public readonly ?string $contactNewsletter = null,

        // Organisation fields (optional)
        public readonly ?string $schoolAccountNo = null,
        public readonly ?string $schoolNameOther = null,
        public readonly bool $schoolNameOtherSelected = false,
        public readonly ?string $state = null,
        public readonly ?string $organisationSubType = null,
        public readonly ?int $numOfStudents = null,
        public readonly ?int $numOfEmployees = null,
        public readonly ?string $contactLeadSource = null,

        // Enquiry-specific fields (optional)
        public readonly ?string $enquiry = null,
        public readonly ?int $participatingNumOfStudents = null,
    ) {
        if (trim($this->contactEmail) === '') {
            throw new \InvalidArgumentException('Contact email must not be empty');
        }
    }

    /**
     * Build an EnquiryRequest from raw form submission data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromFormData(array $data): self
    {
        return new self(
            contactEmail: (string) ($data['contact_email'] ?? ''),
            contactFirstName: (string) ($data['contact_first_name'] ?? ''),
            contactLastName: (string) ($data['contact_last_name'] ?? ''),
            contactPhone: !empty($data['contact_phone']) ? (string) $data['contact_phone'] : null,
            orgPhone: !empty($data['org_phone']) ? (string) $data['org_phone'] : null,
            jobTitle: !empty($data['job_title']) ? (string) $data['job_title'] : null,
            contactType: !empty($data['contact_type']) ? (string) $data['contact_type'] : null,
            contactNewsletter: !empty($data['contact_newsletter']) ? (string) $data['contact_newsletter'] : null,
            schoolAccountNo: !empty($data['school_account_no']) ? (string) $data['school_account_no'] : null,
            schoolNameOther: !empty($data['school_name_other']) ? (string) $data['school_name_other'] : null,
            schoolNameOtherSelected: !empty($data['school_name_other_selected']),
            state: !empty($data['state']) ? (string) $data['state'] : null,
            organisationSubType: !empty($data['organisation_sub_type']) ? (string) $data['organisation_sub_type'] : null,
            numOfStudents: !empty($data['num_of_students']) ? (int) $data['num_of_students'] : null,
            numOfEmployees: !empty($data['num_of_employees']) ? (int) $data['num_of_employees'] : null,
            contactLeadSource: !empty($data['contact_lead_source']) ? (string) $data['contact_lead_source'] : null,
            enquiry: !empty($data['enquiry']) ? (string) $data['enquiry'] : null,
            participatingNumOfStudents: !empty($data['participating_num_of_students']) ? (int) $data['participating_num_of_students'] : null,
        );
    }

    /**
     * Build a Contact domain object from this request.
     */
    public function toContact(): Contact
    {
        return new Contact(
            email: $this->contactEmail,
            firstName: $this->contactFirstName,
            lastName: $this->contactLastName,
            phone: $this->contactPhone,
            orgPhone: $this->orgPhone,
            jobTitle: $this->jobTitle,
            type: $this->contactType,
            newsletter: $this->contactNewsletter,
        );
    }

    /**
     * Build an Organisation domain object from this request.
     */
    public function toOrganisation(): Organisation
    {
        return new Organisation(
            accountNo: $this->schoolAccountNo,
            name: $this->schoolNameOther,
            state: $this->state,
            subType: $this->organisationSubType,
            numStudents: $this->numOfStudents,
            numEmployees: $this->numOfEmployees,
            leadSource: $this->contactLeadSource,
        );
    }
}
```

**Step 2: Run lint + analyse**

Run: `make lint && make analyse`
Expected: PASS (no errors)

**Step 3: Commit**

```bash
git add src/api-v2/Domain/EnquiryRequest.php
git commit -m "Add EnquiryRequest DTO with fromFormData factory"
```

---

### Task 2: Write tests for `EnquiryRequest`

**Files:**
- Create: `tests/ApiV2/EnquiryRequestTest.php`

**Step 1: Write the tests**

```php
<?php

use ApiV2\Domain\EnquiryRequest;
use PHPUnit\Framework\TestCase;

class EnquiryRequestTest extends TestCase
{
    private function makeFormData(array $overrides = []): array
    {
        return array_merge([
            'contact_email' => 'jane@school.edu.au',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'school_account_no' => 'ACC123',
            'state' => 'VIC',
            'enquiry' => 'Interested in the program',
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
        $request = EnquiryRequest::fromFormData($this->makeFormData([
            'contact_phone' => '0412345678',
            'org_phone' => '0398765432',
            'job_title' => 'Principal',
            'contact_type' => 'Decision Maker',
            'contact_newsletter' => 'Yes',
        ]));

        $this->assertSame('0412345678', $request->contactPhone);
        $this->assertSame('0398765432', $request->orgPhone);
        $this->assertSame('Principal', $request->jobTitle);
        $this->assertSame('Decision Maker', $request->contactType);
        $this->assertSame('Yes', $request->contactNewsletter);
    }

    public function test_from_form_data_extracts_optional_organisation_fields(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData([
            'school_name_other' => 'New School',
            'school_name_other_selected' => true,
            'organisation_sub_type' => 'Primary',
            'num_of_students' => '500',
            'num_of_employees' => '30',
            'contact_lead_source' => 'Google',
        ]));

        $this->assertSame('New School', $request->schoolNameOther);
        $this->assertTrue($request->schoolNameOtherSelected);
        $this->assertSame('Primary', $request->organisationSubType);
        $this->assertSame(500, $request->numOfStudents);
        $this->assertSame(30, $request->numOfEmployees);
        $this->assertSame('Google', $request->contactLeadSource);
    }

    public function test_from_form_data_extracts_enquiry_specific_fields(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData([
            'participating_num_of_students' => '200',
        ]));

        $this->assertSame('Interested in the program', $request->enquiry);
        $this->assertSame(200, $request->participatingNumOfStudents);
    }

    public function test_optional_fields_default_to_null(): void
    {
        $request = EnquiryRequest::fromFormData([
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Test',
            'contact_last_name' => 'User',
        ]);

        $this->assertNull($request->contactPhone);
        $this->assertNull($request->schoolAccountNo);
        $this->assertFalse($request->schoolNameOtherSelected);
        $this->assertNull($request->enquiry);
        $this->assertNull($request->participatingNumOfStudents);
    }

    public function test_throws_when_email_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Contact email must not be empty');

        EnquiryRequest::fromFormData($this->makeFormData(['contact_email' => '']));
    }

    public function test_throws_when_email_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EnquiryRequest::fromFormData([
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
        ]);
    }

    public function test_to_contact_creates_correct_contact(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData([
            'contact_phone' => '0412345678',
            'job_title' => 'Principal',
        ]));

        $contact = $request->toContact();

        $this->assertSame('jane@school.edu.au', $contact->email);
        $this->assertSame('Jane', $contact->firstName);
        $this->assertSame('Smith', $contact->lastName);
        $this->assertSame('0412345678', $contact->phone);
        $this->assertSame('Principal', $contact->jobTitle);
    }

    public function test_to_organisation_creates_correct_organisation(): void
    {
        $request = EnquiryRequest::fromFormData($this->makeFormData([
            'num_of_students' => '500',
            'organisation_sub_type' => 'Primary',
        ]));

        $org = $request->toOrganisation();

        $this->assertSame('ACC123', $org->accountNo);
        $this->assertSame('VIC', $org->state);
        $this->assertSame(500, $org->numStudents);
        $this->assertSame('Primary', $org->subType);
    }
}
```

**Step 2: Run the tests**

Run: `vendor/bin/phpunit tests/ApiV2/EnquiryRequestTest.php`
Expected: All tests pass

**Step 3: Commit**

```bash
git add tests/ApiV2/EnquiryRequestTest.php
git commit -m "Add EnquiryRequest DTO tests"
```

---

### Task 3: Refactor `CustomerService` to accept explicit parameters

**Files:**
- Modify: `src/api-v2/Application/CustomerService.php`

This is the key change — replace `array $data` params with explicit typed arguments. Three methods need updating:

**Step 1: Update `captureContact` signature**

Change from:
```php
public function captureContact(Contact $contact, Organisation $organisation, array $data): CapturedContact
```

To:
```php
public function captureContact(
    Contact $contact,
    Organisation $organisation,
    string $sourceForm,
): CapturedContact
```

Update the method body:
- Replace `$data['school_name_other_selected']` with `$organisation->name !== null` (if a name is set, use the name path)
- Replace `$data['school_name_other'] ?? ''` with `$organisation->name ?? ''`
- Replace `$data['school_account_no'] ?? ''` with `$organisation->accountNo ?? ''`

Update `buildCustomerPayload` signature from `array $data` to `string $sourceForm`:
- Replace `$data['source_form']` with `$sourceForm`

**Step 2: Update `updateOrgAssigneeAndSalesEvents` signature**

Change from:
```php
public function updateOrgAssigneeAndSalesEvents(OrganisationDetails $orgDetails, array $data): OrganisationDetails
```

To:
```php
public function updateOrgAssigneeAndSalesEvents(
    OrganisationDetails $orgDetails,
    string $sourceForm,
    ?string $state,
): OrganisationDetails
```

Update the method body:
- Replace `$data['state'] ?? null` with `$state`
- Replace `$data['source_form'] ?? ''` with `$sourceForm`

**Step 3: Update `updateContactAssigneeAndFormsCompleted` signature**

Change from:
```php
public function updateContactAssigneeAndFormsCompleted(CapturedContact $captured, OrganisationDetails $orgDetails, array $data): void
```

To:
```php
public function updateContactAssigneeAndFormsCompleted(
    CapturedContact $captured,
    OrganisationDetails $orgDetails,
    string $sourceForm,
    ?string $state,
): void
```

Update the method body:
- Replace `$data['state'] ?? null` with `$state`
- Replace `$data['source_form'] ?? ''` with `$sourceForm`

**Step 4: Run lint + analyse**

Run: `make lint && make analyse`
Expected: Failures expected — callers not yet updated

---

### Task 4: Update `SubmitEnquiryHandler` to use `EnquiryRequest`

**Files:**
- Modify: `src/api-v2/Application/Schools/SubmitEnquiryHandler.php`

**Step 1: Change the handler signature and body**

Change `handle(array $data)` to `handle(EnquiryRequest $request)`.

Key changes in the method body:
- Replace `$data['source_form'] = 'Enquiry'` with `$sourceForm = 'Enquiry'`
- Replace `Contact::fromFormData($data)` with `$request->toContact()`
- Replace `Organisation::fromFormData($data)` with `$request->toOrganisation()`
- Update `captureContact` call: pass `$sourceForm` instead of `$data`
- Update `updateOrgAssigneeAndSalesEvents` call: pass `$sourceForm, $request->state`
- Update `updateContactAssigneeAndFormsCompleted` call: pass `$sourceForm, $request->state`
- Replace `$data['state']` with `$request->state`
- Replace `$data['participating_num_of_students']` with `$request->participatingNumOfStudents`
- Replace `$data['num_of_students']` with `$request->numOfStudents`
- Replace `$data['enquiry']` with `$request->enquiry`

Add import: `use ApiV2\Domain\EnquiryRequest;`

**Step 2: Run lint + analyse**

Run: `make lint && make analyse`
Expected: May still have failures from MoreInfo handler (next task)

---

### Task 5: Update `SubmitMoreInfoHandler` to pass explicit params

**Files:**
- Modify: `src/api-v2/Application/Schools/SubmitMoreInfoHandler.php`

**Note:** This handler keeps its `array $data` signature for now — it just needs to pass explicit params to the updated `CustomerService` methods.

**Step 1: Update CustomerService calls**

- Replace `$customerService->captureContact($contact, $organisation, $data)` with `$customerService->captureContact($contact, $organisation, $sourceForm)` where `$sourceForm = 'More Info 2026'` (already set as `$data['source_form']`)
- Replace `$customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $data)` with `$customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $sourceForm, $data['state'] ?? null)`
- Replace `$customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $data)` with `$customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $sourceForm, $data['state'] ?? null)`

**Step 2: Run lint + analyse**

Run: `make fix && make analyse`
Expected: PASS

**Step 3: Commit CustomerService + both handlers**

```bash
git add src/api-v2/Application/CustomerService.php src/api-v2/Application/Schools/SubmitEnquiryHandler.php src/api-v2/Application/Schools/SubmitMoreInfoHandler.php
git commit -m "Refactor CustomerService to accept explicit params instead of raw array"
```

---

### Task 6: Update the enquiry endpoint

**Files:**
- Modify: `src/api-v2/endpoints/schools/enquiry.php`

**Step 1: Update the endpoint to create `EnquiryRequest`**

Replace:
```php
$data = get_request_data();
```
and the handler call with:
```php
$data = get_request_data();

$request = EnquiryRequest::fromFormData($data);
```

Add import: `use ApiV2\Domain\EnquiryRequest;`

Update handler call from `$handler->handle($data)` to `$handler->handle($request)`.

Update the logging to use `$request->contactEmail` and `$request->schoolAccountNo` instead of `$data['contact_email']` and `$data['school_account_no']`.

**Step 2: Run lint + analyse**

Run: `make fix && make analyse`
Expected: PASS

**Step 3: Commit**

```bash
git add src/api-v2/endpoints/schools/enquiry.php
git commit -m "Update enquiry endpoint to use EnquiryRequest DTO"
```

---

### Task 7: Update existing handler tests

**Files:**
- Modify: `tests/ApiV2/SubmitEnquiryHandlerTest.php`

**Step 1: Update `makeSchoolData` to return `EnquiryRequest`**

Rename `makeSchoolData` to `makeRequest` and return `EnquiryRequest::fromFormData(...)` instead of a raw array.

Add import: `use ApiV2\Domain\EnquiryRequest;`

Update all `$handler->handle($this->makeSchoolData(...))` calls to `$handler->handle($this->makeRequest(...))`.

**Step 2: Run all tests**

Run: `make test`
Expected: All tests pass

**Step 3: Run full check suite**

Run: `make check`
Expected: All checks pass (lint + analyse + test)

**Step 4: Commit**

```bash
git add tests/ApiV2/SubmitEnquiryHandlerTest.php
git commit -m "Update enquiry handler tests to use EnquiryRequest DTO"
```

---

### Task 8: Final verification

**Step 1: Run `make check`**

Run: `make check`
Expected: All checks pass

**Step 2: Review all changes**

Run: `git diff main --stat` and `git log --oneline main..HEAD`
Verify: All changes are consistent and no raw `$data` access remains in the enquiry flow.
