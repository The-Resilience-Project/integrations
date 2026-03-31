# New School & Early Years Confirmations

## POST /api/confirm.php

Processes new program confirmations for School and Early Years service types. Other service types are rejected with an error response.

### Request

**Method:** POST
**Content-Type:** application/json

#### Parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `service_type` | string | Yes | Must be `School` or `Early Years`. Others return error. |
| `contact_email` | string | Yes | Primary contact email address |
| `contact_first_name` | string | Yes | Primary contact first name |
| `contact_last_name` | string | Yes | Primary contact last name |
| `contact_phone` | string | No | Primary contact phone number |
| `job_title` | string | No | Primary contact job title |
| `school_account_no` | string | Conditional | Vtiger account number for existing schools (School service type) |
| `school_name_other` | string | Conditional | Name for new schools not in Vtiger (School service type) |
| `school_name_other_selected` | boolean | Conditional | Flag indicating a new school name was entered (School) |
| `earlyyears_account_no` | string | Conditional | Vtiger account number (Early Years service type) |
| `earlyyears_name_other` | string | Conditional | Name for new EY centres not in Vtiger |
| `service_name_other_selected` | boolean | Conditional | Flag indicating a new EY centre name was entered |
| `state` | string | Yes | Australian state/territory (e.g. VIC, NSW, QLD, WA) |
| `address` | string | Yes | Street address |
| `suburb` | string | Yes | Suburb |
| `postcode` | string | Yes | Postcode |
| `participating_num_of_students` | integer | Conditional | Total participating students. If not provided, calculated from journal + planner counts. |
| `participating_journal_students` | integer | Conditional | Students receiving journals (Combined schools with Planners secondary) |
| `participating_planner_students` | integer | Conditional | Students receiving planners (Combined schools with Planners secondary) |
| `num_of_students` | integer | No | Total school enrolment (used for small school pricing in SchoolVTController) |
| `different_billing_contact` | string | Yes | `Yes` or `No` -- whether a separate billing contact is provided |
| `billing_contact_email` | string | Conditional | Billing contact email (required if `different_billing_contact=Yes`) |
| `billing_contact_first_name` | string | Conditional | Billing contact first name |
| `billing_contact_last_name` | string | Conditional | Billing contact last name |
| `billing_contact_phone` | string | Conditional | Billing contact phone |
| `mental_health_funding` | string | No | `Yes` or `No` -- using MHF affects inspire pricing (School only) |
| `school_type` | string | Conditional | `Primary`, `Secondary`, or `Combined` (School only, used by ExistingSchoolVTController for line items) |
| `engage` | string | No | `Journals` or `Planners` (default: `Journals`) |
| `secondary_engage` | string | Conditional | `Journals` or `Planners` -- for Secondary/Combined schools (ExistingSchool) |
| `inspire` | string | No | Inspire program level (e.g. `Inspire 1`) |
| `inspire_added` | string | No | `Yes` or `No` -- whether Inspire is being added (ExistingSchool only) |
| `inspire_year_levels` | string | No | `Primary and Secondary` -- triggers +$1000 surcharge (ExistingSchool) |
| `kindy_uplift` | string | No | `Yes` or `No` (Early Years only) |
| `srf` | string | No | SRF participation flag (Early Years only) |
| `funding_org` | string | No | Funding organisation name, sent as `eyFundingOrg` (Early Years only) |
| `selected_year_levels` | array | No | Array of participating year levels (e.g. `["Foundation", "Year 1", "Year 2"]`) |

### Response

```json
{ "status": "success" }
```

or on failure:

```json
{ "status": "fail" }
```

### Control Flow

#### Flowchart 1: Endpoint Routing and Main Flow

Shows the top-level routing in `confirm.php` and the `confirm_program()` method from the `Confirmation` trait.

```mermaid
flowchart TD
    A[POST /api/confirm.php] --> B{service_type?}
    B -->|School| C[new SchoolVTController]
    B -->|Early Years| D[new EarlyYearsVTController]
    B -->|Other| E[Return error: Invalid service type]
    C --> F[confirm_program]
    D --> F

    F --> G[Calculate participating_num_of_students<br/>if not provided]
    G --> H[capture_main_customer_info]
    H --> I["update_or_create_deal('Deal Won', today)"]
    I --> J[capture_billing_contact_info]
    J --> K[get_line_items]
    K --> L[Calculate total from line items]
    L --> M[update_deal_with_confirmation]
    M --> N[set_deal_line_items]
    N --> O[create_quote]
    O --> P["update_years_with_trp('2026')"]
    P --> Q[createSEIP]
    Q --> R[Return true]
```

#### Flowchart 2: Student Count Calculation and Resource Type (SchoolVTController)

The new school `get_line_items()` in `SchoolVTController` always uses journals (SER12) for the engage component. The inspire code varies by funding and school size.

```mermaid
flowchart TD
    A[get_line_items - SchoolVTController] --> B{participating_num_of_students<br/>provided?}
    B -->|Yes| C[Use provided value]
    B -->|No| D["Calculate: journal_students<br/>+ planner_students"]
    D --> C

    C --> E[Set engage_code = SER12<br/>All students get journals]
    E --> F{mental_health_funding<br/>= Yes?}
    F -->|Yes| G["inspire_code = SER157<br/>(standard Inspire)"]
    F -->|No| H{"num_of_students <= 200?<br/>(small school)"}
    H -->|No| G
    H -->|Yes| I{num_of_students > 100?}
    I -->|Yes| J["inspire_code = SER158<br/>(small school 101-200)"]
    I -->|No| K["inspire_code = SER159<br/>(small school 0-100)"]

    G --> L["Build line items array:<br/>1x inspire_code + Nx SER12"]
    J --> L
    K --> L
    L --> M[Fetch service prices<br/>from Vtiger]
    M --> N[Return line_items]
```

