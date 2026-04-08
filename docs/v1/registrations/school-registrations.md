# School Registrations

School registrations are handled by `SchoolVTController.submit_event_registration()` and branch based on the `source_form` field into four distinct flows: Info Session Registration, Info Session Recording, Leading TRP Registration, and Event Confirmation.

## Info Session Registration

> **Deprecated:** The v1 Info Session Registration flow is deprecated. Use `POST /api/v2/schools/registration` instead. See [v2 Schools Endpoints](../../v2/schools.md#post-apiv2schoolsregistration).

When `source_form` is `Info Session Registration`:

1. Calls `capture_customer_info()` to find or create the contact and organisation.
2. Checks `is_new_school()` to determine the branching path:
   - **New school** — calls `update_or_create_deal()` with stage `Considering` and a close date of event date + 1 day. Calculates `first_info_session_date` from the event, then calls `update_deal_with_registration()` to update the deal. Sets `reply_to` based on the contact's state. Finally registers the contact for the event.
   - **Existing school** — sets the enquiry type to `Request for live Info Session` and calls `create_enquiry()` instead of registering for the event.

## Info Session Recording

When `source_form` is `Info Session Recording`:

1. Calls `capture_customer_info()`.
2. Checks `is_new_school()`:
   - **New school** — calls `update_or_create_deal()` with stage `Considering` and a close date of +4 weeks from now. Calls `update_deal_with_registration(null, close_date)`. Sets `reply_to` from the contact's state. Registers the contact for the event.
   - **Existing school** — sets the enquiry type to `Request for Info Session Recording` and calls `create_enquiry()` instead.

## Leading TRP Registration

When `source_form` is `Leading TRP Registration`:

1. Calls `capture_customer_info()`.
2. Calls `updateOrganisation()` with the `leadingTrp` date field set.
3. Registers the contact for the event.

No deal creation or update occurs in this flow.

## Event Confirmation

The Event Confirmation flow has two paths based on whether `contact_id` is provided:

### Ambassador Path (contact_id provided)

1. Retrieves existing contact details using `get_contact_details()` with the provided `contact_id`.
2. Calls `createOrUpdateInvitation()` with status `Date Confirmed`.
3. Sets the `short_event_name` from `event_name_display`.
4. Registers the contact for the event.

### Teacher/Parent Path (no contact_id)

1. Calls `capture_other_contact_info()` to capture the new contact's details.
2. Forces `attendance_type` to `Attending Live`.
3. Calls `createOrUpdateInvitation()` with status `Date Confirmed`.
4. Sets the `short_event_name` from `event_name_display`.
5. Registers the contact for the event.

## Key Details

- **is_new_school()** returns true when the organisation's assignee is one of `MADDIE`, `LAURA`, `VICTOR`, `HELENOR`, or `BRENDAN` (i.e. not assigned to a dedicated School Partnership Manager). When false, the school is considered an existing partner and gets an enquiry instead of a registration.
- **update_deal_with_registration()** updates the deal's close date and first info session date. If the deal's current stage is `New`, it is changed to `Considering`.
- **Event Confirmation flow** always calls `createOrUpdateInvitation` with status `Date Confirmed` and always registers the contact. The ambassador path retrieves existing contact details, while the teacher/parent path captures new contact info and forces `attendance_type = 'Attending Live'`.
- The `register_contact_for_event()` method first checks if the contact is already registered (via `checkContactRegisteredForEvent`) and skips registration if so.

## Scenarios

The following Postman collection variants are available for testing:

1. **School Registration** — Info Session Registration for a school. Triggers deal creation/update if `is_new_school()`, otherwise creates an enquiry.
2. **School Info Session Recording** — Info Session Recording request. Same `is_new_school()` branching as live registration but with a +4 week close date.
3. **School Leading TRP Registration** — Leading TRP event registration. Updates the organisation's `leadingTrp` date and registers for the event.
4. **School Event Confirmation (Ambassador)** — Event Confirmation with `contact_id` provided. Retrieves existing contact details, creates/updates invitation.
5. **School Event Confirmation (Teacher)** — Event Confirmation without `contact_id`. Captures new contact info via `capture_other_contact_info()`, sets `attendance_type` to `Attending Live`.
