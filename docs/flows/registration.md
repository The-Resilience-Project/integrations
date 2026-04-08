# School Registration Flow

Triggered when a school registers for a live info session. The flow branches based on whether the school is new or existing.

> **v1 deprecation:** The v1 Info Session Registration flow (`POST /api/register.php` with `source_form=Info Session Registration`) is deprecated. Use the v2 endpoint below. Other v1 registration flows (Info Session Recording, Leading TRP Registration, Event Confirmation) remain on v1.

---

### Quick Reference

| Layer | Detail | Docs |
|-------|--------|------|
| **Gravity Form** | Info session registration form (via GF Webhooks Add-On) | — |
| **API v2 (current)** | `POST /api/v2/schools/registration` | [v2 Schools Endpoints](../v2/schools.md#post-apiv2schoolsregistration) |
| **API v1 (deprecated for info sessions)** | `POST /api/register.php` | [v1 School Registrations](../v1/registrations/school-registrations.md) |
| **PHP Handler (v2)** | `ApiV2\Application\Schools\SubmitRegistrationHandler` | — |
| **PHP Handler (v1)** | `Registration` trait on `SchoolVTController` | — |
| **VTAP Endpoints** | getEventDetails → setContactsInactive → captureCustomerInfo → getOrgDetails → updateOrganisation → updateContactById → getOrCreateDeal → updateDeal → checkContactRegisteredForEvent → registerContact | [Endpoint Reference](../vtiger/vtap-endpoints.md) |
| **Vtiger Workflow** | "New enquiry — send email to enquirer" (when existing school creates enquiry instead) | [Workflows](../vtiger/workflows.md) |

---

## Flow Diagram — Info Session (New School)

```mermaid
flowchart TD
    Start([Registration form submitted]) --> GED["<b>getEventDetails</b>\nFetch event metadata:\ndate, time, zoom link"]
    GED --> SI["<b>setContactsInactive</b>\nDeactivate existing contacts"]
    SI --> CC{School in system?}
    CC -- "Account number" --> CCA["<b>captureCustomerInfoWithAccountNo</b>"]
    CC -- "New school" --> CCN["<b>captureCustomerInfo</b>"]
    CCA --> GOD["<b>getOrgDetails</b>"]
    CCN --> GOD
    GOD --> UO["<b>updateOrganisation</b>\nSet assignee, track form"]
    UO --> UC["<b>updateContactById</b>"]
    UC --> GCD["<b>getOrCreateDeal</b>\nCreate deal, stage: In Campaign\nRating: Hot"]
    GCD --> UD["<b>updateDeal</b>\nSet firstInfoSessionDate\nAdvance stage → In Campaign\nRating: Hot"]
    UD --> Check["<b>checkContactRegisteredForEvent</b>\nPrevent duplicate registration"]
    Check -- "Not registered" --> RC["<b>registerContact</b>\nRegister for info session"]
    Check -- "Already registered" --> Done([Complete — skip])
    RC --> Done([Complete])
```

## Flow Diagram — Info Session (Existing School)

```mermaid
flowchart TD
    Start([Registration form submitted]) --> GED["<b>getEventDetails</b>"]
    GED --> SI["<b>setContactsInactive</b>"]
    SI --> CC["<b>captureCustomerInfo / WithAccountNo</b>"]
    CC --> GOD["<b>getOrgDetails</b>"]
    GOD --> UO["<b>updateOrganisation</b>"]
    UO --> UC["<b>updateContactById</b>"]
    UC --> CE["<b>createEnquiry</b>\nCreates enquiry instead\nof deal for existing schools"]
    CE --> Done([Complete])
```

## Flow Diagram — Event Confirmation

```mermaid
flowchart TD
    Start([Confirmation form submitted]) --> GED["<b>getEventDetails</b>"]
    GED --> Amb{Ambassador ID\nprovided?}
    Amb -- "Yes" --> GC["<b>getContactById</b>\nLook up ambassador"]
    Amb -- "No — new teacher/parent" --> CC["<b>captureCustomerInfo</b>"]
    GC --> GOD["<b>getOrgDetails</b>"]
    CC --> GOD
    GOD --> UO["<b>updateOrganisation</b>"]
    UO --> INV["<b>createOrUpdateInvitation</b>\nStatus: Date Confirmed"]
    INV --> Check["<b>checkContactRegisteredForEvent</b>"]
    Check -- "Not registered" --> RC["<b>registerContact</b>"]
    Check -- "Already registered" --> Done([Complete])
    RC --> Done
```

---

## Step-by-Step

### 1. Get event details
**Endpoint:** [getEventDetails](../vtiger/vtap-endpoints.md#geteventdetails)

Fetches the event record to get date, time, event number, short name, and zoom link. These are needed for the registration record.

### 2. Customer capture
**Endpoints:** [setContactsInactive](../vtiger/vtap-endpoints.md#setcontactsinactive) → [captureCustomerInfo](../vtiger/vtap-endpoints.md#capturecustomerinfo) / [captureCustomerInfoWithAccountNo](../vtiger/vtap-endpoints.md#capturecustomerinfowithaccountno) → [getOrgDetails](../vtiger/vtap-endpoints.md#getorgdetails) → [updateOrganisation](../vtiger/vtap-endpoints.md#updateorganisation) → [updateContactById](../vtiger/vtap-endpoints.md#updatecontactbyid)

Same customer capture flow as [School Enquiry](enquiry.md#step-by-step) — deactivate, capture, fetch org, apply assignee rules, update org and contact.

### 3. Deal creation/update (new schools only)
**Endpoints:** [getOrCreateDeal](../vtiger/vtap-endpoints.md#getorcreatedeal) → [updateDeal](../vtiger/vtap-endpoints.md#updatedeal)

For new schools registering for an info session:
- Creates the deal if it doesn't exist (stage `In Campaign`, rating `Hot`, pipeline `New Schools`)
- Sets `firstInfoSessionDate` to the event date (earliest of existing vs current)
- Progresses deal stage to `In Campaign` (from `New` or `Considering`)
- Always sets `inCampaignRating` to `Hot`
- Sets close date to event date + 1 day

### 4. Duplicate check and registration
**Endpoints:** [checkContactRegisteredForEvent](../vtiger/vtap-endpoints.md#checkcontactregisteredforevent) → [registerContact](../vtiger/vtap-endpoints.md#registercontact)

Checks if the contact is already registered for this event. If not, creates the registration record with event details and zoom link.

### 5. Enquiry (existing schools only)
**Endpoint:** [createEnquiry](../vtiger/vtap-endpoints.md#createenquiry)

For existing schools (non-new), creates an enquiry instead of a deal. This notifies the assigned staff member about the existing school's interest.

---

## Source Form Variants

| `source_form` | API | Behaviour |
|---------------|-----|-----------|
| Info Session Registration 2026 | **v2** (`/api/v2/schools/registration`) | Full flow: customer capture → deal (In Campaign, Hot) → registration |
| Info Session Registration | **v1** (deprecated) | Same as above but stage `Considering` — use v2 instead |
| Info Session Recording | v1 | Customer capture → deal (Considering) → registration, close date = +4 weeks |
| Leading TRP Registration | v1 | Customer capture → `updateOrganisation` with `leadingTrp` datetime |
| Event Confirmation | v1 | Ambassador lookup or new contact → `createOrUpdateInvitation` → registration |
