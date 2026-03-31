# Form Details Endpoints

These GET endpoints return form pre-population data from the CRM. They are called by front-end forms before the user begins filling in data, so that fields can be pre-populated with existing CRM records (deal status, organisation details, SEIP progress, etc.).

## Overview

| Endpoint | Method | Controller | Purpose |
|---|---|---|---|
| `/api/school_confirmation_form_details.php` | GET | `SchoolVTController` | Retrieve deal and org info for the school confirmation form |
| `/api/school_ltrp_details.php` | GET | `SchoolVTController` | Retrieve SEIP/LTRP progress for a school organisation |
| `/api/school_curric_ordering_details.php` | GET | `SchoolVTController` | Retrieve deal, invoice, and org info for the curriculum ordering form |
| `/api/ey_confirmation_form_details.php` | GET | `EarlyYearsVTController` | Retrieve deal info for the Early Years confirmation form |

## In This Section

- [School Confirmation Form Details](./school-confirmation.md) — Retrieve deal and org info for the school confirmation form
- [LTRP Progress](./ltrp-progress.md) — Retrieve SEIP/LTRP progress for a school organisation
- [Curriculum Ordering Details](./curriculum-ordering.md) — Retrieve deal, invoice, and org info for the curriculum ordering form
- [Early Years Confirmation Form Details](./early-years-confirmation.md) — Retrieve deal info for the Early Years confirmation form
