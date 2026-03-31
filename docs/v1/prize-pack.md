# Prize Pack Endpoint

## Overview

| Endpoint | Method | Integration | Purpose |
|---|---|---|---|
| `/api/prize_pack.php` | POST | VTAP Webhooks | Submit a prize pack entry and mark org as 2026 lead |

---

## POST /api/prize_pack.php

### Request

| Field | Type | Description |
|---|---|---|
| `service_type` | string | One of: School, Workplace, Early Years, Imperfects, or fallback to General |
| `contact_email` | string | Contact email address |
| `contact_first_name` | string | Contact first name |
| `contact_last_name` | string | Contact last name |
| `contact_phone` | string | Contact phone number (optional) |
| `school_account_no` | string | School account number (for School type) |
| `organisation_name` | string | Organisation name (for non-School types) |

### Control Flow

```mermaid
flowchart TD
    A[POST request received] --> B{service_type?}
    B -- School --> C[new SchoolVTController]
    B -- Workplace --> D[new WorkplaceVTController]
    B -- Early Years --> E[new EarlyYearsVTController]
    B -- Imperfects --> F[new ImperfectsVTController]
    B -- else --> G[new GeneralVTController]
    C & D & E & F & G --> H["submit_prize_pack_entry()"]
    H --> I["capture_customer_info()"]
    I --> I1["captureCustomerInfo /\ncaptureCustomerInfoWithAccountNo\n→ creates/updates contact + org"]
    I1 --> I2["getOrgDetails\n→ fetch org assignee,\nsales events, confirmation status"]
    I2 --> I3["updateOrganisation\n→ set assignee (routing)\n→ append source_form to\ncf_accounts_2025salesevents"]
    I3 --> I4["updateContactById\n→ set assignee (routing)\n→ append source_form to\ncf_contacts_formscompleted"]
    I4 --> J["mark_org_as_2026_lead()"]
    J --> K["getOrgDetails\n(cached — no extra API call)"]
    K --> L{cf_accounts_2026confirmationstatus\nnot empty?}
    L -- "Yes (already confirmed)" --> M["Return early — org\nalready has 2026 status"]
    L -- "No (empty)" --> N["updateOrganisation\n→ set organisation2026Status\n= 'Lead'"]
    N --> O{Exception thrown?}
    M --> O
    O -- No --> P["Return {status: success}"]
    O -- Yes --> Q["Return {status: fail}"]
```

> **Note:** `capture_customer_info()` (in `ContactAndOrg` trait) is not a single VTAP call — it internally calls `captureCustomerInfo` (or `captureCustomerInfoWithAccountNo`), then `getOrgDetails`, `updateOrganisation`, and `updateContactById`. These update the org's assignee and sales event tracking, and the contact's assignee and forms completed tracking.

### CRM Records Modified

| Record | VTAP Endpoint | Fields Modified | Why |
|--------|--------------|----------------|-----|
| **Contact** | `captureCustomerInfo` → `updateContactById` | `assigned_user_id` (assignee routing), `cf_contacts_formscompleted` (source form appended) | Routes contact to correct partnership manager; tracks which forms this contact came through |
| **Organisation** | `captureCustomerInfo` → `updateOrganisation` | `assigned_user_id` (assignee routing), `cf_accounts_2025salesevents` (source form appended) | Routes org to correct partnership manager; tracks which conference/form touched this org |
| **Organisation** | `updateOrganisation` (mark as lead) | `cf_accounts_2026confirmationstatus` → `"Lead"` (only if currently empty) | Flags org for sales follow-up without creating a deal |

### Scenarios

| Scenario | service_type | Behaviour |
|---|---|---|
| School submission | School | SchoolVTController captures customer info (creates/updates contact + org, sets assignees, tracks source form), then marks org as 2026 Lead if not already confirmed |
| Workplace submission | Workplace | WorkplaceVTController same flow, different controller and org lookup |
| Already confirmed org | any | `mark_org_as_2026_lead()` returns early without updating since confirmation status is already set |
