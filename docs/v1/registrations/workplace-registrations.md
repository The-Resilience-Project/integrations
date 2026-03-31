# Workplace Registrations

Workplace registrations are handled by `WorkplaceVTController.submit_event_registration()`. The flow includes role mapping and conditional deal creation for webinar recording forms.

## Role Mapping

Before processing the registration, the controller checks for role fields (`role_workplace`, `role_school`, `role_ey`) and maps the value to `job_title`. This ensures a consistent job title field regardless of which role parameter the form submits.

## Standard Registration Flow

For all forms except `Workplace Webinar Recording 2025`:

1. Gets event details from `event_id`.
2. Maps role to `job_title`.
3. Calls `capture_customer_info()` to find or create the contact and organisation.
4. Registers the contact for the event via `register_contact_for_event()`.

No deal creation occurs for standard workplace registrations.

## Workplace Webinar Recording

When `source_form` is `Workplace Webinar Recording 2025`, additional logic applies after `capture_customer_info()`:

### Employee Count Branching

- If `num_of_employees >= 100` — appends ` >100` to `source_form`.
- If `num_of_employees < 100` — appends ` <100` to `source_form`.

### Deal Creation Conditions

A deal is created only when **both** of the following conditions are met:

1. The organisation's `cf_accounts_2025confirmationstatus` field is empty (i.e. no existing 2025 confirmation).
2. The organisation's sub-type is in the target list: `Professional Services`, `Healthcare`, `Government`, `Not for Profit`, `Retail/Wholesale`.

When conditions are met, `create_deal()` is called with stage `In Campaign` and a close date of +10 days. When conditions are not met, no deal is created.

After the deal creation check, the contact is registered for the event regardless of whether a deal was created.

## Key Details

- **Target organisation sub-types** for deal creation: `Professional Services`, `Healthcare`, `Government`, `Not for Profit`, `Retail/Wholesale`.
- The `register_contact_for_event()` method first checks if the contact is already registered (via `checkContactRegisteredForEvent`) and skips registration if so.

## Scenarios

The following Postman collection variants are available for testing:

6. **Workplace Registration** — Standard workplace event registration. No deal creation for non-webinar-recording forms.
7. **Workplace Webinar Recording (Large)** — Workplace Webinar Recording 2025 with `num_of_employees >= 100`. Appends ` >100` to source_form. Creates deal if confirmation status is empty and organisation sub-type matches target list.
8. **Workplace Webinar Recording (Small)** — Workplace Webinar Recording 2025 with `num_of_employees < 100`. Appends ` <100` to source_form. Same deal creation conditions apply.
