# LTRP Progress

## GET /api/school_ltrp_details.php

### Request

Query parameters:

| Parameter | Required | Description |
|---|---|---|
| `org_id` | Yes | Vtiger organisation account number |

### Control Flow

```mermaid
flowchart TD
    A[GET request received] --> B{org_id provided?}
    B -->|No| C[Return 'Missing required parameter: org_id']
    B -->|Yes| D[Create SchoolVTController]
    D --> E[getOrgWithAccountNo using org_id]
    E --> F{Organisation found?}
    F -->|No| G["Return {error: true}"]
    F -->|Yes| H[createOrUpdateSEIP with org ID and '2026 SEIP']
    H --> I{SEIP record found?}
    I -->|No| G
    I -->|Yes| J[Return ltrp, ca, name, id, participants]
```

### Response

Success:
```json
{
  "data": {
    "ltrp": "2026-02-15",
    "ca": "2026-03-01",
    "name": "Example Primary School",
    "id": "3x12345",
    "participants": "25",
    "error": false
  }
}
```

Error:
```json
{
  "data": {
    "error": true
  }
}
```

### Scenarios

**Standard lookup** -- The `org_id` is used to find the organisation, then a SEIP record (named "2026 SEIP") is created or retrieved for that organisation. The response includes the Leading TRP watched date (`ltrp`), Culture Assessment completed date (`ca`), the organisation name and ID, and the number of participants. If the organisation or SEIP record cannot be found, `{error: true}` is returned.
