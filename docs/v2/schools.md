# API v2 — Schools Endpoints

API v2 introduces a schools-specific URL structure with a DDD-lite architecture. Only schools have v2 endpoints; all other service types (Workplace, Early Years, General) continue to use v1.

> **Note:** The v1 school enquiry endpoint (`POST /api/enquiry.php` with `service_type=School`) is deprecated. All school enquiries and registrations should use the v2 endpoints below.

## Key Differences from v1

| | v1 | v2 |
|---|---|---|
| **URL pattern** | `/api/enquiry.php` | `/api/v2/schools/enquiry` |
| **Service type routing** | `service_type` field in POST body | URL path determines service type |
| **Architecture** | Controller + traits | Domain objects + Application handlers |
| **Testability** | Reflection-based tests | Interface-based stubs |

## Overview

| Endpoint | Method | URL | Description |
|----------|--------|-----|-------------|
| School Enquiry | POST | `/api/v2/schools/enquiry` | Submit a school enquiry |
| School More Info | POST | `/api/v2/schools/more-info` | Request more info — registers for event or creates deal based on school size |
| School Registration | POST | `/api/v2/schools/registration` | Register for a live info session (new schools get deal + event registration, existing schools get enquiry) |

---

## POST /api/v2/schools/enquiry

Submit a school enquiry. Captures customer info in CRM, creates a deal for new schools, and creates an enquiry record.

### Request

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `contact_email` | string | Yes | Contact's email address |
| `contact_first_name` | string | Yes | Contact's first name |
| `contact_last_name` | string | Yes | Contact's last name |
| `contact_phone` | string | No | Contact's phone number |
| `org_phone` | string | No | Organisation phone number |
| `job_title` | string | No | Contact's job title |
| `school_account_no` | string | Conditional | Existing school's Vtiger account number (when school is in CRM) |
| `school_name_other` | string | Conditional | New school name (when school is not in CRM) |
| `school_name_other_selected` | string | Conditional | Flag indicating a new school name was entered (truthy value) |
| `state` | string | No | Australian state (VIC, NSW, QLD, etc.). Used for assignee routing |
| `enquiry` | string | No | Enquiry text. Defaults to `"Conference Enquiry"` |
| `source_form` | string | No | Name of the originating form |
| `num_of_students` | integer | No | Number of students at the school |
| `organisation_sub_type` | string | No | Organisation sub-type |
| `contact_lead_source` | string | No | Lead source for the contact |

### Control Flow

```mermaid
flowchart TD
    A[POST /api/v2/schools/enquiry] --> B[Deactivate existing contacts]
    B --> C{school_name_other_selected?}

    C -->|Yes| D[captureCustomerInfo<br/>with organisation name]
    C -->|No| E[captureCustomerInfoWithAccountNo<br/>with account number]

    D --> F[Get organisation details]
    E --> F

    F --> G[Update organisation<br/>assignee + sales events]
    G --> H[Update contact<br/>assignee + forms completed]

    H --> I{isNewSchool?<br/>Org assignee in<br/>MADDIE/LAURA/VICTOR/HELENOR/BRENDAN}

    I -->|Yes| J["getOrCreateDeal<br/>Stage: 'New'<br/>Close: +2 weeks"]
    I -->|No| K[Skip deal creation]

    J --> L[createEnquiry]
    K --> L

    L --> M["Response: {status: success}"]

    style A fill:#4a90d9,color:#fff
    style J fill:#f5a623,color:#fff
    style L fill:#7ed321,color:#fff
```

### Assignee Routing

| Org Assignee | State | Enquiry Assignee |
|-------------|-------|-----------------|
| `null` | Any | LAURA |
| Not MADDIE | Any | Keep org assignee |
| MADDIE | NSW, QLD | BRENDAN |
| MADDIE | Other | LAURA |

### Response

```json
{"status": "success"}
```
or
```json
{"status": "fail", "message": "Error processing school enquiry: ..."}
```

### Scenarios

1. **New school enquiry (VIC)** — New school submits enquiry. Deal created with stage "New". Enquiry assigned to LAURA. → `Enquiry (New School).request.yaml`
2. **Existing school enquiry (NSW)** — School with dedicated SPM submits enquiry. No deal created. Enquiry assigned to SPM. → `Enquiry (Existing School).request.yaml`

---

## POST /api/v2/schools/more-info

Request more information about school programs. Captures customer info in CRM, then branches based on student count: large schools (>= 500 students) get a deal created, while smaller schools are registered for a more-info event.

> **v1 equivalent:** This endpoint supersedes the v1 Info Session Recording flow (`POST /api/register.php` with `source_form=Info Session Recording`), though the business logic differs.

### Request

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `contact_email` | string | Yes | Contact's email address |
| `contact_first_name` | string | Yes | Contact's first name |
| `contact_last_name` | string | Yes | Contact's last name |
| `contact_phone` | string | No | Contact's phone number |
| `org_phone` | string | No | Organisation phone number |
| `job_title` | string | No | Contact's job title |
| `contact_type` | string | No | Contact type (e.g. "Teacher", "Principal") |
| `contact_newsletter` | string | No | Newsletter opt-in |
| `school_account_no` | string | Conditional | Existing school's Vtiger account number |
| `school_name_other` | string | Conditional | New school name (when school is not in CRM) |
| `school_name_other_selected` | string | Conditional | Flag indicating a new school name was entered |
| `state` | string | No | Australian state for assignee routing |
| `organisation_sub_type` | string | No | Organisation sub-type |
| `num_of_students` | integer | No | Number of students — determines branching (>= 500 → deal, < 500 → event registration) |
| `num_of_employees` | integer | No | Number of employees |
| `contact_lead_source` | string | No | Lead source for the contact |
| `source_form` | string | No | Name of the originating form. Defaults to `"More Info 2026"` |

