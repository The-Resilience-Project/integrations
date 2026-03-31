# Invoice Creation

## POST /Invoices/createInvoice.php

### Request

Form-urlencoded fields using numeric keys:

| Field Key | Description |
|---|---|
| `55` | PO number |
| `62` | Shipping/handling charge |
| `70` | Account ID (ACC number or raw ID) |
| `50_1` | "Yes" to copy billing address to shipping |
| `59` | Quote ID (currently overridden to null in code) |
| `10`-`21`, `63` | Student journal quantities (Foundation through Year 12) |
| `30`-`41`, `64` | Teacher resource quantities (Foundation through Year 12) |
| `42_1`-`42_6` | Billing address (street, city, state, postcode, country) |
| `43_1`-`43_6` | Shipping address (street, city, state, postcode, country) |

### Control Flow

```mermaid
flowchart TD
    A[POST request received] --> B[Build student journal line items from quantity fields 10-21, 63]
    B --> C[Build teacher resource line items from quantity fields 30-41, 64]
    C --> D{quoteId exists?}
    D -- "Yes (from quote)" --> E[Retrieve quote from VTiger]
    E --> F[Update Engage line item quantity with total_student]
    F --> G[Set account_id from quote]
    D -- "No (no quote - current path)" --> H[Resolve account_id from field 70]
    H --> I{"account_id contains 'ACC'?"}
    I -- Yes --> J[Query Accounts by account_no to get internal ID]
    I -- No --> K["Ensure '3x' prefix on ID"]
    J & K --> L[Retrieve org record from VTiger]
    L --> M["Query for matching '2024 School Partnership Program' quote"]
    M --> N[Set contact_id, billing contact, potential_id from quote]
    G & N --> O[Query Services table for shipping SER111 and Engage SER12]
    O --> P[Query Products table for teacher resources PRO44]
    P --> Q[Query Products for each student journal by product_no]
    Q --> R["Query boru_products DB for each teacher resource by product_no"]
    R --> S[Build line items in 3 sections: Display on Invoice, Student Journals, Teacher Resources]
    S --> T[Map billing and shipping addresses from request]
    T --> U{"50_1 == 'Yes'?"}
    U -- Yes --> V[Copy billing address to shipping address]
    U -- No --> W[Keep separate addresses]
    V & W --> X[Create invoice in VTiger via webservice API]
    X --> Y{Invoice creation succeeded?}
    Y -- Yes --> Z[Update account with selectedyearlevels]
    Y -- No --> AA[Log error]
```

### Sections

The invoice is built with three line-item sections:

1. **Display on Invoice** - Shipping/handling (SER111) and Engage program (SER12), plus Hard Copy Teacher Resources (PRO44)
2. **Student Journals** - Individual product line items per year level (PRO18-PRO30)
3. **Teacher Resources** - Individual product line items per year level (PRO31-PRO44), looked up via `boru_products` DB table
