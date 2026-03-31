# Existing School Extensions

## POST /api/confirm_existing_schools.php

Processes confirmation for returning/existing schools adding Extend programs (teacher wellbeing workshops, parent webinars) and optionally re-adding Inspire. Hardcoded to use `ExistingSchoolVTController` (no service_type routing).

### Request

**Method:** POST
**Content-Type:** application/json

#### Core Parameters

Same as `confirm.php` for contact and address fields. The key differences are the Extend-specific fields below.

#### Extend Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `school_type` | string | Yes | `Primary`, `Secondary`, or `Combined` -- determines journal/planner split |
| `secondary_engage` | string | Conditional | `Journals` or `Planners` -- required for Secondary and Combined schools |
| `inspire_added` | string | No | `Yes` or `No` -- whether to add an Inspire session |
| `inspire_year_levels` | string | No | `Primary and Secondary` -- triggers +$1000 P-12 surcharge |
| `num_of_students_1` / `num_of_students_2` | integer | No | Total enrolment for small school inspire pricing |
| `teacher_wellbeing_program` | string | No | Comma-separated list: `TWB 1`, `TWB 2`, `TWB 3`, `DWF`, `BRH` |
| `twb_1_online_only` | string | No | `Yes` if TWB 1 is online-only |
| `twb_1_workshop_paid` | string | No | Selected paid workshop, e.g. `Wellbeing Workshop 1 (Self) ($XYZ)` |
| `twb_1_workshop_free` | string | No | Selected free workshop (for schools with free travel) |
| `twb_2_online_only` | string | No | `Yes` if TWB 2 is online-only |
| `twb_2_workshop_paid` | string | No | TWB 2 paid workshop selection |
| `twb_2_workshop_free` | string | No | TWB 2 free workshop selection |
| `twb_3_online_only` | string | No | `Yes` if TWB 3 is online-only |
| `twb_3_workshop_paid` | string | No | TWB 3 paid workshop selection |
| `twb_3_workshop_free` | string | No | TWB 3 free workshop selection |
| `dwf_online_only` | string | No | `Yes` if Digital Wellbeing Families is online-only |
| `dwf_workshop_paid` | string | No | DWF paid workshop selection |
| `dwf_workshop_free` | string | No | DWF free workshop selection |
| `brh_online_only` | string | No | `Yes` if Building Resilience at Home is online-only |
| `brh_workshop_paid` | string | No | BRH paid workshop selection |
| `brh_workshop_free` | string | No | BRH free workshop selection |
| `feeling_ace` | string | No | `Yes` to add Feeling ACE parent webinar |
| `connected_parenting` | string | No | `Yes` to add Connected Parenting webinar |

#### Extend Service Code Map

The extend option values contain a service name followed by a price in parentheses (e.g. `Wellbeing Workshop 1 (Self) ($500)`). The code strips the price portion and maps the name to a Vtiger service code:

| Extend Option | Service Code |
|---|---|
| Teacher Wellbeing Program (online) | SER23 |
| Wellbeing Webinar 1 (Self) | SER26 |
| Wellbeing Workshop 1 (Self) | SER24 |
| Wellbeing Webinar 2 (Others) | SER27 |
| Wellbeing Workshop 2 (Others) | SER25 |
| Wellbeing Webinar 3 (Success) | SER117 |
| Wellbeing Workshop 3 (Success) | SER118 |
| Family Digital Wellbeing Webinar | SER120 |
| Family Digital Wellbeing Workshop | SER119 |
| Building Resilience at Home Webinar | SER30 |
| Building Resilience at Home Workshop | SER104 |
| Hugh Parent Webinar (Feeling ACE) | SER160 |
| Martin Parent Webinar (Feeling ACE) | SER161 |
| Connected Parenting Webinar | SER32 |

### Control Flow

#### Flowchart 7: Existing School Student Count and Engage Split

Shows how `ExistingSchoolVTController::get_line_items()` determines journal and planner quantities based on school type and secondary engage preference.

```mermaid
flowchart TD
    A[get_line_items - ExistingSchoolVTController] --> B{school_type?}
    B -->|Primary| C["journal_qty = all students<br/>planner_qty = 0"]
    B -->|Secondary| D{secondary_engage?}
    B -->|Combined| G{secondary_engage?}

    D -->|Journals| E["journal_qty = all students<br/>planner_qty = 0"]
    D -->|Planners| F["journal_qty = 0<br/>planner_qty = all students"]

    G -->|Journals| H["journal_qty = all students<br/>planner_qty = 0"]
    G -->|Planners| I["journal_qty = participating_journal_students<br/>planner_qty = participating_planner_students"]

    C --> J{journal_qty > 0?}
    E --> J
    F --> J
    H --> J
    I --> J

    J -->|Yes| K["Add SER12 (Journals)<br/>qty = journal_qty"]
    J -->|No| L{planner_qty > 0?}
    K --> L
    L -->|Yes| M["Add SER65 (Planners)<br/>qty = planner_qty"]
    L -->|No| N[Set engage array from<br/>which items were added]
    M --> N
```

