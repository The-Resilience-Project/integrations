# Enquiry API Flow

## Overview

The `api/enquiry.php` endpoint accepts POST requests to create enquiry records in vTiger CRM. It routes to a service-type-specific controller, each with its own enquiry submission logic.

## Endpoint Routing

```mermaid
flowchart TD
    A[POST /api/enquiry.php] --> B{service_type?}
    B -->|School| C[SchoolVTController]
    B -->|Workplace| D[WorkplaceVTController]
    B -->|Early Years| E[EarlyYearsVTController]
    B -->|Imperfects| F[ImperfectsVTController]
    B -->|other| G[GeneralVTController]

    C --> H[submit_enquiry]
    D --> H
    E --> H
    F --> H
    G --> H

    H --> I{success?}
    I -->|yes| J["JSON: {status: success}"]
    I -->|no| K["JSON: {status: fail}"]
```

## School Enquiry Flow

```mermaid
flowchart TD
    Start[submit_enquiry] --> CloseDate["Set deal_close_date = now + 2 weeks"]

    CloseDate --> Capture[capture_customer_info]

    subgraph Capture Customer Info
        Capture --> Deactivate["Deactivate existing contacts\nwith same email"]
        Deactivate --> BuildPayload["Build payload:\nemail, name, phone,\norganisation type, state"]
        BuildPayload --> SchoolLookup{school_name_other_selected?}
        SchoolLookup -->|Yes - new/unlisted school| ByName["POST captureCustomerInfo\nwith organisation name"]
        SchoolLookup -->|No - known school| ByAcct["POST captureCustomerInfoWithAccountNo\nwith school_account_no"]
        ByName --> StoreIds["Store contact_id\nand organisation_id"]
        ByAcct --> StoreIds
    end

    StoreIds --> FetchOrg["Fetch organisation details\n(assignee, sales events, years with TRP)"]
    FetchOrg --> UpdateOrg[Update organisation assignee\nand sales events]
    UpdateOrg --> UpdateForms["Update 'Forms Completed'\non contact record"]

    UpdateForms --> NewSchool{is_new_school?}

    NewSchool -->|"Yes\n(assignee is MADDIE, LAURA,\nVICTOR, HELENOR, or BRENDAN)"| CreateDeal["Create/update deal\nname: '2026 School Partnership Program'\nstage: 'New'\nclose: now + 2 weeks"]
    NewSchool -->|"No\n(has dedicated SPM)"| SkipDeal[Skip deal creation]

    CreateDeal --> CreateEnquiry[create_enquiry]
    SkipDeal --> CreateEnquiry

    subgraph Create Enquiry Ticket
        CreateEnquiry --> Subject["Subject: 'FirstName LastName | OrgName'"]
        Subject --> Body["Body: enquiry text or 'Conference Enquiry'"]
        Body --> Assignee[Determine assignee]
        Assignee --> PostEnquiry["POST createEnquiry to vTiger"]
    end

    PostEnquiry --> Return["Return true / false"]
```

## School Enquiry Assignee Logic

```mermaid
flowchart TD
    A[get_enquiry_assignee] --> B{Org has assignee?}
    B -->|No / null| C[LAURA]
    B -->|Yes| D{Assignee is MADDIE?}
    D -->|No| E[Keep existing assignee\n- the current SPM]
    D -->|Yes| F{State?}
    F -->|NSW or QLD| G[BRENDAN]
    F -->|Other| C
```

## Contact and Organisation Assignee Logic

The same state-based routing applies to `get_contact_assignee` and `get_org_assignee`:

```mermaid
flowchart TD
    A[get_contact_assignee / get_org_assignee] --> B{Org assignee is MADDIE?}
    B -->|No| C[Keep existing assignee]
    B -->|Yes| D{State?}
    D -->|NSW or QLD| E[BRENDAN]
    D -->|Other| F[LAURA]
```

## Other Service Type Enquiry Flows

```mermaid
flowchart TD
    subgraph Workplace
        W1[capture_customer_info] --> W2["Create deal\n(always, stage: 'New')"]
        W2 --> W3[create_enquiry\nassignee: LAURA]
    end

    subgraph Early Years
        EY1[capture_customer_info] --> EY2["Create deal\n(always, stage: 'New')"]
        EY2 --> EY3[create_enquiry\nassignee: BRENDAN]
    end

    subgraph General
        G1["get_contact_by_email\n(lookup only, no org capture)"] --> G2[create_enquiry\nassignee: ASHLEE]
    end

    subgraph Imperfects
        I1["get_contact_by_email\n(same as General)"] --> I2[create_enquiry\ntype: 'Imperfects'\nassignee: ASHLEE]
    end
```
