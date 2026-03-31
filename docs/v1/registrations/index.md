# Registration Endpoints

The registration endpoints handle event registrations across School, Workplace, and Early Years service types. The `register.php` endpoint routes to service-specific controllers that each implement `submit_event_registration()` with different branching logic. A separate `seminar_registration.php` endpoint handles a specific hardcoded seminar event.

## Overview

| Endpoint | Method | URL | Description |
|----------|--------|-----|-------------|
| Register | POST | `/api/register.php` | Register a contact for an event (service-type dependent) |
| Seminar Registration | POST | `/api/seminar_registration.php` | Register for a hardcoded Melbourne Teacher Seminar |

## In This Section

- [School Registrations](./school-registrations.md) — Info Session, Info Session Recording, Leading TRP, and Event Confirmation flows
- [Workplace Registrations](./workplace-registrations.md) — Role mapping, Webinar Recording logic, and deal creation conditions
- [Early Years Registrations](./early-years-registrations.md) — Early Years registration flow and Seminar Registration endpoint

## POST /api/register.php

### Request

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `service_type` | string | Yes | One of: `School`, `Workplace`, `Early Years` |
| `contact_email` | string | Yes | Contact's email address |
| `contact_first_name` | string | Yes | Contact's first name |
| `contact_last_name` | string | Yes | Contact's last name |
| `contact_phone` | string | No | Contact's phone number |
| `job_title` | string | No | Contact's job title |
| `state` | string | No | Australian state (e.g. VIC, NSW, QLD) |
| `event_id` | string | Yes | Vtiger event ID (with or without `18x` prefix) |
| `source_form` | string | Yes | Form name that determines branching logic |
| `school_account_no` | string | Conditional | Existing school account number (School) |
| `school_name_other` | string | Conditional | New school name (School) |
| `school_name_other_selected` | string | Conditional | Flag for new school name |
| `organisation_name` | string | Conditional | Workplace organisation name |
| `workplace_name_other` | string | Conditional | New workplace name |
| `workplace_name_other_selected` | string | Conditional | Flag for new workplace name |
| `workplace_account_no` | string | Conditional | Existing workplace account number |
| `earlyyears_account_no` | string | Conditional | Existing Early Years account number |
| `earlyyears_name_other` | string | Conditional | New Early Years service name |
| `service_name_other_selected` | string | Conditional | Flag for new Early Years name |
| `contact_id` | string | No | Existing contact ID for ambassador flow (Event Confirmation) |
| `contact_type` | string | No | Contact type for teacher/parent flow (Event Confirmation) |
| `attendance_type` | string | No | Attendance type (e.g. `Attending Live`) |
| `event_name_display` | string | No | Display name for Event Confirmation short event name |
| `contact_newsletter` | string | No | Newsletter opt-in flag |
| `num_of_students` | integer | No | Number of students (School) |
| `num_of_employees` | integer | No | Number of employees (Workplace) |
| `num_of_ey_children` | integer | No | Number of children (Early Years) |
| `organisation_sub_type` | string | No | Organisation sub-type (Workplace) |
| `role_workplace` | string | No | Workplace role, mapped to `job_title` |
| `role_school` | string | No | School role, mapped to `job_title` |
| `role_ey` | string | No | Early Years role, mapped to `job_title` |

### Control Flow