#### Flowchart 8: Existing School Inspire Pricing

Shows the Inspire pricing logic for existing schools. Previous inspire level is checked from the organisation's 2025 data.

```mermaid
flowchart TD
    A["After engage items built..."] --> B{inspire_added = Yes?}
    B -->|No| C["Set inspire = '' (empty)<br/>No inspire line item"]
    B -->|Yes| D["Set inspire = 'Inspire 2'<br/>Check previous year level"]
    D --> E{"org cf_accounts_2025inspire<br/>= 'Inspire 3'?"}
    E -->|Yes| F["inspire = 'Inspire 3'"]
    E -->|No| G{"org cf_accounts_2025inspire<br/>= 'Inspire 4'?"}
    G -->|Yes| H["inspire = 'Inspire 4'"]
    G -->|No| I["Keep inspire = 'Inspire 2'"]

    F --> J[Determine inspire_code]
    H --> J
    I --> J

    J --> K{mental_health_funding = Yes?}
    K -->|Yes| L["inspire_code = SER146<br/>(MHF Inspire)"]
    K -->|No| M{"num_of_students provided<br/>AND <= 200?"}
    M -->|No| N["inspire_code = SER147<br/>(standard Inspire)"]
    M -->|Yes| O{num_of_students > 100?}
    O -->|Yes| P["inspire_code = SER148<br/>(small school 101-200)"]
    O -->|No| Q["inspire_code = SER149<br/>(small school 0-100)"]

    L --> R{inspire_year_levels =<br/>'Primary and Secondary'<br/>AND NOT mhf?}
    N --> R
    P --> R
    Q --> R

    R -->|Yes| S["additional = $1000<br/>billing_note = 'Additional $1000 for P-12 Inspire'"]
    R -->|No| T["additional = $0"]
    S --> U["Add inspire line item:<br/>1x inspire_code + additional"]
    T --> U
```

#### Flowchart 9: Existing School Extend Programs

Shows how extend options (teacher wellbeing programs, parent webinars) are parsed and converted to line items.

```mermaid
flowchart TD
    A["After inspire items..."] --> B["Iterate extend_payload_options:<br/>teacher_wellbeing_program,<br/>twb_1_online_only, twb_1_workshop_paid,<br/>twb_1_workshop_free, twb_2_*,<br/>twb_3_*, dwf_*, brh_*,<br/>feeling_ace, connected_parenting"]
    B --> C{Current option<br/>has data?}
    C -->|No| D[Skip to next option]
    C -->|Yes| E["Split value by ', '<br/>(may contain multiple selections)"]
    E --> F[For each selection]
    F --> G["Replace 'One'->'1',<br/>'Two'->'2', 'Three'->'3'"]
    G --> H["Strip price suffix:<br/>remove from '$' onwards"]
    H --> I["Look up service code<br/>from extend_code_map"]
    I --> J["Add line item:<br/>qty=1, code from map"]
    J --> K[Add to extend_options array]
    K --> D
    D --> L[Set this->extend = extend_options]
    L --> M[Fetch all service prices<br/>from Vtiger]
    M --> N[Build final line_items<br/>with prices and tax]
```

#### Flowchart 10: Existing School Quote Stage

The `ExistingSchoolVTController` overrides `get_quote_stage()` with logic based on free travel and workshop count.

```mermaid
flowchart TD
    A[get_quote_stage - ExistingSchoolVTController] --> B{"org cf_accounts_freetravel<br/>= '1'?"}
    B -->|Yes| C["Return 'Delivered'"]
    B -->|No| D[Count workshop items<br/>in extend array]
    D --> E{workshop count > 0?}
    E -->|Yes| F["Return 'New'<br/>(needs scheduling)"]
    E -->|No| G["Return 'Delivered'<br/>(webinars only)"]
```

### Postman Scenarios

| # | Scenario | Key Fields |
|---|---|---|
| 1 | **Existing School Confirmation** | TWB 1 online only, no workshops |
| 2 | **Existing School (Multiple Programs)** | TWB 1 paid workshop + DWF online + BRH paid workshop + connected parenting |
| 3 | **Existing School (Feeling Ace)** | TWB 2 online + feeling_ace add-on |
