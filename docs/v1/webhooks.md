# Webhook Endpoints

Uses the Vtiger REST API (`$vtod`) directly.

## Overview

| Endpoint | Method | Integration | Purpose |
|---|---|---|---|
| `/Webhooks/Order.php` | POST | Vtiger REST | Process WooCommerce order webhook for Early Years curriculum orders |

---

## POST /Webhooks/Order.php

### Request

JSON body -- WooCommerce webhook payload:

| Field | Type | Description |
|---|---|---|
| `id` | int | WooCommerce order ID |
| `status` | string | Order status (only `processing` is handled) |
| `date_created` | string | ISO datetime (e.g. `2026-01-15T10:30:00`) |
| `billing.company` | string | Company name from billing (primary school name source) |
| `meta_data[]` | array | Order meta data; key `school_name` used as fallback for school name |
| `line_items[]` | array | Order line items with `name`, `product_id`, `quantity`, `meta_data` |

### Control Flow

```mermaid
flowchart TD
    A[POST webhook received] --> B[Parse JSON body from php://input]
    B --> C[Extract schoolName from billing.company]
    C --> D{schoolName empty?}
    D -- Yes --> E["Search meta_data for key='school_name'"]
    E --> F[Use meta_data value as schoolName]
    D -- No --> G[Keep billing.company as schoolName]

    F & G --> H["Scan line_items for 'Early Years Children's Portfolio'"]
    H --> I[Capture qty_early_year from matching item]

    I --> J{schoolName not empty AND status == processing?}
    J -- No, empty schoolName --> K[Log: No school name in order]
    J -- No, wrong status --> L[Log: Order status is not processing]
    J -- Yes --> M[Query Accounts by accountname in VTiger]

    M --> N{Account found?}
    N -- Yes --> O[Use existing account ID]
    N -- No --> P["Query for fallback account 'School Name Other'"]
    P --> Q{Other account found?}
    Q -- Yes --> R[Use Other account ID]
    Q -- No --> S["Create new 'School Name Other' account: type=School"]
    S --> T{Account created?}
    T -- Yes --> U[Use new account ID]
    T -- No --> V[Log error, no account to update]

    O & R & U --> W{Account has cf_accounts_curriculumordered already set?}
    W -- Yes --> X[Log: Account already has curric ordered date, skip update]
    W -- No --> Y["Update account: curriculum ordered date, selectedyearlevels='Early Years', totalresourcesordered=qty"]

    Y --> Z["Query Potentials for '2026 Early Years Partnership Program' deal linked to account"]
    Z --> AA{Deal found?}
    AA -- No --> AB[Log error: deal query failed]
    AA -- Yes --> AC[Extract deal line items]
    AC --> AD["Find product 25x95211 in deal line items"]
    AD --> AE[Update that line item quantity with qty_early_year]

    AE --> AF["Search order line_items for 'Engage: Early Years Teaching and Learning Program'"]
    AF --> AG[Extract number_of_groups from item meta_data]
    AG --> AH[Calculate new grand total from updated line items]
    AH --> AI["Update deal: wcreference, numberOfGroups, numberOfParticipants, LineItems, hdnGrandTotal"]
```

### Account Resolution Logic

```mermaid
flowchart TD
    A[schoolName from order] --> B{Exact match in VTiger Accounts?}
    B -- Yes --> C[Use matched account]
    B -- No --> D{"'School Name Other' account exists?"}
    D -- Yes --> E[Use Other account]
    D -- No --> F["Create 'School Name Other' account"]
    F --> G{Created successfully?}
    G -- Yes --> H[Use new account]
    G -- No --> I[No account available - skip all updates]
```

### Deal Update Details

When the account is found and has no existing curriculum ordered date, the webhook updates the related deal:

| Deal Field | Source |
|---|---|
| `cf_potentials_wcreference` | WooCommerce order `id` |
| `cf_potentials_numberofgroups` | Count of groups from `Engage: Early Years Teaching and Learning Program` line item meta_data |
| `cf_potentials_numberofparticipants` | `qty_early_year` from the `Early Years Children's Portfolio` line item |
| `LineItems` | Existing deal line items with updated quantity for product `25x95211` |
| `hdnGrandTotal` | Recalculated from updated line items (listprice * quantity) |

### Processing Guard

The webhook exits early without processing in two cases:
1. `status` is not `processing` (e.g. `completed`, `pending`, `on-hold`)
2. `schoolName` could not be resolved from either `billing.company` or the `school_name` meta_data key
