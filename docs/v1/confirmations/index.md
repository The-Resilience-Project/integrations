# Confirmation Endpoints

Program confirmation endpoints handle the transition from enquiry/registration to a confirmed (won) deal in Vtiger CRM. Two distinct endpoints serve different scenarios: `confirm.php` handles new School and Early Years confirmations, while `confirm_existing_schools.php` handles extend/add-on confirmations for existing (returning) schools.

Both endpoints share the `Confirmation` trait (`src/api/classes/traits/confirmation.php`) which orchestrates the core flow: capture customer info, update the deal to "Deal Won", build line items, create a quote, update the organisation's years-with-TRP, and create a SEIP record.

---

## Overview

| Endpoint | Controller | Service Types | Purpose |
|---|---|---|---|
| `POST /api/confirm.php` | `SchoolVTController` or `EarlyYearsVTController` | School, Early Years | New program confirmation |
| `POST /api/confirm_existing_schools.php` | `ExistingSchoolVTController` | School (existing) | Extend/add-on confirmation for returning schools |

---

## In This Section

- [New School & Early Years Confirmations](./new-school-confirmations.md) -- Covers `POST /api/confirm.php` for new School and Early Years program confirmations, including request parameters, control flow with flowcharts, and Postman scenarios.
- [Existing School Extensions](./existing-school-extensions.md) -- Covers `POST /api/confirm_existing_schools.php` for returning schools adding Extend programs and optionally re-adding Inspire, including extend parameters, service code mapping, control flow, and Postman scenarios.

---

## Key Source Files

| File | Role |
|---|---|
| `src/api/confirm.php` | Endpoint routing by service_type |
| `src/api/confirm_existing_schools.php` | Hardcoded to ExistingSchoolVTController |
| `src/api/classes/traits/confirmation.php` | `confirm_program()`, `update_deal_with_confirmation()`, `createSEIP()`, `create_quote()`, `set_deal_line_items()`, `update_years_with_trp()` |
| `src/api/classes/school.php` | `SchoolVTController::get_line_items()`, `ExistingSchoolVTController::get_line_items()`, `ExistingSchoolVTController::get_quote_stage()` |
| `src/api/classes/early_years.php` | `EarlyYearsVTController::get_line_items()`, `get_quote_stage()` |
| `src/api/classes/traits/contact_and_org.php` | `capture_main_customer_info()`, `capture_billing_contact_info()`, `capture_customer_info()` |
| `src/api/classes/traits/deal.php` | `update_or_create_deal()` |
| `src/api/classes/base.php` | `VTController` base class, VTAP webhook client, staff constants |