#### Flowchart 3: Early Years Line Items

The `EarlyYearsVTController` has a simpler line items structure with fixed service codes.

```mermaid
flowchart TD
    A[get_line_items - EarlyYearsVTController] --> B["Set engage_code = SER84<br/>(EY Engage)"]
    B --> C["Set inspire_code = SER13<br/>(EY Digital)"]
    C --> D["Build items:<br/>1x SER13 + Nx SER84"]
    D --> E[Fetch service prices<br/>from Vtiger]
    E --> F[Return line_items]
```

#### Flowchart 4: Billing Contact and Deal Update Fields

Shows how `capture_billing_contact_info()` and `update_deal_with_confirmation()` assemble the deal update payload with optional fields.

```mermaid
flowchart TD
    A[capture_billing_contact_info] --> B{different_billing_contact<br/>= Yes?}
    B -->|No| C[Return early<br/>billing_contact_id stays null]
    B -->|Yes| D["Create billing contact in Vtiger<br/>(email, first_name, last_name, phone)"]
    D --> E[Set billing_contact_id<br/>and billing_contact_email]

    F[update_deal_with_confirmation] --> G["Build base payload:<br/>dealId, contactId, address,<br/>suburb, postcode, state,<br/>total, dealStage='Deal Won'"]
    G --> H{billing_contact_id<br/>not null?}
    H -->|Yes| I[Add billingContactId]
    H -->|No| J[Skip]
    I --> K{inspire set?}
    J --> K
    K -->|Yes| L[Add inspire]
    K -->|No| M{engage set?}
    L --> M
    M -->|Yes| N[Add engage value]
    M -->|No| O["Default engage = 'Journals'"]
    N --> P{extend has items?}
    O --> P
    P -->|Yes| Q[Add extend array]
    P -->|No| R{mental_health_funding?}
    Q --> R
    R -->|Set| S[Add mentalHealthFunding]
    R -->|Not set| T{kindy_uplift?}
    S --> T
    T -->|Set| U[Add kindyUplift]
    T -->|Not set| V{srf?}
    U --> V
    V -->|Set| W[Add srf]
    V -->|Not set| X{funding_org?}
    W --> X
    X -->|Set| Y[Add eyFundingOrg]
    X -->|Not set| Z{selected_year_levels?}
    Y --> Z
    Z -->|Set| AA[Add selectedYearLevels]
    Z -->|Not set| AB[POST updateDeal to Vtiger]
    AA --> AB
```

#### Flowchart 5: SEIP Creation

Shows `createSEIP()` which creates a School Engagement & Implementation Plan record. Skipped entirely for Early Years.

```mermaid
flowchart TD
    A[createSEIP] --> B{Controller class =<br/>EarlyYearsVTController?}
    B -->|Yes| C[Return early<br/>No SEIP for Early Years]
    B -->|No| D["Build SEIP payload:<br/>seipName = '2026 SEIP'<br/>organisationId, dateConfirmed,<br/>assignee = org assignee,<br/>participants, dealId"]
    D --> E{Controller class =<br/>SchoolVTController?}
    E -->|Yes| F["Set yearsWithTrp = '1st year'"]
    E -->|No| G["ExistingSchoolVTController:<br/>Check student count & state"]
    G --> H{"participating_num_of_students<br/><= 99?"}
    H -->|Yes| I["Set assignee = 19x49 (LCD)"]
    H -->|No| J{state = WA?}
    J -->|Yes| K["Set assignee = 19x6 (BW)"]
    J -->|No| L[Keep org assignee]
    F --> M[POST createOrUpdateSEIP to Vtiger]
    I --> M
    K --> M
    L --> M
    M --> N[Link contact to SEIP<br/>via updateContactById]
```

#### Flowchart 6: Quote Stage (SchoolVTController)

New schools always get a "Delivered" quote stage.

```mermaid
flowchart TD
    A[get_quote_stage - SchoolVTController] --> B["Return 'Delivered'"]
```

### Postman Scenarios

The following Postman request variants exist in `postman/collections/Confirmations/`:

| # | Scenario | Key Fields |
|---|---|---|
| 1 | **School Confirmation** | Primary school, 350 students, Journals, no MHF, no separate billing |
| 2 | **Early Years Confirmation** | EY centre, 45 students, no funding org |
| 3 | **Existing School Confirmation** | TWB 1 online only (uses `confirm_existing_schools.php`) |
| 4 | **School Confirmation (Different Billing)** | `different_billing_contact=Yes`, separate billing contact details |
| 5 | **School Confirmation (Secondary Journals)** | Secondary school, 600 students, Journals engage |
| 6 | **School Confirmation (Secondary Planners)** | Secondary school, 450 students, Planners engage |
| 7 | **School Confirmation (Combined Split)** | Combined school, 300 journal + 250 planner students, Planners secondary engage |
| 8 | **School Confirmation (Mental Health Funding)** | `mental_health_funding=Yes`, `inspire_added=Yes` |
| 9 | **School Confirmation (Inspire Both Levels)** | Combined school, `inspire_year_levels=Primary and Secondary` (+$1000 surcharge) |
| 10 | **Early Years Confirmation (With Funding)** | `funding_org=Department of Education`, `kindy_uplift=Yes` |
| 11 | **Existing School (Multiple Programs)** | TWB 1 workshop + DWF online + BRH workshop + connected parenting |
| 12 | **Existing School (Feeling Ace)** | TWB 2 online + `feeling_ace=Yes` |
