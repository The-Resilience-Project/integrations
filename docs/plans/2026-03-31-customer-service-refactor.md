# CustomerService Refactor — Explicit Steps with DTOs

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace CustomerService's mutable state and opaque method names with explicit steps and immutable DTOs, so handlers read as clear step-by-step workflows.

**Architecture:** Introduce two DTOs (`CapturedContact`, `OrganisationDetails`) in `Domain/`. Refactor `CustomerService` to be stateless — five public methods each making 0–1 CRM calls, returning/accepting DTOs. Update both handlers to call steps explicitly. Remove dead code from deleted registration handler.

**Tech Stack:** PHP 8.2, PHPUnit, PSR-4 autoloading under `ApiV2\` namespace.

---

### Task 1: Create CapturedContact DTO

**Files:**
- Create: `src/api-v2/Domain/CapturedContact.php`
- Test: `tests/ApiV2/DomainObjectsTest.php`

**Step 1: Write the failing test**

Add to `tests/ApiV2/DomainObjectsTest.php`:

```php
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
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter test_captured_contact_construction`
Expected: FAIL — class not found.

**Step 3: Write minimal implementation**

Create `src/api-v2/Domain/CapturedContact.php`:

```php
<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class CapturedContact
{
    public function __construct(
        public readonly string $contactId,
        public readonly string $organisationId,
        public readonly string $assignedUserId,
        public readonly string $formsCompleted,
    ) {
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter test_captured_contact_construction`
Expected: PASS

**Step 5: Commit**

```bash
git add src/api-v2/Domain/CapturedContact.php tests/ApiV2/DomainObjectsTest.php
git commit -m "Add CapturedContact DTO for customer capture result"
```

---

### Task 2: Create OrganisationDetails DTO

**Files:**
- Create: `src/api-v2/Domain/OrganisationDetails.php`
- Test: `tests/ApiV2/DomainObjectsTest.php`

**Step 1: Write the failing test**

Add to `tests/ApiV2/DomainObjectsTest.php`:

```php
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
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter test_organisation_details`
Expected: FAIL — class not found.

**Step 3: Write minimal implementation**

Create `src/api-v2/Domain/OrganisationDetails.php`:

```php
<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class OrganisationDetails
{
    public function __construct(
        public readonly string $organisationId,
        public readonly string $name,
        public readonly string $assignedUserId,
        public readonly string $salesEvents2025,
        public readonly string $confirmationStatus2024,
        public readonly string $confirmationStatus2025,
        public readonly string $confirmationStatus2026,
    ) {
    }

    public function withAssignedUserId(string $assignedUserId): self
    {
        return new self(
            organisationId: $this->organisationId,
            name: $this->name,
            assignedUserId: $assignedUserId,
            salesEvents2025: $this->salesEvents2025,
            confirmationStatus2024: $this->confirmationStatus2024,
            confirmationStatus2025: $this->confirmationStatus2025,
            confirmationStatus2026: $this->confirmationStatus2026,
        );
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter test_organisation_details`
Expected: PASS

**Step 5: Commit**

```bash
git add src/api-v2/Domain/OrganisationDetails.php tests/ApiV2/DomainObjectsTest.php
git commit -m "Add OrganisationDetails DTO with immutable withAssignedUserId"
```

---

### Task 3: Refactor CustomerService to stateless explicit methods

**Files:**
- Modify: `src/api-v2/Application/Schools/CustomerService.php`

This is the core refactor. Replace the mutable state and two opaque methods with five explicit methods. Remove dead code (`captureOtherContactInfo`, `setContactAndOrg`, `getContactName`, all private state fields).

**Step 1: Rewrite CustomerService**

Replace the entire contents of `src/api-v2/Application/Schools/CustomerService.php` with:

```php
<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Domain\AssigneeRules;
use ApiV2\Domain\CapturedContact;
use ApiV2\Domain\Contact;
use ApiV2\Domain\Organisation;
use ApiV2\Domain\OrganisationDetails;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class CustomerService
{
    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Deactivate all existing contacts with this email address in the CRM.
     */
    public function deactivateExistingContacts(string $email): void
    {
        $this->client->post('setContactsInactive', ['contactEmail' => $email]);
    }

    /**
     * Create or update a contact and organisation in the CRM.
     *
     * @param array<string, mixed> $data Raw request data
     */
    public function captureContact(Contact $contact, Organisation $organisation, array $data): CapturedContact
    {
        $payload = $this->buildCustomerPayload($contact, $organisation, $data);

        if (!empty($data['school_name_other_selected'])) {
            $payload['organisationName'] = $data['school_name_other'] ?? '';
            $response = $this->client->post('captureCustomerInfo', $payload);
        } else {
            $payload['organisationAccountNo'] = $data['school_account_no'] ?? '';
            $response = $this->client->post('captureCustomerInfoWithAccountNo', $payload);
        }

        $responseData = $response->result[0];

        return new CapturedContact(
            contactId: $responseData->id,
            organisationId: $responseData->account_id,
            assignedUserId: $responseData->assigned_user_id ?? '',
            formsCompleted: $responseData->cf_contacts_formscompleted ?? '',
        );
    }

    /**
     * Fetch organisation details from the CRM.
     */
    public function fetchOrganisationDetails(string $organisationId): OrganisationDetails
    {
        $response = $this->client->post('getOrgDetails', [
            'organisationId' => $organisationId,
        ], true);

        $org = $response->result[0];

        return new OrganisationDetails(
            organisationId: $organisationId,
            name: $org->accountname,
            assignedUserId: $org->assigned_user_id,
            salesEvents2025: $org->cf_accounts_2025salesevents ?? '',
            confirmationStatus2024: $org->cf_accounts_2024confirmationstatus ?? '',
            confirmationStatus2025: $org->cf_accounts_2025confirmationstatus ?? '',
            confirmationStatus2026: $org->cf_accounts_2026confirmationstatus ?? '',
        );
    }

    /**
     * Update the organisation's assignee (based on state routing) and append
     * the current form to sales event tracking.
     *
     * Returns updated OrganisationDetails (assignee may have changed).
     *
     * @param array<string, mixed> $data Raw request data (needs source_form, state)
     */
    public function updateOrgAssigneeAndSalesEvents(OrganisationDetails $orgDetails, array $data): OrganisationDetails
    {
        $requestBody = [];
        $state = $data['state'] ?? null;

        $newOrgAssignee = AssigneeRules::resolveOrgAssignee($orgDetails->assignedUserId, $state);
        if ($newOrgAssignee !== $orgDetails->assignedUserId) {
            $requestBody['assignee'] = $newOrgAssignee;
        }

        $currentForm = $data['source_form'] ?? '';
        if ($currentForm !== '') {
            $existingFormsArray = explode(' |##| ', $orgDetails->salesEvents2025);
            if (!in_array($currentForm, $existingFormsArray, true)) {
                $existingFormsArray[] = $currentForm;
                $requestBody['salesEvents2025'] = $existingFormsArray;
            }
        }

        if (count($requestBody) === 0) {
            return $orgDetails;
        }

        $requestBody['organisationId'] = $orgDetails->organisationId;
        $response = $this->client->post('updateOrganisation', $requestBody);
        $responseData = $response->result[0];

        return $orgDetails->withAssignedUserId($responseData->assigned_user_id);
    }

    /**
     * Update the contact's assignee (based on state routing) and append
     * the current form to forms-completed tracking.
     *
     * @param array<string, mixed> $data Raw request data (needs source_form, state)
     */
    public function updateContactAssigneeAndFormsCompleted(
        CapturedContact $captured,
        OrganisationDetails $orgDetails,
        array $data,
    ): void {
        $requestBody = [];
        $state = $data['state'] ?? null;

        $currentForm = $data['source_form'] ?? '';
        if ($currentForm !== '') {
            $existingFormsArray = explode(' |##| ', $captured->formsCompleted);
            if (!in_array($currentForm, $existingFormsArray, true)) {
                $existingFormsArray[] = $currentForm;
                $requestBody['contactLeadSource'] = $existingFormsArray;
            }
        }

        $newContactAssignee = AssigneeRules::resolveContactAssignee($orgDetails->assignedUserId, $state);
        if ($newContactAssignee !== $captured->assignedUserId) {
            $requestBody['assignee'] = $newContactAssignee;
        }

        if (count($requestBody) === 0) {
            return;
        }

        $requestBody['contactId'] = $captured->contactId;
        $this->client->post('updateContactById', $requestBody);
    }

    /**
     * Build the customer info payload for Vtiger.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildCustomerPayload(Contact $contact, Organisation $organisation, array $data): array
    {
        $payload = [
            'contactEmail' => $contact->email,
            'contactFirstName' => $contact->firstName,
            'contactLastName' => $contact->lastName,
            'organisationType' => 'School',
        ];

        if ($organisation->state) {
            $payload['state'] = $organisation->state;
        }
        if ($contact->type) {
            $payload['contactType'] = $contact->type;
        }
        if ($contact->phone) {
            $payload['contactPhone'] = $contact->phone;
        }
        if ($contact->orgPhone) {
            $payload['orgPhone'] = $contact->orgPhone;
        }
        if ($contact->newsletter) {
            $payload['newsletter'] = $contact->newsletter;
        }
        if ($contact->jobTitle) {
            $payload['jobTitle'] = $contact->jobTitle;
        }
        if ($organisation->numStudents) {
            $payload['organisationNumOfStudents'] = $organisation->numStudents;
        }
        if ($organisation->numEmployees) {
            $payload['organisationNumOfEmployees'] = $organisation->numEmployees;
        }
        if ($organisation->leadSource) {
            $payload['contactLeadSource'] = $organisation->leadSource;
        }
        if ($organisation->subType) {
            $payload['organisationSubType'] = $organisation->subType;
        }

        return $payload;
    }
}
```

**Note:** The old `buildCustomerPayload` had a `$this->organisationName` check — this was always null during the capture flow (it was only populated _after_ `fetchOrganisationDetails`), so removing it changes nothing.

**Step 2: Run `make analyse` to check for static analysis errors**

Run: `make analyse`
Expected: PASS (handlers not yet updated, but they're not autoloaded by PHPStan at level 1 if they have no type errors in isolation — verify and fix any issues).

**Step 3: Commit**

```bash
git add src/api-v2/Application/Schools/CustomerService.php
git commit -m "Refactor CustomerService to stateless explicit methods with DTOs"
```

---

### Task 4: Update SubmitEnquiryHandler to use explicit steps

**Files:**
- Modify: `src/api-v2/Application/Schools/SubmitEnquiryHandler.php`

**Step 1: Rewrite the handler**

Replace the contents of `src/api-v2/Application/Schools/SubmitEnquiryHandler.php` with:

```php
<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Domain\AssigneeRules;
use ApiV2\Domain\Contact;
use ApiV2\Domain\Deal;
use ApiV2\Domain\Enquiry;
use ApiV2\Domain\Organisation;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitEnquiryHandler
{
    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Handle a school enquiry submission.
     *
     * @param array<string, mixed> $data Raw request data
     */
    public function handle(array $data): bool
    {
        $contact = Contact::fromFormData($data);
        $organisation = Organisation::fromFormData($data);

        // 1. Deactivate existing contacts with this email
        $customerService = new CustomerService($this->client);
        $customerService->deactivateExistingContacts($contact->email);

        // 2. Create/update contact and organisation in CRM
        $captured = $customerService->captureContact($contact, $organisation, $data);

        // 3. Fetch organisation details (assignee, sales events, etc.)
        $orgDetails = $customerService->fetchOrganisationDetails($captured->organisationId);

        // 4. Update org assignee routing and sales event tracking
        $orgDetails = $customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $data);

        // 5. Update contact assignee routing and forms-completed tracking
        $customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $data);

        // 6. Create deal if this is a new school
        if (AssigneeRules::isNewSchool($orgDetails->assignedUserId)) {
            $deal = Deal::forSchoolEnquiry();
            $state = $data['state'] ?? null;

            $dealPayload = [
                'dealName' => $deal->name,
                'dealType' => $deal->type,
                'dealOrgType' => $deal->orgType,
                'dealStage' => $deal->stage,
                'dealCloseDate' => $deal->closeDate,
                'contactId' => $captured->contactId,
                'organisationId' => $captured->organisationId,
                'assignee' => AssigneeRules::resolveContactAssignee(
                    $orgDetails->assignedUserId,
                    $state,
                ),
            ];

            if (!empty($data['participating_num_of_students'])) {
                $dealPayload['dealNumOfParticipants'] = $data['participating_num_of_students'];
            } elseif (!empty($data['num_of_students'])) {
                $dealPayload['dealNumOfParticipants'] = $data['num_of_students'];
            }

            if (!empty($data['state'])) {
                $dealPayload['dealState'] = $data['state'];
            }

            $this->client->post('getOrCreateDeal', $dealPayload);
        }

        // 7. Create the enquiry
        $orgName = $orgDetails->name;
        $enquirySubject = $contact->fullName();
        if ($orgName !== null) {
            $enquirySubject .= ' | '.$orgName;
        }

        $enquiry = new Enquiry(
            subject: $enquirySubject,
            body: !empty($data['enquiry']) ? $data['enquiry'] : 'Conference Enquiry',
            type: 'School',
            contactId: $captured->contactId,
            assigneeId: AssigneeRules::resolveEnquiryAssignee(
                $orgDetails->assignedUserId,
                $data['state'] ?? null,
            ),
        );

        $this->client->post('createEnquiry', [
            'enquirySubject' => $enquiry->subject,
            'enquiryBody' => $enquiry->body,
            'contactId' => $enquiry->contactId,
            'assignee' => $enquiry->assigneeId,
            'enquiryType' => $enquiry->type,
        ]);

        return true;
    }
}
```

**Step 2: Run existing tests**

Run: `vendor/bin/phpunit tests/ApiV2/SubmitEnquiryHandlerTest.php`
Expected: All 10 tests PASS — same CRM call sequence, same behaviour.

**Step 3: Commit**

```bash
git add src/api-v2/Application/Schools/SubmitEnquiryHandler.php
git commit -m "Update SubmitEnquiryHandler to use explicit CustomerService steps"
```

---

### Task 5: Update SubmitPrizePackHandler to use explicit steps

**Files:**
- Modify: `src/api-v2/Application/Schools/SubmitPrizePackHandler.php`

**Step 1: Rewrite the handler**

Replace the contents of `src/api-v2/Application/Schools/SubmitPrizePackHandler.php` with:

```php
<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Domain\Contact;
use ApiV2\Domain\Organisation;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitPrizePackHandler
{
    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Handle a school prize pack submission.
     *
     * @param array<string, mixed> $data Raw request data
     */
    public function handle(array $data): bool
    {
        $contact = Contact::fromFormData($data);
        $organisation = Organisation::fromFormData($data);

        // 1. Deactivate existing contacts with this email
        $customerService = new CustomerService($this->client);
        $customerService->deactivateExistingContacts($contact->email);

        // 2. Create/update contact and organisation in CRM
        $captured = $customerService->captureContact($contact, $organisation, $data);

        // 3. Fetch organisation details
        $orgDetails = $customerService->fetchOrganisationDetails($captured->organisationId);

        // 4. Update org assignee routing and sales event tracking
        $orgDetails = $customerService->updateOrgAssigneeAndSalesEvents($orgDetails, $data);

        // 5. Update contact assignee routing and forms-completed tracking
        $customerService->updateContactAssigneeAndFormsCompleted($captured, $orgDetails, $data);

        // 6. Mark org as 2026 lead if confirmation status is not yet set
        if (empty($orgDetails->confirmationStatus2026)) {
            $this->client->post('updateOrganisation', [
                'organisationId' => $orgDetails->organisationId,
                'organisation2026Status' => 'Lead',
            ]);
        }

        return true;
    }
}
```

**Step 2: Run existing tests**

Run: `vendor/bin/phpunit tests/ApiV2/SubmitPrizePackHandlerTest.php`
Expected: All 6 tests PASS.

**Step 3: Commit**

```bash
git add src/api-v2/Application/Schools/SubmitPrizePackHandler.php
git commit -m "Update SubmitPrizePackHandler to use explicit CustomerService steps"
```

---

### Task 6: Run full check suite

**Step 1: Run all checks**

Run: `make check`
Expected: Lint PASS, PHPStan PASS, all tests PASS (except the 2 pre-existing deal name year failures).

**Step 2: Fix any issues**

If lint fails, run `make fix` then re-run `make check`.

**Step 3: Commit any fixes**

```bash
git add -A
git commit -m "Fix lint/analyse issues from CustomerService refactor"
```
