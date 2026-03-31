# Resource Ordering

## POST /api/order_resources.php

### Request

| Parameter | Required | Description |
|---|---|---|
| `school_account_no` | One of these | School account number |
| `school_name_other` | One of these | School name (if no account number) |
| `contact_email` | Yes | Email of person placing the order |

Plus all line item fields (year-level quantities, extras, etc. -- same fields as `order_resources_2026.php` below).

### Control Flow

```mermaid
flowchart TD
    A[POST request received] --> B[Create SchoolVTController]
    B --> C["order_resources() called"]
    C --> D[create_invoice -- same pipeline as order_resources_2026]
    D --> E{Success?}
    E -->|Yes| F["Return {status: 'success'}"]
    E -->|No| G["Return {status: 'fail'}"]
```

### Scenarios

**Standard order** -- Follows the same invoice creation pipeline as `order_resources_2026.php`. The SchoolVTController class includes the `order_resources_26.php` trait, so both endpoints share the same `order_resources()` / `create_invoice()` implementation.

---

## POST /api/order_resources_2026.php

### Request

| Parameter | Required | Description |
|---|---|---|
| `school_account_no` | One of these | School account number |
| `school_name_other` | One of these | School name (if no account number) |
| `order_for_2026` | No | If truthy, use 2026 naming/dates; otherwise use 2025 |
| `shipping` | Yes | Shipping cost amount |
| `contact_first_name` | Yes | First name of person placing order |
| `contact_last_name` | Yes | Last name of person placing order |
| `po_number` | No | Purchase order number |
| `foundation_qty` .. `year12_qty` | No | Student journal quantities per year level |
| `tr_foundation_qty` .. `tr_year12_qty` | No | Teacher resource quantities per year level |
| `year7_planner_1/2` .. `year12_planner_1/2` | No | Set to "Planners" to include that year level for planner hub courses |
| `gem_card_qty` | No | Gem card quantity (tiered pricing) |
| `emotion_card_qty` | No | Emotion card quantity (tiered pricing) |
| `fence_sign_qty`, `reading_log_qty`, `primary_planner_qty`, `journal_21_qty`, `journal_6_qty` | No | Extra resource quantities |
| `teacher_planner_qty` / `teacher_planner_type` | No | Teacher planner quantity and type selection |
| `senior_planner_qty` / `senior_planner_type` | No | Senior planner quantity and type selection |
| `teacher_seminar_qty` / `teacher_seminar_type` | No | Teacher seminar tickets and location |
| `shipping_address`, `shipping_address_2`, `shipping_suburb`, `shipping_postcode`, `shipping_state` | Yes | Shipping address fields |
| `billing_address`, `billing_address_2`, `billing_suburb`, `billing_postcode`, `billing_state` | No | Billing address (defaults to shipping if not set) |
| `ship_by` | No | Requested ship-by date |
| `hold_until` | No | Hold shipment until date |

### Control Flow -- Flowchart 1: Invoice Setup

```mermaid
flowchart TD
    A[POST request received] --> B[Create SchoolVTController]
    B --> C["order_resources_26() called"]
    C --> D[create_invoice]
    D --> E["get_quote(): look up quote by school_account_no or school_name_other"]
    E --> F["get_deal(): look up deal by school_account_no or school_name_other"]
    F --> G["get_org(): look up org by school_account_no or school_name_other"]
    G --> H{Quote found?}
    H -->|Yes| I[Use quote's contact, deal_id, org_id, lineItems]
    H -->|No| J[Use deal's contact, deal_id, org_id, lineItems]
    I --> K{order_for_2026?}
    J --> K
    K -->|Yes| L["invoice_date = max(now, 2026-01-31)<br/>due_date = invoice_date + 4 weeks<br/>Use '2026 School Partnership Program' naming<br/>Get hub courses from deal.cf_potentials_presentations<br/>Get SEIP ID"]
    K -->|No| M["invoice_date = max(now, 2025-01-31)<br/>due_date = invoice_date + 2 weeks<br/>Use '2025 School Partnership Program' naming"]
    L --> N[Build addresses and determine status]
    M --> N
```

### Control Flow -- Flowchart 2: Line Items

