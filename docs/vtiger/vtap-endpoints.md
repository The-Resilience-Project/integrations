# VTAP Endpoint Reference

Complete reference for all 37 VTAP webhook endpoints used by the TRP integrations API. Each endpoint runs custom code inside Vtiger CRM to create, update, or retrieve records.

**Base URL:** `https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/{endpoint}`

**Auth:** Each request includes a `token` header with an endpoint-specific auth token.

**Tokens config:** `src/api-v2/Config/webhook_tokens.php` (v2) / `src/api/classes/base.php` `$tokens` (v1)

---

## Quick Reference

| # | Endpoint | Method | Purpose |
|---|----------|--------|---------|
| 1 | [captureCustomerInfo](#capturecustomerinfo) | POST | Create/update contact + organisation by name |
| 2 | [captureCustomerInfoWithAccountNo](#capturecustomerinfowithaccountno) | POST | Create/update contact + organisation by account number |
| 3 | [setContactsInactive](#setcontactsinactive) | POST | Deactivate existing contacts by email |
| 4 | [getContactByEmail](#getcontactbyemail) | GET | Retrieve contact by email address |
| 5 | [getContactById](#getcontactbyid) | GET | Retrieve contact by ID |
| 6 | [getContactDetails](#getcontactdetails) | GET | Retrieve contact details |
| 7 | [updateContactById](#updatecontactbyid) | POST | Update contact assignee and forms completed |
| 8 | [getOrgDetails](#getorgdetails) | GET | Fetch organisation details by ID |
| 9 | [getOrganisationByName](#getorganisationbyname) | GET | Find organisation by name |
| 10 | [getOrgWithAccountNo](#getorgwithaccountno) | GET | Fetch organisation by account number |
| 11 | [getOrgWithName](#getorgwithname) | GET | Fetch organisation by name |
| 12 | [updateOrganisation](#updateorganisation) | POST | Update organisation assignee, status, and fields |
| 13 | [createDeal](#createdeal) | POST | Create a new deal |
| 14 | [getOrCreateDeal](#getorcreatedeal) | POST | Create or retrieve existing deal |
| 15 | [getDealByContactId](#getdealbycontactid) | GET | Retrieve deal by contact ID |
| 16 | [getDealDetails](#getdealdetails) | GET | Retrieve deal by name + organisation name |
| 17 | [getDealDetailsFromAccountNo](#getdealdetailsfromaccountno) | GET | Retrieve deal by name + account number |
| 18 | [updateDeal](#updatedeal) | POST | Update deal stage, dates, and fields |
| 19 | [setDealLineItems](#setdeallineitems) | POST* | Set line items on a deal |
| 20 | [createQuote](#createquote) | POST* | Create quote with line items |
| 21 | [getQuoteWithAccountNo](#getquotewithaccountno) | GET | Retrieve quote by name + account number |
| 22 | [getQuoteWithName](#getquotewithname) | GET | Retrieve quote by name + organisation name |
| 23 | [setQuoteLineItems](#setquotelineitems) | POST* | Set line items on a quote |
| 24 | [createInvoice](#createinvoice) | POST* | Create invoice with line items |
| 25 | [getInvoicesFromAccountNo](#getinvoicesfromaccountno) | GET | Retrieve invoices by account number |
| 26 | [getInvoicesFromOrgName](#getinvoicesfromorgname) | GET | Retrieve invoices by organisation name |
| 27 | [getEventDetails](#geteventdetails) | GET | Fetch event metadata (date, zoom link, etc.) |
| 28 | [checkContactRegisteredForEvent](#checkcontactregisteredforevent) | GET | Check if contact is registered for event |
| 29 | [registerContact](#registercontact) | POST | Register contact for an event |
| 30 | [createOrUpdateInvitation](#createorupdateinvitation) | POST | Create/update event invitation record |
| 31 | [getServices](#getservices) | POST | Retrieve service pricing and details |
| 32 | [getProducts](#getproducts) | POST | Retrieve product pricing and details |
| 33 | [createEnquiry](#createenquiry) | POST | Create enquiry record |
| 34 | [createDateAcceptance](#createdateacceptance) | POST | Create date acceptance record |
| 35 | [updateDateAcceptance](#updatedateacceptance) | POST | Link events to date acceptance |
| 36 | [createAssessment](#createassessment) | POST | Create wellbeing culture assessment |
| 37 | [createOrUpdateSEIP](#createorupdateseip) | POST | Create/update School Engagement Implementation Plan |

\* Uses form-encoded POST with line items instead of JSON body.

---

## Customer Management

### captureCustomerInfo

Creates or updates a contact and organisation in Vtiger. Used when the organisation is identified by **name** (typically a new/unknown organisation).

**Called by:**
- v2: `CustomerService::captureOtherContactInfo()`, `CustomerService::captureCustomerInfo()`
- v1: `SchoolVTController`, `WorkplaceVTController`, `EarlyYearsVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| contactEmail | string | Yes | Contact email address |
| contactFirstName | string | Yes | First name |
| contactLastName | string | Yes | Last name |
| organisationType | string | Yes | `School`, `Workplace`, or `Early Years` |
| organisationName | string | Yes | Organisation name |
| state | string | No | Australian state (VIC, NSW, etc.) |
| contactType | string | No | `Primary`, `Billing`, etc. |
| contactPhone | string | No | Contact phone number |
| orgPhone | string | No | Organisation phone number |
| newsletter | string | No | Newsletter opt-in |
| jobTitle | string | No | Contact job title |
| organisationNumOfStudents | string | No | Number of students |
| organisationNumOfEmployees | string | No | Number of employees |
| contactLeadSource | string | No | Lead source identifier |
| organisationSubType | string | No | Organisation sub-type |

**Response:** `result[0]` → `{ id, account_id, assigned_user_id, cf_contacts_formscompleted }`

---

### captureCustomerInfoWithAccountNo

Same as `captureCustomerInfo` but identifies the organisation by **account number** instead of name. Used when the school/organisation is already in Vtiger.

**Called by:**
- v2: `CustomerService::captureOtherContactInfo()`, `CustomerService::captureCustomerInfo()`
- v1: `SchoolVTController`, `WorkplaceVTController`, `EarlyYearsVTController`

**Request fields:** Same as `captureCustomerInfo` except `organisationName` is replaced with:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationAccountNo | string | Yes | Vtiger account number |

**Response:** Same as `captureCustomerInfo`.

---

### setContactsInactive

Deactivates all existing contacts with a given email address. Called **before** `captureCustomerInfo` to ensure clean contact state.

**Called by:**
- v2: `CustomerService::captureOtherContactInfo()`, `CustomerService::captureCustomerInfo()`
- v1: `ContactAndOrg` trait (used by all service types)

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| contactEmail | string | Yes | Email of contacts to deactivate |

**Response:** Not used (fire-and-forget).

---

### getContactByEmail

Retrieves a contact record by email address.

**Called by:** Token configured but no active callers traced in current codebase. May be used by VTAP internal logic or reserved for future use.

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| contactEmail | string | Yes | Email to search |

**Response:** `result[0]` → contact record.

---

### getContactById

Retrieves contact details by Vtiger ID. Used in event confirmation flows to look up ambassador contacts.

**Called by:**
- v2: `SubmitRegistrationHandler::handle()` (event confirmation flow)
- v1: `ContactAndOrg` trait `get_contact_details()`, `SchoolVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| contactId | string | Yes | Vtiger contact ID (with or without `4x` prefix) |

**Response:** `result[0]` → `{ id, account_id, firstname, lastname, assigned_user_id, cf_contacts_formscompleted }`

---

### getContactDetails

Retrieves detailed contact information.

**Called by:** Token configured but primary usage is through `getContactById`. May be used by VTAP internal logic.

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| contactId | string | Yes | Vtiger contact ID |

**Response:** `result[0]` → full contact record.

---

### updateContactById

Updates contact assignee and tracks which forms the contact has completed (`cf_contacts_formscompleted` picklist).

**Called by:**
- v2: `CustomerService::updateContact()`
- v1: `ContactAndOrg` trait `update_contact()`, `Confirmation` trait

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| contactId | string | Yes | Vtiger contact ID |
| assignee | string | No | User ID (`19xN` format) |
| contactLeadSource | array | No | Forms completed picklist values |
| seipId | string | No | Link contact to SEIP record |

**Response:** Not used.

---

## Organisation Management

### getOrgDetails

Fetches full organisation details including custom fields for sales events, confirmation status, and years with TRP.

**Called by:**
- v2: `CustomerService::fetchOrganisationDetails()`
- v1: `ContactAndOrg` trait `get_organisation_details()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationId | string | Yes | Vtiger org ID (`3x` format) |

**Response:** `result[0]` → `{ accountname, assigned_user_id, cf_accounts_2025salesevents, cf_accounts_freetravel, cf_accounts_yearswithtrp, cf_accounts_2024inspire, cf_accounts_2025inspire, cf_accounts_2025confirmationstatus, cf_accounts_2024confirmationstatus, cf_accounts_2026confirmationstatus }`

---

### getOrganisationByName

Finds an organisation by name.

**Called by:** Token configured but no active callers traced. May be used by VTAP internal logic or reserved for future use.

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationName | string | Yes | Organisation name to search |

**Response:** `result[0]` → organisation record.

---

### getOrgWithAccountNo

Retrieves organisation details by account number. Returns extended data including deal and invoice context.

**Called by:**
- v1: `OrderResources` trait `get_org()`, `OrderResources26` trait, `SchoolVTController` (multiple forms), `EarlyYearsVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationAccountNo | string | Yes | Account number |
| dealName | string | No | Filter by deal name |
| invoiceName | string | No | Filter by invoice name |

**Response:** `result[0]` → `{ id, accountname, assigned_user_id, cf_accounts_*, funded_years, free_travel, ... }`

---

### getOrgWithName

Retrieves organisation details by name. Fallback for organisations without an account number.

**Called by:**
- v1: `OrderResources` trait `get_org()`, `OrderResources26` trait, `SchoolVTController`, `EarlyYearsVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationName | string | Yes | Organisation name |
| dealName | string | No | Filter by deal name |

**Response:** Same as `getOrgWithAccountNo`.

---

### updateOrganisation

Updates organisation assignee, sales events tracking, lead status, and address fields. The most frequently called update endpoint.

**Called by:**
- v2: `CustomerService::updateOrganisation()`, `SubmitRegistrationHandler::handle()`, `SubmitPrizePackHandler::markOrgAs2026Lead()`
- v1: `ContactAndOrg` trait `update_organisation()`, `Confirmation` trait, `Lead` trait (2024/2025/2026 lead marking), `SchoolVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationId | string | Yes | Vtiger org ID |
| assignee | string | No | User ID (`19xN` format) |
| salesEvents2025 | array | No | Sales events picklist values |
| yearsWithTrp | array | No | Years with TRP picklist |
| leadingTrp | string | No | Leading TRP event datetime |
| organisation2026Status | string | No | `Lead` — marks as 2026 lead |
| organisation2025Status | string | No | `Lead` — marks as 2025 lead |
| organisation2024Status | string | No | `Lead` — marks as 2024 lead |
| address, suburb, postcode, state | string | No | Address fields |

**Response:** `result[0]` → `{ assigned_user_id, ... }`

---

## Deal Management

### createDeal

Creates a new deal (potential) in Vtiger.

**Called by:** Token configured. Primary usage is through `getOrCreateDeal` which handles create-or-get logic. May be used by VTAP internal logic.

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| dealName | string | Yes | Deal name (e.g., "2026 School Partnership Program") |
| dealType | string | Yes | `School`, `Workplace`, `Early Years` |
| contactId | string | Yes | Primary contact ID |
| organisationId | string | Yes | Organisation ID |
| assignee | string | Yes | User ID |

**Response:** `result[0]` → deal record with `id`.

---

### getOrCreateDeal

Creates a new deal or retrieves an existing one for the contact/organisation. The primary endpoint for deal management in form flows.

**Called by:**
- v2: `SubmitEnquiryHandler::handle()`, `SubmitRegistrationHandler::createOrUpdateDeal()`
- v1: `Deal` trait `capture_deal_in_vt()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| dealName | string | Yes | Deal name (e.g., "2026 School Partnership Program") |
| dealType | string | Yes | `School`, `Workplace`, `Early Years` |
| dealOrgType | string | Yes | `School - New`, `School - Existing`, `Workplace - New`, etc. |
| dealStage | string | Yes | `New`, `Considering`, `In Campaign`, `Deal Won`, etc. |
| dealCloseDate | string | Yes | Close date in `d/m/Y` format |
| contactId | string | Yes | Primary contact ID |
| organisationId | string | Yes | Organisation ID |
| assignee | string | Yes | User ID (`19xN` format) |
| dealNumOfParticipants | int | No | Number of participants |
| dealState | string | No | Australian state |

**Response:** `result[0]` → `{ id, cf_potentials_firstinfosessiondate, description, sales_stage, cf_potentials_billingnote }`

---

### getDealByContactId

Retrieves deal by contact ID.

**Called by:** Token configured but no active callers traced. May be used by VTAP internal logic or reserved for future use.

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| contactId | string | Yes | Contact ID |

**Response:** `result[0]` → deal record.

---

### getDealDetails

Retrieves deal details by deal name and organisation name. Used for confirmation forms and order resources.

**Called by:**
- v1: `OrderResources` trait `get_deal()`, `OrderResources26` trait, `SchoolVTController` `get_info_for_confirmation_form()`, `EarlyYearsVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| dealName | string | Yes | Deal name |
| organisationName | string | Yes | Organisation name |

**Response:** `result[0]` → `{ id, sales_stage, cf_potentials_*, lineItems }`

---

### getDealDetailsFromAccountNo

Retrieves deal details by deal name and account number. Preferred over `getDealDetails` when account number is available.

**Called by:**
- v1: `OrderResources` trait `get_deal()`, `OrderResources26` trait, `SchoolVTController` (confirmation + ordering forms), `EarlyYearsVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| dealName | string | Yes | Deal name |
| organisationAccountNo | string | Yes | Account number |

**Response:** Same as `getDealDetails`.

---

### updateDeal

Updates deal stage, dates, programs, and financial details. Called after registration, confirmation, and qualification.

**Called by:**
- v2: `SubmitRegistrationHandler::updateDealWithRegistration()`
- v1: `Registration` trait, `Confirmation` trait, `Qualify` trait, `CalendlyProspect` trait, `SchoolVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| dealId | string | Yes | Deal ID |
| dealStage | string | No | `Considering`, `In Campaign`, `Ready To Close`, `Deal Won` |
| dealCloseDate | string | No | Close date (`d/m/Y`) |
| firstInfoSessionDate | string | No | First info session date |
| interestedPrograms | string | No | Interested programs |
| description | string | No | Deal description/notes |
| contactId | string | No | Primary contact |
| address, suburb, postcode, state | string | No | Address fields |
| total | string | No | Deal total |
| billingContactId | string | No | Billing contact ID |
| inspire, engage, extend | string | No | Program selections |
| mentalHealthFunding | string | No | Funding flag |
| kindyUplift | string | No | Kindy uplift flag |
| srf | string | No | SRF flag |
| eyFundingOrg | string | No | Early years funding org |
| billingNote | string | No | Billing notes |
| selectedYearLevels | array | No | Selected year levels |

**Response:** `result[0]` → updated deal data.

---

### setDealLineItems

Sets line items on a deal. Uses form-encoded POST with line items (not JSON).

**Called by:**
- v1: `Confirmation` trait via `post_request_with_line_items()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| dealId | string | Yes | Deal ID |
| total | string | Yes | Deal total |
| lineItems | array | Yes | Line items (form-encoded) |

**Response:** Not used.

---

## Quote & Invoice

### createQuote

Creates a quote with line items. Uses form-encoded POST.

**Called by:**
- v1: `Confirmation` trait via `post_request_with_line_items()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| dealId | string | Yes | Associated deal |
| subject | string | Yes | Quote name |
| type | string | Yes | `School - New`, `School - Existing`, `Early Years`, etc. |
| program | string | Yes | `School`, `Early Years` |
| stage | string | Yes | `Delivered`, `New` |
| contactId | string | Yes | Primary contact |
| contactEmail | string | Yes | Contact email |
| organisationId | string | Yes | Organisation |
| assignee | string | Yes | User ID |
| address, suburb, postcode, state | string | Yes | Address fields |
| preTaxTotal, grandTotal, taxTotal | string | Yes | Financial totals |
| billingContactId | string | No | Billing contact (if different) |
| billingContactEmail | string | No | Billing email |
| lineItems | array | Yes | Service/product line items (form-encoded) |

**Response:** `result` → `{ id }` (quote ID).

---

### getQuoteWithAccountNo

Retrieves a quote by name and account number. Used to get quote details for invoice creation.

**Called by:**
- v1: `OrderResources` trait `get_quote()`, `OrderResources26` trait, `Assess` trait `get_quote_contact()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Quote name |
| organisationAccountNo | string | Yes | Account number |

**Response:** `result[0]` → `{ id, potential_id, cf_quotes_billingcontactname, account_id, contact_id, lineItems }`

---

### getQuoteWithName

Retrieves a quote by name and organisation name. Fallback when account number is unavailable.

**Called by:**
- v1: `OrderResources` trait `get_quote()`, `OrderResources26` trait

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Quote name |
| organisationName | string | Yes | Organisation name |

**Response:** Same as `getQuoteWithAccountNo`.

---

### setQuoteLineItems

Sets line items on an existing quote. Uses form-encoded POST.

**Called by:** Token configured but no active callers traced in current codebase. Quote line items are typically set during `createQuote`.

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| quoteId | string | Yes | Quote ID |
| lineItems | array | Yes | Line items (form-encoded) |

**Response:** Not documented.

---

### createInvoice

Creates an invoice with line items. Uses form-encoded POST. The most complex endpoint with the most request fields.

**Called by:**
- v1: `OrderResources` trait via `post_request_with_line_items()`, `OrderResources26` trait

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| subject | string | Yes | Invoice name |
| invoiceDate | string | Yes | Invoice date (`d/m/Y`) |
| contactId | string | Yes | Primary contact |
| assignee | string | Yes | User ID |
| dealId | string | Yes | Associated deal |
| theQuoteId | string | Yes | Associated quote |
| organisationId | string | Yes | Organisation |
| seipId | string | No | SEIP record (2026+) |
| dueDate | string | Yes | Due date (`d/m/Y`) |
| status | string | Yes | `Auto Created`, `Unconfirmed Deal` |
| poNumber | string | No | Purchase order number |
| selectedYearLevels | array | No | Year levels |
| engageHubCourses | array | No | Engage hub course selections |
| studentInspireHubCourses | array | No | Student inspire hub courses |
| teacherInspireHubCourses | array | No | Teacher inspire hub courses |
| billingAddress, billingSuburb, billingPostcode, billingState | string | Yes | Billing address |
| shippingAddress, shippingSuburb, shippingPostcode, shippingState | string | Yes | Shipping address |
| orderPlacedBy | string | Yes | Who placed the order |
| billingContactId | string | No | Billing contact (if different) |
| shipBy | string | No | Ship by date |
| holdUntil | string | No | Hold until date |
| lineItems | array | Yes | Invoice line items (form-encoded) |

**Response:** `result` → `{ id }` (invoice ID).

---

### getInvoicesFromAccountNo

Retrieves invoices for an organisation by account number. Used to determine if an order is the first invoice (different pricing/shipping).

**Called by:**
- v1: `OrderResources` trait `is_first_invoice()`, `OrderResources26` trait, `SchoolVTController` `get_info_for_curric_ordering_form()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationAccountNo | string | Yes | Account number |
| invoiceName | string | No | Filter by invoice name |

**Response:** `result` → array of invoice records with `createdtime`.

---

### getInvoicesFromOrgName

Retrieves invoices by organisation name. Fallback when account number is unavailable.

**Called by:**
- v1: `OrderResources` trait `is_first_invoice()`, `OrderResources26` trait

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationName | string | Yes | Organisation name |
| invoiceName | string | No | Filter by invoice name |

**Response:** Same as `getInvoicesFromAccountNo`.

---

## Event & Registration

### getEventDetails

Fetches event metadata needed for registration — date, time, event number, zoom link. Called at the start of registration flows.

**Called by:**
- v2: `SubmitRegistrationHandler::getEventDetails()`
- v1: `Registration` trait `get_event_details()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| eventId | string | Yes | Event ID (with or without `18x` prefix) |

**Response:** `result[0]` → `{ date_start, time_start, event_no, cf_events_shorteventname, cf_events_zoomlink }`

---

### checkContactRegisteredForEvent

Checks if a contact is already registered for an event. Prevents duplicate registrations.

**Called by:**
- v2: `SubmitRegistrationHandler::registerContactForEvent()`
- v1: `Registration` trait `is_contact_registered_for_event()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| eventNo | string | Yes | Event number |
| contactId | string | Yes | Contact ID |

**Response:** `result` → empty array if not registered, non-empty if already registered.

---

### registerContact

Registers a contact for an event. Only called after `checkContactRegisteredForEvent` confirms no duplicate.

**Called by:**
- v2: `SubmitRegistrationHandler::registerContactForEvent()`
- v1: `Registration` trait `register_contact_for_event()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| eventId | string | Yes | Event ID |
| eventNo | string | Yes | Event number |
| eventShortName | string | Yes | Short event name |
| eventStart | string | Yes | Event start datetime |
| eventZoomLink | string | Yes | Zoom link for event |
| registrationName | string | Yes | `"{ContactName} \| {EventNo}"` |
| contactId | string | Yes | Contact ID |
| dealId | string | No | Associated deal (if exists) |
| source | string | Yes | Source form name |
| attendanceType | string | No | In-person or online |
| replyTo | string | No | User ID for reply-to email |

**Response:** `result[0]` → `{ id }` (registration ID).

---

### createOrUpdateInvitation

Creates or updates an invitation record for event confirmations. Records that an organisation has confirmed attendance.

**Called by:**
- v2: `SubmitRegistrationHandler::handle()` (event confirmation flow)
- v1: `SchoolVTController` `submit_event_registration()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationId | string | Yes | Organisation ID |
| eventId | string | Yes | Event number |
| status | string | Yes | `Date Confirmed` |
| name | string | Yes | Deal name (e.g., "2025 School Partnership Program") |

**Response:** Not used.

---

## Catalogue

### getServices

Retrieves service records by service numbers or IDs. Returns pricing data for building quotes and invoices.

**Called by:**
- v1: `VTController::get_services()` — used by `Confirmation`, `OrderResources`, `OrderResources26`, `SchoolVTController`, `EarlyYearsVTController`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| serviceNumbers | array | Yes | Service codes (e.g., `['SER12', 'SER15']`) |
| serviceIds | array | Yes | Service IDs |

**Response:** `result` → array of `{ id, service_no, unit_price, cf_services_xerocode, ... }`

---

### getProducts

Retrieves product records by product numbers. Returns pricing data for invoice line items.

**Called by:**
- v1: `VTController::get_products()` — used by `OrderResources`, `OrderResources26`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| productNumbers | array | Yes | Product codes (e.g., `['PRO18']`) |

**Response:** `result` → array of product records with pricing.

---

## Enquiry & Sales

### createEnquiry

Creates an enquiry record in Vtiger. Triggered by enquiry form submissions and some registration flows (existing schools registering for info sessions).

**Called by:**
- v2: `SubmitEnquiryHandler::handle()`, `SubmitRegistrationHandler::handle()`
- v1: `Enquiry` trait, `Qualify` trait

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| enquirySubject | string | Yes | `"{ContactName} \| {OrgName}"` |
| enquiryBody | string | Yes | Enquiry text or `Conference Enquiry` |
| contactId | string | Yes | Contact ID |
| assignee | string | Yes | User ID |
| enquiryType | string | Yes | `School`, `Workplace`, `Early Years`, `General`, `Imperfects` |
| workplaceInterestedPrograms | array | No | Workplace program interests |
| source | string | No | Source form name |

**Response:** Not used.

> **Note:** Creating an enquiry triggers the **"New enquiry - send email to enquirer"** Vtiger workflow. This workflow must be disabled during bulk imports — see [Workflows](workflows.md).

---

### createDateAcceptance

Creates a date acceptance record when a school accepts event dates.

**Called by:**
- v1: `AcceptDates` trait `create_date_acceptance_record()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| dateAcceptanceSubject | string | Yes | `"2026 School Date Acceptance"` |
| email | string | Yes | Contact email |
| emailBody | string | Yes | HTML-formatted acceptance details |
| organisationId | string | Yes | Organisation ID (`3x` format) |
| acceptedEvents | string | Yes | Comma-separated event numbers |

**Response:** `result` → `{ id }` (date acceptance ID).

---

### updateDateAcceptance

Links individual events to a date acceptance record. Called once per accepted event.

**Called by:**
- v1: `AcceptDates` trait `link_documents()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| eventNo | string | Yes | Event number to link |
| dateAcceptanceId | string | Yes | Date acceptance record ID |
| allDocumentsLinked | boolean | No | `true` when final event linked |

**Response:** Not used (fire-and-forget).

---

## Specialised

### createAssessment

Creates a Wellbeing Culture Assessment record with domain scores and individual question responses (91 fields).

**Called by:**
- v1: `Assess` trait `create_assessment()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| organisationId | string | Yes | Organisation ID |
| assessmentName | string | Yes | `"2026 Wellbeing Culture Assessment"` |
| contactId | string | Yes | Contact who completed it |
| orgType | string | Yes | `"School - New"`, etc. |
| visionAndPractice | string | Yes | Domain score: `Emerging`, `Established`, `Excelling` |
| explicitTeaching | string | Yes | Domain score |
| habitBuilding | string | Yes | Domain score |
| staffCapacity | string | Yes | Domain score |
| staffWellbeing | string | Yes | Domain score |
| familyCapacity | string | Yes | Domain score |
| partnerships | string | Yes | Domain score |
| VP01–VP14 | boolean | Yes | Vision & Practice question responses |
| ET01–ET14 | boolean | Yes | Explicit Teaching question responses |
| HB01–HB14 | boolean | Yes | Habit Building question responses |
| SC01–SC13 | boolean | Yes | Staff Capacity question responses |
| SW01–SW12 | boolean | Yes | Staff Wellbeing question responses |
| FC01–FC12 | boolean | Yes | Family Capacity question responses |
| P01–P12 | boolean | Yes | Partnerships question responses |

**Response:** `result` → `{ id }` (assessment ID).

---

### createOrUpdateSEIP

Creates or updates a School Engagement Implementation Plan. The central record linking a school's deal, assessment, and year-level participation.

**Called by:**
- v1: `Confirmation` trait `createSEIP()`, `Assess` trait `update_seip_with_ca()`, `SchoolVTController` `get_info_for_ltrp_form()`, `OrderResources26` trait `get_seip()`

**Request fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| seipName | string | Yes | `"2026 SEIP"` |
| organisationId | string | Yes | Organisation ID |
| dateConfirmed | string | No | Confirmation date (`d/m/Y`) |
| assignee | string | No | User ID |
| participants | int | No | Number of participants |
| dealId | string | No | Associated deal |
| yearsWithTrp | string | No | `"1st year"`, `"2nd year"`, etc. |
| wellbeingCultureAssessmentId | string | No | Link to assessment |
| caCompleted | string | No | Assessment completion date (`d/m/Y`) |
| schoolContext | string | No | HTML description of school context |

**Response:** `result[0]` → `{ id, fld_leadingtrpwatched, fld_cacompleted, cf_vtcmseip_numberofparticipants }`
