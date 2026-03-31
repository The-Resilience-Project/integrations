# Early Years Confirmation Form Details

## GET /api/ey_confirmation_form_details.php

### Request

Query parameters:

| Parameter | Required | Description |
|---|---|---|
| `school_account_no` | One of these | Vtiger account number for the organisation |
| `school_name` | One of these | Organisation name (used if account number not provided) |

### Control Flow

```mermaid
flowchart TD
    A[GET request received] --> B[Create EarlyYearsVTController]
    B --> C{school_account_no provided?}
    C -->|Yes| D[getOrgWithAccountNo]
    C -->|No| E[getOrgWithName using school_name]
    D --> F[getDealDetailsFromAccountNo]
    E --> G[getDealDetails]
    F --> H[Extract deal_status and deal_id]
    G --> H
    H --> I[Return deal_status and id]
```

### Response

```json
{
  "data": {
    "deal_status": "Considering",
    "id": "4x56789"
  }
}
```

### Scenarios

**Standard lookup** -- Simpler than the school version. The endpoint only returns the deal's `sales_stage` and `id` for the "2026 Early Years Partnership Program" deal. No organisation-level fields are returned. If no deal is found, both fields return as empty strings.