### Control Flow

```mermaid
flowchart TD
    A[POST /api/v2/schools/more-info] --> B[Deactivate existing contacts]
    B --> C[Capture customer info in CRM]
    C --> D[Get organisation details]
    D --> E[Update org assignee + sales events]
    E --> F[Update contact assignee + forms completed]
    F --> G{num_of_students >= 500?}

    G -->|Yes| H{isNewSchool?}
    H -->|Yes| I["getOrCreateDeal<br/>Stage: 'New'<br/>Close: +2 weeks"]
    H -->|No| J[Skip deal creation]

    G -->|No| K[Register contact for<br/>more-info event]
    K --> L["updateContactById<br/>lifecycleStage = 'Lead'<br/>contactStatus = 'Hot'"]

    I --> M["Response: {status: success}"]
    J --> M
    L --> M

    style A fill:#4a90d9,color:#fff
    style I fill:#f5a623,color:#fff
    style K fill:#7ed321,color:#fff
```

### Response

```json
{"status": "success"}
```
or
```json
{"status": "fail", "message": "Error processing more info request: ..."}
```

### Scenarios

1. **More info (new school, >= 500 students)** — Deal created with stage "New". → `v2 School More Info (New School - Deal Creation).request.yaml`
2. **More info (new school, < 500 students)** — Contact registered for more-info event, lifecycle set to Lead/Hot. → `v2 School More Info (New School - Event Registration).request.yaml`
3. **More info (existing school)** — Customer info captured and updated, but no deal created regardless of student count.

---

## POST /api/v2/schools/registration

Register a school for a live information session. Captures customer info in CRM, then branches based on whether the school is new or existing.

> **Deprecates:** The v1 Info Session Registration flow (`POST /api/register.php` with `source_form=Info Session Registration`). Other v1 registration flows (Info Session Recording, Leading TRP Registration, Event Confirmation) remain on v1 for now.

### Request

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `contact_email` | string | Yes | Contact's email address |
| `contact_first_name` | string | Yes | Contact's first name |
| `contact_last_name` | string | Yes | Contact's last name |
| `event_id` | string | Yes | Vtiger event ID (with or without `18x` prefix — normalised automatically) |
| `contact_phone` | string | No | Contact's phone number |
| `org_phone` | string | No | Organisation phone number |
| `job_title` | string | No | Contact's job title |
| `contact_type` | string | No | Contact type (e.g. "Teacher", "Principal") |
| `contact_newsletter` | string | No | Newsletter opt-in |
| `school_account_no` | string | Conditional | Existing school's Vtiger account number |
| `school_name_other` | string | Conditional | New school name (when school is not in CRM) |
| `school_name_other_selected` | string | Conditional | Flag indicating a new school name was entered |
| `state` | string | No | Australian state for assignee routing |
| `organisation_sub_type` | string | No | Organisation sub-type |
| `num_of_students` | integer | No | Number of students at the school |
| `num_of_employees` | integer | No | Number of employees |
| `contact_lead_source` | string | No | Lead source for the contact |
| `source_form` | string | No | Defaults to `"Info Session Registration 2026"` |

### Control Flow

```mermaid
flowchart TD
    A[POST /api/v2/schools/registration] --> GED["Get event details"]
    GED --> B[Deactivate existing contacts]
    B --> C[Capture customer info in CRM]
    C --> D[Get organisation details]
    D --> E[Update org assignee + sales events]
    E --> F[Update contact assignee + forms completed]
    F --> G{isNewSchool?}

    G -->|Yes| H["getOrCreateDeal<br/>Stage: 'In Campaign'<br/>Rating: Hot<br/>Close: event date + 1 day"]
    H --> I["updateDeal<br/>Set firstInfoSessionDate<br/>Advance stage → In Campaign<br/>Set rating: Hot"]
    I --> J["registerContact<br/>with replyTo + dealId"]

    G -->|No| K["createEnquiry<br/>'Request for live Info Session'"]

    J --> Z["Response: {status: success}"]
    K --> Z

    style A fill:#4a90d9,color:#fff
    style H fill:#f5a623,color:#fff
    style I fill:#f5a623,color:#fff
    style J fill:#7ed321,color:#fff
    style K fill:#7ed321,color:#fff
```

### Assignee Routing

Same rules as [School Enquiry](#post-apiv2schoolsenquiry). Additionally, the `replyTo` field on the registration is set via `AssigneeRules::resolveRegistrationReplyTo()`:

| State | Reply To |
|-------|----------|
| NSW, QLD | BRENDAN |
| Other | LAURA |

### Response

```json
{"status": "success"}
```
or
```json
{"status": "fail", "message": "Error processing school registration: ..."}
```

### Scenarios

1. **New school (VIC)** — Deal created with stage "In Campaign" + rating "Hot", deal updated with info session date, contact registered for event with replyTo=LAURA. → `v2 School Registration (New School).request.yaml`
2. **New school (NSW)** — Same as above but replyTo=BRENDAN and assignee routing to BRENDAN. → `v2 School Registration (New School - NSW).request.yaml`
3. **Existing school** — Enquiry created with body "Request for live Info Session". No deal created, no event registration. → `v2 School Registration (Existing School).request.yaml`