```mermaid
flowchart TD
    A[POST /api/register.php] --> B{service_type?}

    B -->|School| SC[SchoolVTController]
    B -->|Workplace| WP[WorkplaceVTController]
    B -->|Early Years| EY[EarlyYearsVTController]

    %% -- School: submit_event_registration() --
    SC --> SC0[Get event details from event_id]
    SC0 --> SC1{source_form?}

    SC1 -->|Info Session Registration| SC_ISR[capture_customer_info]
    SC_ISR --> SC_ISR1{is_new_school?}
    SC_ISR1 -->|Yes| SC_ISR2["update_or_create_deal('Considering', event+1day)"]
    SC_ISR2 --> SC_ISR3[Calculate first_info_session_date]
    SC_ISR3 --> SC_ISR4[update_deal_with_registration]
    SC_ISR4 --> SC_ISR5[Set reply_to from state]
    SC_ISR5 --> SC_REG[register_contact_for_event]
    SC_ISR1 -->|No| SC_ISR_ENQ["Set enquiry = 'Request for live Info Session'"]
    SC_ISR_ENQ --> SC_ENQ[create_enquiry instead of registration]

    SC1 -->|Info Session Recording| SC_REC[capture_customer_info]
    SC_REC --> SC_REC1{is_new_school?}
    SC_REC1 -->|Yes| SC_REC2["update_or_create_deal('Considering', +4 weeks)"]
    SC_REC2 --> SC_REC3["update_deal_with_registration(null, close_date)"]
    SC_REC3 --> SC_REC4[Set reply_to from state]
    SC_REC4 --> SC_REG
    SC_REC1 -->|No| SC_REC_ENQ["Set enquiry = 'Request for Info Session Recording'"]
    SC_REC_ENQ --> SC_ENQ

    SC1 -->|Leading TRP Registration| SC_LTRP[capture_customer_info]
    SC_LTRP --> SC_LTRP1["updateOrganisation with leadingTrp date"]
    SC_LTRP1 --> SC_REG

    SC1 -->|Event Confirmation| SC_EC{contact_id isset?}
    SC_EC -->|"Yes (Ambassador)"| SC_EC_AMB[get_contact_details by contact_id]
    SC_EC -->|"No (Teacher/Parent)"| SC_EC_TCH[capture_other_contact_info]
    SC_EC_TCH --> SC_EC_TCH1["Set attendance_type = 'Attending Live'"]
    SC_EC_TCH1 --> SC_EC_INV
    SC_EC_AMB --> SC_EC_INV["createOrUpdateInvitation(status='Date Confirmed')"]
    SC_EC_INV --> SC_EC_SEN[Set short_event_name]
    SC_EC_SEN --> SC_REG

    SC_REG --> RET[Return success/fail]
    SC_ENQ --> RET

    %% -- Workplace: submit_event_registration() --
    WP --> WP0[Get event details]
    WP0 --> WP_ROLE{role_workplace / role_school / role_ey?}
    WP_ROLE --> WP_JT[Map role to job_title]
    WP_JT --> WP1[capture_customer_info]
    WP1 --> WP2{source_form === 'Workplace Webinar Recording 2025'?}

    WP2 -->|Yes| WP3{num_of_employees >= 100?}
    WP3 -->|Yes| WP4["Append ' >100' to source_form"]
    WP3 -->|No| WP5["Append ' <100' to source_form"]
    WP4 --> WP6{cf_accounts_2025confirmationstatus === '' AND\norg_sub_type in target list?}
    WP5 --> WP6
    WP6 -->|Yes| WP7["create_deal('In Campaign', +10 days)"]
    WP6 -->|No| WP8[No deal creation]
    WP7 --> WP_REG[register_contact_for_event]
    WP8 --> WP_REG

    WP2 -->|No| WP_REG

    WP_REG --> RET

    %% -- Early Years: submit_event_registration() --
    EY --> EY0[Get event details]
    EY0 --> EY1[capture_customer_info]
    EY1 --> EY2["update_or_create_deal('Considering', event+1day)"]
    EY2 --> EY3[Calculate first_info_session_date]
    EY3 --> EY4[update_deal_with_registration]
    EY4 --> EY5[register_contact_for_event]
    EY5 --> RET

    style SC fill:#e1f5fe
    style WP fill:#fff3e0
    style EY fill:#e8f5e9
```

**Key details:**

- **is_new_school()** returns true when the organisation's assignee is one of `MADDIE`, `LAURA`, `VICTOR`, `HELENOR`, or `BRENDAN` (i.e. not assigned to a dedicated School Partnership Manager). When false, the school is considered an existing partner and gets an enquiry instead of a registration.
- **update_deal_with_registration()** updates the deal's close date and first info session date. If the deal's current stage is `New`, it is changed to `Considering`.
- **Workplace target organisation sub-types** for deal creation: `Professional Services`, `Healthcare`, `Government`, `Not for Profit`, `Retail/Wholesale`.
- **Event Confirmation flow** always calls `createOrUpdateInvitation` with status `Date Confirmed` and always registers the contact. The ambassador path retrieves existing contact details, while the teacher/parent path captures new contact info and forces `attendance_type = 'Attending Live'`.
- The `register_contact_for_event()` method first checks if the contact is already registered (via `checkContactRegisteredForEvent`) and skips registration if so.
