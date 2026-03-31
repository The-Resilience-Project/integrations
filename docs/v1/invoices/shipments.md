# Shipments

## POST /Invoices/createShipment.php

### Request

| Field | Type | Description |
|---|---|---|
| `id` | string | VTiger invoice ID |

### Control Flow

```mermaid
flowchart TD
    A[POST request received] --> B{invoiceId provided?}
    B -- No --> C["Return {success: false}"]
    B -- Yes --> D[Retrieve invoice from VTiger]
    D --> E{invoice found?}
    E -- No --> F["Return {success: false}"]
    E -- Yes --> G[Iterate line items]
    G --> H{item is Shipping costs?}
    H -- Yes --> I[Capture shipping amount]
    H -- No --> J{"section is Student Journals or Teacher Resources?"}
    J -- No --> K[Skip item]
    J -- Yes --> L[Retrieve product from VTiger for weight and SKU]
    L --> M[Accumulate total weight: product weight * quantity]
    M --> N[Add to ShipStation items array]
    I & K & N --> O[Retrieve contact from VTiger for phone, email, name]
    O --> P[Retrieve account from VTiger for account name]
    P --> Q[Build ShipStation order payload]
    Q --> R["Set carrier: star_track, service: express, storeId: 791256"]
    R --> S[POST to ShipStation /orders/createorder]
    S --> T{response has orderId?}
    T -- Yes --> U[Insert into boru_shipment_invoice DB table]
    U --> V["Return {success: true, orderData}"]
    T -- No --> W["Return {success: false}"]
```

---

## POST /Invoices/create_shipment_2025.php

### Request

| Field | Type | Description |
|---|---|---|
| `recordid` | string | VTiger invoice ID (preferred) |
| `id` | string | VTiger invoice ID (fallback) |

### Control Flow

```mermaid
flowchart TD
    A[POST request received] --> B[Resolve invoice_id from recordid or id param]
    B --> C["Instantiate ShipStationOrder(invoice_id)"]
    C --> D["Call create()"]
    D --> E{vtod initialised?}
    E -- No --> F["Return 500: Failed to init vtod"]
    E -- Yes --> G{invoice_id valid?}
    G -- No --> H["Return 500: No Invoice ID"]
    G -- Yes --> I[Retrieve invoice from VTiger]
    I --> J{invoice found in VTiger?}
    J -- No --> K["Return 500: Failed to find invoice"]
    J -- Yes --> L{Determine store by invoice subject}
    L -- "contains 'Workplace'" --> M["Use bulk_order_store_id 380683, prefix orderNumber with '2'"]
    L -- "contains '2026'" --> N["Use school_26_store_id 823800"]
    L -- else --> O["Use school_25_store_id 809689"]
    M & N & O --> P{shippable line items count > 0?}
    P -- No --> Q["Return: No need to create order"]
    P -- Yes --> R[format_items: query Products, calculate weight, build items array]
    R --> S[set_account_data: retrieve account name]
    S --> T[format_ship_to: retrieve contact phone/email/name]
    T --> U[format_bill_to: set billing address from invoice]
    U --> V{order already in ShipStation?}
    V -- Yes --> W["Return: Order already in Ship Station with calculated weight"]
    V -- No --> X["Build SS payload: carrier star_track, service express, dimensions 35x23x22cm"]
    X --> Y{cf_invoice_holduntil set?}
    Y -- Yes --> Z["Set orderStatus = on_hold"]
    Y -- No --> AA["Set orderStatus = awaiting_shipment"]
    Z & AA --> AB[POST to ShipStation /orders/createorder]
    AB --> AC{response has orderId?}
    AC -- No --> AD["Return 500: Failed to create order"]
    AC -- Yes --> AE{cf_invoice_holduntil set?}
    AE -- Yes --> AF[POST holduntil to ShipStation]
    AF --> AG{hold succeeded?}
    AG -- No --> AH["Return 500: Failed to hold order"]
    AG -- Yes --> AI["Return success with orderId"]
    AE -- No --> AI
```

### Store Routing

| Invoice Subject Contains | Store ID | Store Name |
|---|---|---|
| "Workplace" | 380683 | Bulk order store |
| "2026" | 823800 | School 2026 store |
| Default | 809689 | School 2025 store |

### Item Filtering

Only line items in these sections are included in the ShipStation order:
- Student Journals
- Teacher Resources
- Extra Resources

Items with product IDs in the `teacher_sem` list (25x662672, 25x662669, 25x662668, 25x662673) are excluded.
