# Xero Code Synchronisation

## POST /Invoices/58850_updateXeroCodeInvoiceItem.php

### Request

| Field | Type | Description |
|---|---|---|
| `id` | string | VTiger invoice ID |

### Control Flow

```mermaid
flowchart TD
    A[POST request received] --> B{invoiceId provided?}
    B -- No --> C[Exit]
    B -- Yes --> D[Retrieve invoice from VTiger]
    D --> E{invoice found?}
    E -- No --> F[Exit]
    E -- Yes --> G{line items exist?}
    G -- No --> H[Exit]
    G -- Yes --> I[For each line item]
    I --> J{"xero code or xero_account empty AND productid exists?"}
    J -- No --> K[Skip to next item]
    J -- Yes --> L[Retrieve product/service master data from VTiger]
    L --> M{Is it a Service? Check for service_no field}
    M -- Yes --> N{cf_services_xerocode differs from line item?}
    N -- Yes --> O[Update line item xero code from service, set update_required]
    M -- No --> P{Is it a Product? Check for product_no field}
    P -- Yes --> Q{cf_products_xerocode differs from line item?}
    Q -- Yes --> R[Update line item xero code from product, set update_required]
    N -- No & Q -- No --> S[Check xero_account field]
    O & R --> S
    S --> T{master xero_account differs from line item?}
    T -- Yes --> U[Update line item xero_account, set update_required]
    T -- No --> K
    U --> K
    K --> V{update_required?}
    V -- Yes --> W[Update invoice in VTiger via webservice API]
    W --> X[Add comment noting Xero Code and Xero Sales Account updated]
    V -- No --> Y[Exit with no changes]
```
