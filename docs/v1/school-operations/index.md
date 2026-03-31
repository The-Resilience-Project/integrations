# School Operations Endpoints

These POST endpoints handle school-specific operations: accepting event dates, submitting culture assessments, and ordering curriculum resources (including full invoice creation).

## Overview

| Endpoint | Method | Controller Method | Purpose |
|---|---|---|---|
| `/api/accept_dates.php` | POST | `accept_dates()` | Record accepted event dates and link documents |
| `/api/submit_ca.php` | POST | `create_culture_assessment()` | Submit a Wellbeing Culture Assessment |
| `/api/order_resources.php` | POST | `order_resources()` | Create a curriculum resource invoice (legacy) |
| `/api/order_resources_2026.php` | POST | `order_resources_26()` | Create a curriculum resource invoice (2026 version) |

All four endpoints use `SchoolVTController`.

## In This Section

- [Date Acceptance](./date-acceptance.md) — Record accepted event dates and link documents
- [Culture Assessment](./culture-assessment.md) — Submit a Wellbeing Culture Assessment
- [Resource Ordering](./resource-ordering.md) — Create curriculum resource invoices (legacy and 2026)
