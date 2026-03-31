# Testing

This project uses a layered testing approach.

## Layers

| Layer | Tool | Who | When |
|-------|------|-----|------|
| Unit / Integration | PHPUnit + `StubVtigerWebhookClient` | CI (automated) | Every PR |
| API Contract | Postman collections (`postman/`) | Dev (manual) or Newman | Before deploy |
| Business Acceptance | Excel test plan (see below) | Business testers | Before go-live / after major changes |

## Business Acceptance Test Plans

- [2027 New Schools Test Plan](2027%20New%20Schools%20Test%20Plan.xlsx) — covers Bulk Upload, Enquiry, More Info, Conferences, Teacher Seminar, Live Info, and cross-cutting prospect/lead flows.

### How to use

1. Open the Excel file and go to the relevant test case sheet (TC-01 through XC-02)
2. Execute each test and enter **Pass**, **Fail**, or **Blocked** in column G
3. The **Overview** dashboard updates automatically

### Test case sheets

| Sheet | Scenario |
|-------|----------|
| TC-01 | Bulk Upload — Proactive Prospects |
| TC-02 | Enquiry Process — "Chat to us" (CJ1) |
| TC-03 | More Information Form — >=500 & <500 (CJ1/CJ3a) |
| TC-04 | Conferences — Delegates, Prize Pack (CJ2b) |
| TC-05 | Conferences — Enquiry (CJ3c) |
| TC-06 | Teacher Seminar — Attendance (CJ3b) |
| TC-07 | Live Info Session — Registration (CJ3a/3b) |
| XC-01 | Cross-cutting: Enquiry Prospect Flow (>=500) |
| XC-02 | Cross-cutting: Lead Flow (<500) |

## Postman Collections

- `postman/collections/v1/` — v1 API endpoint requests
- `postman/collections/v2/Schools/` — v2 Schools endpoint requests

## Running Automated Tests

```bash
make test      # All PHPUnit tests
make check     # Lint + static analysis + tests
```