```mermaid
flowchart TD
    A[get_invoice_items called with quote line items] --> B[Student resources]
    B --> C{For each year level Foundation-Year 12}
    C -->|Qty set| D["Add product item with code PRO18-PRO30<br/>Track total_students count<br/>Add year level to selected_year_levels"]
    C -->|Not set| C
    D --> C
    C -->|Done| E[Planner detection]
    E --> F{"For each planner key (year7-year12)"}
    F -->|"Value === 'Planners'"| G[Add year level to selected_year_levels]
    F -->|Otherwise| F
    G --> F
    F -->|Done| H[Teacher resources]
    H --> I{For each teacher year level}
    I -->|Qty set| J[Add product item PRO31-PRO43, track total]
    J --> I
    I -->|Done| K{total_teacher_resources > 0?}
    K -->|Yes| L[Add teacher resource service item SER101]
    K -->|No| M[Extra resources]
    L --> M
    M --> N{For each extra resource key}
    N -->|Qty set| O[Add product item]
    O --> P{Is gem_card PRO48?}
    P -->|Yes| Q{"qty >= 500: $14.55<br/>qty >= 250: $15.45<br/>qty >= 100: $16.36"}
    P -->|No| R{Is emotion_card PRO64?}
    R -->|Yes| S{"qty >= 500: $18.17<br/>qty >= 250: $19.31<br/>qty >= 100: $20.45"}
    R -->|No| N
    Q --> N
    S --> N
    N -->|Done| T[Teacher/Senior planners and seminars]
    T --> U{teacher_planner_qty set?}
    U -->|Yes| V[Parse type, look up code PRO46-PRO62, add product]
    U -->|No| W{senior_planner_qty set?}
    V --> W
    W -->|Yes| X["Parse type, look up code PRO60/PRO63, add product"]
    W -->|No| Y{teacher_seminar_qty set?}
    X --> Y
    Y -->|Yes| Z[Parse city, look up code SER162-SER165, add service]
    Y -->|No| AA[Process quote line items]
    Z --> AA
    AA --> AB[Copy items from quote]
```

### Control Flow -- Flowchart 3: Quote Items, Addresses, and Status

```mermaid
flowchart TD
    A["Process quote line items"] --> B{is_first_invoice? Check existing invoices}
    B --> C{For each quote line item}
    C --> D{Item is Engage Journals or Engage Journals Discounted?}
    D -->|Yes| E{total_students > 0?}
    E -->|Yes| F[Add as service item with student qty]
    E -->|No| C
    D -->|No| G{Item is Engage Planners?}
    G -->|Yes| H[Skip -- do nothing]
    G -->|No| I{Is first invoice?}
    I -->|Yes| J[Add Extend/Inspire items as service items]
    I -->|No| K[Skip -- only first invoice gets Extend/Inspire]
    F --> C
    J --> C
    K --> C
    H --> C
    C -->|Done| L[Add shipping service SER111 with provided cost]
    L --> M[Build shipping address]
    M --> N{"shipping_address_2 set?"}
    N -->|Yes| O["Combine: shipping_address + ' ' + shipping_address_2"]
    N -->|No| P[Use shipping_address only]
    O --> Q[Build billing address]
    P --> Q
    Q --> R{billing_address set?}
    R -->|Yes| S[Use billing_address]
    R -->|No| T[Use shipping_address as billing]
    S --> U[Determine invoice status]
    T --> U
    U --> V{"deal.sales_stage in<br/>[Deal Won, Closed INV, Prepaid]?"}
    V -->|Yes| W["status = 'Auto Created'"]
    V -->|No| X["status = 'Unconfirmed Deal'"]
    W --> Y[createInvoice webhook with all fields and line items]
    X --> Y
    Y --> Z[Return success]
```

### Scenarios

**Basic order (2026, first invoice)** -- `order_for_2026` is set. Invoice date is max(now, 2026-01-31), due in 4 weeks. Hub courses are retrieved. Extend and Inspire quote items are included since it is the first invoice. Student journal quantities generate product line items.

**Secondary order (2026, subsequent invoice)** -- Same as above, but `is_first_invoice()` returns false because an existing invoice is found. Extend and Inspire items from the quote are skipped. Only Engage journals/discounted journals are copied from the quote.

**Previous year order** -- `order_for_2026` is not set. Uses "2025 School Partnership Program" naming. Invoice date is max(now, 2025-01-31), due in 2 weeks. No hub courses or SEIP lookup is performed.

**Order with extras (tiered pricing)** -- Gem cards and emotion cards use quantity-based pricing tiers. Teacher planners, senior planners, and teacher seminar tickets are added based on their type selections, each mapping to specific product/service codes.
