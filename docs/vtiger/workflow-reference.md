# Vtiger Workflow Reference

> Auto-generated on 27 Mar 2026 by `scripts/scrape-vtiger-workflow-details.ts`
> 113 workflows documented

## Contents

- [Contacts](#contacts) (4)
- [Date Acceptances](#date-acceptances) (1)
- [Deals](#deals) (44)
- [Enquiries](#enquiries) (3)
- [Events](#events) (9)
- [Invoice](#invoice) (16)
- [Organisations](#organisations) (1)
- [Quotes](#quotes) (12)
- [Registration](#registration) (19)
- [SEIP](#seip) (3)
- [creditnotes](#creditnotes) (1)

---

## Contacts

### Send email to contact step 2

| Property | Value |
|----------|-------|
| **ID** | 94 |
| **Module** | Contacts |
| **Trigger** | Time Interval (On Specific Date) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send email to contact step 2 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=94&mode=V7Edit) |

**Conditions:**

*All of:*
- `(account_id : (Accounts) cf_accounts_2024confirmationstatus)` is not `Confirmed,Not Interested`

**Actions:**

1. **Send Email** — send email
   - **To:** `,$(contact_id : (Contacts) email)`
   - **Subject:** Welcome to The Resilience Project’s 2024 Partnership Program!

---

### Send email to contact step 3

| Property | Value |
|----------|-------|
| **ID** | 95 |
| **Module** | Contacts |
| **Trigger** | Time Interval (On Specific Date) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send email to contact step 3 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=95&mode=V7Edit) |

**Conditions:**

*All of:*
- `(account_id : (Accounts) cf_accounts_2024confirmationstatus)` is not `Confirmed,Not Interested`

**Actions:**

1. **Send Email** — send email
   - **To:** `,$(contact_id : (Contacts) email)`
   - **Subject:** Welcome to The Resilience Project’s 2024 Partnership Program!

---

### Update Confirmation Form

| Property | Value |
|----------|-------|
| **ID** | 42 |
| **Module** | Contacts |
| **Trigger** | Time Interval (On Specific Date) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update Confirmation Form |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=42&mode=V7Edit) |

**Conditions:**

```
All :   -NA-
Any : -NA-
```

**Actions:**

1. **Update Fields** — Update Confirmation Form
   - `cf_contacts_confirmationform` → `if (account_id : (Accounts) cf_accounts_organisationtype) == 'Workplace' then 'Workplace' else if  (account_id : (Accounts) cf_accounts_organisationtype) == 'School' and (account_id : (Accounts) source) == 'WEBSERVICE' then 'New School' else if  (account_id : (Accounts) cf_accounts_organisationtype) == 'Early Years Centre' then 'Early Years' else if  (account_id : (Accounts) cf_accounts_organisationtype) == 'School' and (account_id : (Accounts) source) != 'WEBSERVICE' then 'Existing School' else ' ' end`

---

### Update Confirmation URL - New School

| Property | Value |
|----------|-------|
| **ID** | 47 |
| **Module** | Contacts |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update Confirmation URL - New School |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=47&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_contacts_confirmationform` has changed to `New School`

**Actions:**

1. **Webhook** — Update Confirmation URL - New School
   - **URL:** `https://devl06.borugroup.com/resilience/Potentials/updateConfirmationURLContact.php`
   - **Method:** POST
   - **Parameters:**
     - `cf_contacts_confirmationform` ← `New School`

---

## Date Acceptances

### Send Date Acceptance Confirmation to contact

| Property | Value |
|----------|-------|
| **ID** | 164 |
| **Module** | Date Acceptances |
| **Trigger** | Date Acceptances creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send email with accepted dates to contact and set event confirmation status |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=164&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmdateacceptances_emailbody` is not empty
- `cf_vtcmdateacceptances_confirmationsentto` is not empty

**Actions:**

1. **Send Email** — Send Date Acceptance Confirmation Email
   - **To:** `$cf_vtcmdateacceptances_confirmationsentto`
   - **From:** `bookings@theresilienceproject.com.au`
   - **Subject:** The Resilience Project - Your confirmed 2026 presentation dates
   - **Body excerpt:** Thank you for confirming the following presentation dates for 2026: $cf_vtcmdateacceptances_emailbody **Please add these dates to your school calendar** We will send further details for each of your events approximately 4 weeks prior to each presentation. Feel free to contact me or your School Partn

2. **Update Fields** — Set Event Confirmation Status = Dates Accepted
   - `(cf_vtcmdateacceptances_organisation : (Accounts) cf_accounts_eventstatus)` → `Dates Accepted`

---

## Deals

### 2024 Deal Update Org 2024 Confirmation Status

| Property | Value |
|----------|-------|
| **ID** | 132 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | multipath |
| **Status** | Enabled |
| **Description** | Update the 2024 confirmation status to match the deal sales stage |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=132&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2024`

---

### 2025 Deal Update Org 2025 Confirmation Status

| Property | Value |
|----------|-------|
| **ID** | 130 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | multipath |
| **Status** | Disabled |
| **Description** | Update the 2025 confirmation status to match the deal sales stage |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=130&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2025`
*All of:*
- `cf_potentials_orgtype` is not `Workplace - Existing`
- `sales_stage` is not `New`

---

### 2027 Deal Update Org 2027 Confirmation Status

| Property | Value |
|----------|-------|
| **ID** | 208 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | multipath |
| **Status** | Enabled |
| **Description** | Update the 2027 confirmation status to match the deal sales stage |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=208&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2027`
*All of:*
- `cf_potentials_orgtype` is not `Workplace - Existing`
- `sales_stage` is not `New`

---

### 206 Deal Update Org 2026 Confirmation Status

| Property | Value |
|----------|-------|
| **ID** | 168 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | multipath |
| **Status** | Enabled |
| **Description** | Update the 2026 confirmation status to match the deal sales stage |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=168&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2026`
*All of:*
- `cf_potentials_orgtype` is not `Workplace - Existing`
- `sales_stage` is not `New`

---

### Automated reminder for curric ordering

| Property | Value |
|----------|-------|
| **ID** | 159 |
| **Module** | Deals |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send a reminder to confirmed existing schools to place their curriculum order (confirmed after 16 Sept) |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=159&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` is `2025 School Partnership Program`
- `cf_potentials_orgtype` is `School - Existing`
- `sales_stage` is `Deal Won`
- `closingdate` after `2024-09-16`
- `(related_to : (Accounts) cf_accounts_curriculumordered)` is empty
*All of:*
- `closingdate` days ago `14`
- `closingdate` days ago `28`

**Actions:**

1. **Send Email** — Send reminder to existing confirmed schools to place their curric order
   - **To:** `$(contact_id : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** TRP | Order 2025 Program Resources
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname) I just wanted to reach out and remind you to please order your curriculum resources for 2025 to ensure that you receive your resources by the end of the school year. Please click on the link below to place your order. CLICK HERE TO ORDER YOUR PROGRAM RESOURCES

---

### Calculate or Update Weighted Revenue

| Property | Value |
|----------|-------|
| **ID** | 12 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Calculate or Update Weighted Revenue |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=12&mode=V7Edit) |

**Conditions:**

```
All :   -NA-
Any : -NA-
```

**Actions:**

1. **Update Fields** — update Weighted Revenue
   - `forecast_amount_currency_value` → `amount * probability / 100`

---

### Create Project On Successful Close

| Property | Value |
|----------|-------|
| **ID** | 35 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Create a new project and assign it to the organization owner. |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=35&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `Closed Won`

**Actions:**

1. **Create Record** — Create Project

---

### Early Years - internal email notification

| Property | Value |
|----------|-------|
| **ID** | 111 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send email to TRP team when EY confirmed |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=111&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` is `2026 Early Years Partnership Program`
- `sales_stage` has changed to `Deal Won`
- `cf_potentials_orgtype` is `Early Years - New,Early Years - Existing`

**Actions:**

1. **Send Email** — Early Years Confirmation
   - **To:** `emma@theresilienceproject.com.au,ben@theresilienceproject.com.au,brendan.close@theresilienceproject.com.au,maddie@theresilienceproject.com.au,enquiries@theresilienceproject.com.au,aretha@theresilienceproject.com.au,monica.butera@theresilienceproject.com.au`
   - **From:** `earlyyears@theresilienceproject.com.au`
   - **Subject:** An EY service has confirmed for 2026!
   - **Body excerpt:** An EY service has confirmed for 2026! Service name: $related_to Deal stage: $sales_stage Number of children: $cf_potentials_numberofparticipants State: $cf_potentials_state Org Type: $cf_potentials_orgtype Applying for SRF? $cf_potentials_applyingforsrf Applying for Kindy Uplift? $cf_potentials_appl

---

### Existing School - Send Mail on Deal Creation with Number Of Students less than 200

| Property | Value |
|----------|-------|
| **ID** | 71 |
| **Module** | Deals |
| **Trigger** | Deals creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Existing School - Send Mail on Deal Creation with Number Of Students less than 200 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=71&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `WEBSERVICE`
- `cf_potentials_numberofparticipants` less than `200`
*All of:*
- `(contact_id : (Contacts) cf_contacts_confirmationform)` is `Existing School (F2F/Digital)`
- `(contact_id : (Contacts) cf_contacts_confirmationform)` is `Existing School (Digital Only)`

**Actions:**

1. **Send Email** — Existing School - Send Mail on Deal Creation with Number Of Students less than 200
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `education@theresilienceproject.com.au`
   - **Subject:** Welcome to The Resilience Project’s 2023 Partnership Program!
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you for confirming your school’s involvement in The Resilience Project’s Partnership Program in 2023. We are thrilled to have the opportunity to continue to support the wellbeing of your whole school community. We will be in touch shortly to outline nex

---

### Existing School Confirmation 2026 - internal email notification

| Property | Value |
|----------|-------|
| **ID** | 147 |
| **Module** | Deals |
| **Trigger** | Only first time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send internal notification that an existing school has confirmed for 2026 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=147&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` is `2026 School Partnership Program`
- `cf_potentials_orgtype` is `School - Existing`
- `sales_stage` has changed to `Deal Won`

**Actions:**

1. **Send Email** — Existing School Confirmation
   - **To:** `aretha@theresilienceproject.com.au,benf@theresilienceproject.com.au,ben@theresilienceproject.com.au,connor@theresilienceproject.com.au,elias@theresilienceproject.com.au,lillian@theresilienceproject.com.au,lucy@theresilienceproject.com.au,lucy.cody-davis@theresilienceproject.com.au,luke@theresilienceproject.com.au,maddie@theresilienceproject.com.au,phoebe@theresilienceproject.com.au,samantha@theresilienceproject.com.au,sam.desousa@theresilienceproject.com.au,sam@theresilienceproject.com.au,tom.ferguson@theresilienceproject.com.au,whitney@theresilienceproject.com.au,monica.butera@theresilienceproject.com.au`
   - **Subject:** An existing school has confirmed for 2026!
   - **Body excerpt:** An existing school has confirmed for 2026! Assigned to: $(related_to : (Accounts) assigned_user_id) School name: $related_to Priority: $(related_to : (Accounts) cf_accounts_priority) Engage: $cf_potentials_curriculum Inspire: $cf_potentials_presentations Extend: $cf_potentials_additionaloptions Numb

2. **Update Fields** — Set confirmed date
   - `(related_to : (Accounts) cf_accounts_2026confirmationdate)` → `get_date('today')`

---

### Existing school deal - internal email notification

| Property | Value |
|----------|-------|
| **ID** | 99 |
| **Module** | Deals |
| **Trigger** | Deals creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Internal notification email when an existing 2024 deal is created |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=99&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2024`
- `sales_stage` is `Deal Won`
- `cf_potentials_orgtype` is `School - Existing`

**Actions:**

1. **Send Email** — Existing School Confirmation
   - **To:** `maddie@theresilienceproject.com.au, ben@theresilienceproject.com.au, helenor@theresilienceproject.com.au, elias@theresilienceproject.com.au, whitney@theresilienceproject.com.au, phoebe@theresilienceproject.com.au, luke@theresilienceproject.com.au, courteney@theresilienceproject.com.au, lillian@theresilienceproject.com.au, sam@theresilienceproject.com.au, bookings@theresilienceproject.com.au`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** An existing school has confirmed for 2024!
   - **Body excerpt:** An existing school has confirmed for 2024! Assigned to: $(related_to : (Accounts) assigned_user_id) School name: $related_to Deal stage: $sales_stage Engage: $cf_potentials_curriculum Inspire: $cf_potentials_presentations Extend: $cf_potentials_additionaloptions Number of students: $cf_potentials_nu

---

### EY Hub Access (Existing)

| Property | Value |
|----------|-------|
| **ID** | 205 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Give Hub Access to EY Centres |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=205&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` is `2026 Early Years Partnership Program`
- `cf_potentials_eyhubaccessprovisioned` is `1`
- `(related_to : (Accounts) cf_accounts_trphubambassadoraccess)` is empty
- `cf_potentials_schoolpostcode` is not empty
- `cf_potentials_orgtype` is `Early Years - Existing`

**Actions:**

1. **Webhook** — Create subscription in Hub
   - **URL:** `https://lh9sixjcw4.execute-api.ap-southeast-2.amazonaws.com/prod/schools/create`
   - **Method:** POST

2. **Update Fields** — Set Hub access date
   - `(related_to : (Accounts) cf_accounts_trphubambassadoraccess)` → `get_date('today')`

---

### EY Hub Access (New)

| Property | Value |
|----------|-------|
| **ID** | 171 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Give Hub Access to EY Centres |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=171&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` is `2026 Early Years Partnership Program`
- `cf_potentials_eyhubaccessprovisioned` is `1`
- `(related_to : (Accounts) cf_accounts_trphubambassadoraccess)` is empty
- `cf_potentials_schoolpostcode` is not empty
- `cf_potentials_orgtype` is `Early Years - New`

**Actions:**

1. **Webhook** — Create subscription in Hub
   - **URL:** `https://lh9sixjcw4.execute-api.ap-southeast-2.amazonaws.com/prod/schools/create`
   - **Method:** POST

2. **Update Fields** — Set Hub access date
   - `(related_to : (Accounts) cf_accounts_trphubambassadoraccess)` → `get_date('today')`

---

### Mark school deal as dormant

| Property | Value |
|----------|-------|
| **ID** | 143 |
| **Module** | Deals |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Mark a school propsect as dormant |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=143&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `New`
- `cf_potentials_orgtype` is `School - New`
- `potentialname` contains `2026`
- `closingdate` more than days ago `29`
- `closingdate` less than days ago `30`

**Actions:**

1. **Update Fields** — Automatically mark as dormant
   - `sales_stage` → `Dormant`
   - `(related_to : (Accounts) cf_accounts_2026confirmationstatus)` → `Dormant`

---

### Mark workplace deal as dormant

| Property | Value |
|----------|-------|
| **ID** | 140 |
| **Module** | Deals |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Mark a workplace lead as dormant |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=140&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `New`
- `cf_potentials_orgtype` is `Workplace - New`
- `closingdate` more than days ago `22`
- `closingdate` less than days ago `23`

**Actions:**

1. **Update Fields** — Automatically mark as dormant
   - `sales_stage` → `Dormant`
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `Dormant`

---

### MM - Update School 2024 Confirmation Status when Closed Lost

| Property | Value |
|----------|-------|
| **ID** | 119 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update 2024 confirmation status to not interested when deal is closed lost |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=119&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2024`
- `sales_stage` is `Closed Lost`
- `cf_potentials_orgtype` is `School - New,School - Existing`

**Actions:**

1. **Update Fields** — Update 2024 Confirmation Status to Not Interested
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `Not Interested`

---

### MM - Update School 2024 Confirmation Status when Deal Won

| Property | Value |
|----------|-------|
| **ID** | 118 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | When deal stage = deal won, update organisation 2024 confirmation status to 'Confirmed' |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=118&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2024`
- `sales_stage` is `Deal Won`
*All of:*
- `cf_potentials_orgtype` is `School - New`
- `cf_potentials_orgtype` is `School - Existing`

**Actions:**

1. **Update Fields** — Update 2024 Confirmation Status
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `Confirmed`

---

### MM - Update Workplace 2024 Confirmation Status - Closed Lost

| Property | Value |
|----------|-------|
| **ID** | 125 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update confirmation status to 'Not Interested' when deal sales stage is updated to 'Closed Lost' |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=125&mode=V7Edit) |

**Conditions:**

*All of:*
- `closingdate` after `2024-01-01`
- `cf_potentials_orgtype` is `Workplace - New`
- `sales_stage` is `Closed Lost`

**Actions:**

1. **Update Fields** — Update org status when deal status is updated
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `Not Interested`

---

### MM - Update Workplace 2024 Confirmation Status - Considering

| Property | Value |
|----------|-------|
| **ID** | 122 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update 2024 Confirmation status to considering when deal sales stage is updated to considering |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=122&mode=V7Edit) |

**Conditions:**

*All of:*
- `closingdate` after `2024-01-01`
- `cf_potentials_orgtype` is `Workplace - New`
- `sales_stage` is `Considering`

**Actions:**

1. **Update Fields** — Update org status when deal status is updated
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `Considering`

---

### MM - Update Workplace 2024 Confirmation Status - Deal Won

| Property | Value |
|----------|-------|
| **ID** | 124 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update org 2024 confirmation status to confirmed when deal stage is updated to 'deal won' or 'closed INV' |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=124&mode=V7Edit) |

**Conditions:**

*All of:*
- `closingdate` after `2024-01-01`
- `cf_potentials_orgtype` is `Workplace - New`
*All of:*
- `sales_stage` is `Deal Won`
- `sales_stage` is `Closed INV`

**Actions:**

1. **Update Fields** — Update org status when deal status is updated
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `Confirmed`

---

### MM - Update Workplace 2024 Confirmation Status - NEW Prospect

| Property | Value |
|----------|-------|
| **ID** | 121 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | 2024 Confirmation Status is updated to 'New' when a related deal status is updated to 'New Prospect' |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=121&mode=V7Edit) |

**Conditions:**

*All of:*
- `closingdate` after `2024-01-01`
- `cf_potentials_orgtype` is `Workplace - New`
- `sales_stage` is `New`

**Actions:**

1. **Update Fields** — Update org status when deal status is updated
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `New Prospect`

---

### MM - Update Workplace 2024 Confirmation Status - Ready to Close

| Property | Value |
|----------|-------|
| **ID** | 123 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update 2024 Confirmation Status to ready to close when a related deal status is updated to ready to close |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=123&mode=V7Edit) |

**Conditions:**

*All of:*
- `closingdate` after `2024-01-01`
- `cf_potentials_orgtype` is `Workplace - New`
- `sales_stage` is `Ready to close`

**Actions:**

1. **Update Fields** — Update org status when deal status is updated
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `Ready to Close`

---

### New partnership school

| Property | Value |
|----------|-------|
| **ID** | 36 |
| **Module** | Deals |
| **Trigger** | Deals creation |
| **Type** | multipath |
| **Status** | Disabled |
| **Description** | Trigger onboarding process when new partnership school deal is created. |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=36&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` is `Partnership Program`
- `sales_stage` is `Closed Won`
- `cf_potentials_orgtype` contains `School`

---

### New school - internal email notification

| Property | Value |
|----------|-------|
| **ID** | 5 |
| **Module** | Deals |
| **Trigger** | Only first time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send Email to user on Deal creation |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=5&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2024 School Partnership Program`
- `sales_stage` is `Deal Won`
- `cf_potentials_orgtype` is `School - New`

**Actions:**

1. **Send Email** — New School Confirmation
   - **To:** `enquiries@theresilienceproject.com.au,ben@theresilienceproject.com.au,helenor@theresilienceproject.com.au,victor@theresilienceproject.com.au,maddie@theresilienceproject.com.au,kim@theresilienceproject.com.au`
   - **Subject:** A new school has confirmed!
   - **Body excerpt:** A new school has confirmed for 2025! @$assigned_user_id - please manually update the deal name, quote name and 2024/25 org status, Assigned to: $assigned_user_id School name: $related_to Deal stage: $sales_stage Number of students: $cf_potentials_numberofparticipants State: $cf_potentials_state Usin

---

### New School - Send Mail on Deal Creation with Number Of Students less than 200

| Property | Value |
|----------|-------|
| **ID** | 69 |
| **Module** | Deals |
| **Trigger** | Deals creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | New School - Send Mail on Deal Creation with Number Of Students less than 200 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=69&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `WEBSERVICE`
- `cf_potentials_numberofparticipants` less than `200`
- `(contact_id : (Contacts) cf_contacts_confirmationform)` is `New School`

**Actions:**

1. **Send Email** — New School - Send Mail on Deal Creation with Number Of Students less than 200
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `education@theresilienceproject.com.au`
   - **Subject:** Welcome to The Resilience Project’s 2023 Partnership Program!
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you for confirming your school’s involvement in The Resilience Project’s Partnership Program in 2023. We are thrilled to have the opportunity to partner with you and support the wellbeing of your whole school community. Your dedicated School Partnership

---

### New School Confirmation 2026 - internal email notification

| Property | Value |
|----------|-------|
| **ID** | 136 |
| **Module** | Deals |
| **Trigger** | Only first time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send internal notification that a new school has confirmed for 2026 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=136&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` is `2026 School Partnership Program`
- `cf_potentials_orgtype` is `School - New`
- `sales_stage` has changed to `Deal Won`

**Actions:**

1. **Send Email** — New School Confirmation
   - **To:** `ian.newmarch@theresilienceproject.com.au,laura.ng@theresilienceproject.com.au,ben@theresilienceproject.com.au,victor@theresilienceproject.com.au,maddie@theresilienceproject.com.au,brendan.close@theresilienceproject.com.au,monica.butera@theresilienceproject.com.au,`
   - **Subject:** A new school has confirmed!
   - **Body excerpt:** A new school has confirmed for 2026! Assigned to: $assigned_user_id School name: $related_to Deal stage: $sales_stage Number of participants: $cf_potentials_numberofparticipants Selected year levels: $cf_potentials_selectedyearlevels State: $cf_potentials_state Here is a link to the Deal record: CRM

2. **Update Fields** — Set Confirmation Date
   - `(related_to : (Accounts) cf_accounts_2026confirmationdate)` → `get_date('today')`

---

### Notify Dawn of Confirmed Planner School

| Property | Value |
|----------|-------|
| **ID** | 188 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Notify Dawn of confirmed planner school |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=188&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_potentials_curriculum` is `Planners`
- `sales_stage` is `Deal Won`
*All of:*
- `cf_potentials_curriculum` has changed ``
- `sales_stage` has changed

**Actions:**

1. **Send Email** — Send email to Dawn about Planner school
   - **To:** `dawn.maxa@theresilienceproject.com.au`
   - **Subject:** Planner School has confirmed
   - **Body excerpt:** Hi Dawn A planner school has confirmed School name: $related_to CRM Detail View URL

---

### Notify of cancellation

| Property | Value |
|----------|-------|
| **ID** | 167 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Notify the correct people about a cancelled deal |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=167&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2026`
- `sales_stage` is `Closed Lost`
- `opportunity_type` is `School`
- `cf_potentials_quotecreated` is `1`

**Actions:**

1. **Send Email** — Send cancelled deal next steps
   - **To:** `bookings@theresilienceproject.com.au,accounts@theresilienceproject.com.au,dawn.maxa@theresilienceproject.com.au,,$(related_to : (Accounts) assigned_user_id : (Users) email1)`
   - **Subject:** Cancelled deal - next steps
   - **Body excerpt:** Hi Mel, Helen, Dawn, Sarah and $(related_to : (Accounts) assigned_user_id) $related_to has cancelled their deal for 2026. If they have previously confirmed and placed a curric order, please action the following items: Accounts: Cancel their invoice in Xero Sarah: Ensure they have been removed from t

---

### Notify Sarah when extend and inspire options are changed

| Property | Value |
|----------|-------|
| **ID** | 151 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email to Sarah when Extend and Inspire options have changed |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=151&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_potentials_quotecreated` is `1`
- `potentialname` is `2026 School Partnership Program`
*All of:*
- `cf_potentials_additionaloptions` has changed ``
- `cf_potentials_presentations` has changed ``

**Actions:**

1. **Send Email** — Notify Events about changed Extend and Inspire options
   - **To:** `bookings@theresilienceproject.com.au`
   - **Subject:** $related_to Extend and/or Inspire Options has changed
   - **Body excerpt:** Hi Sarah, Extend or Inspire options for $related_to has changed. New Extend options: $cf_potentials_additionaloptions New Inspire options: $cf_potentials_presentations To view the old options, please view the Recent Updates tab of the Deal. CRM Detail View URL https://theresilienceproject.od2.vtiger

---

### Populate Changed to Deal Won date if blank

| Property | Value |
|----------|-------|
| **ID** | 173 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Populate Changed to Deal Won date if blank when a deal is in Prepaid or Closed INV |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=173&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_potentials_changedtodealwon` is empty
*All of:*
- `sales_stage` has changed to `Prepaid`
- `sales_stage` has changed to `Closed INV`

**Actions:**

1. **Update Fields** — Set Changed to Deal Won Date if blank
   - `cf_potentials_changedtodealwon` → `get_date('today')`

---

### Populate Changed to Status Date

| Property | Value |
|----------|-------|
| **ID** | 172 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | multipath |
| **Status** | Enabled |
| **Description** | Populate changed to status date field when a deal changes status |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=172&mode=V7Edit) |

**Conditions:**

*All of:*
- `assigned_user_id` is not `46`
- `sales_stage` has changed

---

### Populate participating students from confirmed school deal

| Property | Value |
|----------|-------|
| **ID** | 190 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Populate participating students from confirmed school deal |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=190&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2026`
- `cf_potentials_numberofparticipants` is not empty
- `sales_stage` has changed to `Deal Won`
- `cf_potentials_orgtype` is `School - New,School - Existing`

**Actions:**

1. **Update Fields** — Update number of participants on org from confirmed deal
   - `(related_to : (Accounts) cf_accounts_participatingstudents)` → `cf_potentials_numberofparticipants`

---

### Send School Enquiry follow up email 1

| Property | Value |
|----------|-------|
| **ID** | 144 |
| **Module** | Deals |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send a follow up email to unanswered enquiry responses |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=144&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `New`
- `cf_potentials_orgtype` is `School - New`
- `potentialname` contains `2026`
- `closingdate` is yesterday

**Actions:**

1. **Send Email** — Send follow up email 1
   - **To:** `$(contact_id : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** The Resilience Project - Invitation to our 2026 School Wellbeing Program Info Session
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), I hope this email finds you well. Further to your recent enquiry, I wanted to follow-up my email about our School Wellbeing Program to see if your school would be interested in partnering with us in 2026? If you would like to learn more about our evidence-bas

---

### Send School Enquiry follow up email 2

| Property | Value |
|----------|-------|
| **ID** | 145 |
| **Module** | Deals |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send follow up email 2 to unanswered enquiry responses |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=145&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `New`
- `cf_potentials_orgtype` is `School - New`
- `potentialname` contains `2026`
- `closingdate` more than days ago `22`
- `closingdate` less than days ago `23`

**Actions:**

1. **Send Email** — Send follow up email 2
   - **To:** `$(contact_id : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** The Resilience Project - Learn more about our 2025 School Wellbeing Program
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), I hope you&#39;re well. I wanted to reach out one last time to see if your school would be interested in running our School Wellbeing Program in 2026. If you would like to learn more about our evidence-based, whole-school approach towards building resilience 

---

### Send to contact email after deal creation: Welcome to The Resilience Project’s 2024 Partnership Prog

| Property | Value |
|----------|-------|
| **ID** | 93 |
| **Module** | Deals |
| **Trigger** | Deals creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send to contact an email |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=93&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `WEBSERVICE`
- `potentialname` contains `2024 School Partnership Program`
- `cf_potentials_formlink` contains `new-schools-confirmation-2024`

---

### Send Workplace Enquiry follow up email 1

| Property | Value |
|----------|-------|
| **ID** | 135 |
| **Module** | Deals |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send a follow up email to unanswered enquiry responses |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=135&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `New`
- `cf_potentials_orgtype` is `Workplace - New`
- `closingdate` is yesterday

**Actions:**

1. **Send Email** — Send follow up email 1
   - **To:** `$(contact_id : (Contacts) email)`
   - **From:** `enquiries@theresilienceproject.com.au`
   - **Subject:** The Resilience Project — Following up on your Workplace Wellbeing enquiry
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), I’m just following up on your recent webform enquiry to see whether you’d like to proceed or learn more about our Workplace Wellbeing Programs. We recommend a short meeting so we can better understand your organisation and tailor a solution to align with your

---

### Send Workplace Enquiry follow up email 2

| Property | Value |
|----------|-------|
| **ID** | 138 |
| **Module** | Deals |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send follow up email 2 to unanswered enquiry responses |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=138&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `New`
- `cf_potentials_orgtype` is `Workplace - New`
- `closingdate` more than days ago `15`
- `closingdate` less than days ago `16`

**Actions:**

1. **Send Email** — Send follow up email 2
   - **To:** `$(contact_id : (Contacts) email)`
   - **From:** `enquiries@theresilienceproject.com.au`
   - **Subject:** TRP Update Request — are you still exploring options?
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), We just wanted to check in to see whether your organisation is still interested in running one of our Workplace Wellbeing Programs to support your team. If you’d like to explore this further, please feel free to book a short meeting via the link here, or simp

---

### Set Org 2024 Conf status for new WP deal

| Property | Value |
|----------|-------|
| **ID** | 134 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Set Org's 2024 confirmation status if 2024 confirmation status is blank and linked deal status is new |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=134&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2024`
- `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` is empty ``
- `sales_stage` is `New`
*All of:*
- `opportunity_type` is `Workplace`
- `cf_potentials_orgtype` is `Workplace - New`

**Actions:**

1. **Update Fields** — Set empty 2024 confirmation status to new prospect
   - `(related_to : (Accounts) cf_accounts_2024confirmationstatus)` → `New Prospect`

---

### Set organisation confirmed date

| Property | Value |
|----------|-------|
| **ID** | 169 |
| **Module** | Deals |
| **Trigger** | Only first time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Set confirmed date |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=169&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `Deal Won`
*All of:*
- `potentialname` contains `2025`
- `potentialname` contains `2026`

**Actions:**

1. **Update Fields** — Set confirmed date
   - `(related_to : (Accounts) cf_accounts_2025confirmationdate)` → `get_date('today')`

---

### Task to Schedule staff and parent presentations with Hugh

| Property | Value |
|----------|-------|
| **ID** | 74 |
| **Module** | Deals |
| **Trigger** | Deals creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Create a Task to Schedule staff and parent presentations with Hugh for Sarah. |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=74&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_potentials_presentations` is `Hugh F2F`

**Actions:**

1. **Create To-do** — Create Task

---

### Task to schedule staff and parent webinar with Hugh

| Property | Value |
|----------|-------|
| **ID** | 75 |
| **Module** | Deals |
| **Trigger** | Deals creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Task to schedule staff and parent webinar with Hugh |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=75&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_potentials_presentations` is `Hugh F2F`

**Actions:**

1. **Create To-do** — Create Staff and Parent Presentation with Hugh

---

### Update organisation Inspire field

| Property | Value |
|----------|-------|
| **ID** | 114 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Update organisation's 2026 Inspire field to reflect deal inspire field (for increased SPM visibility and TRP Hub export) |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=114&mode=V7Edit) |

**Conditions:**

*All of:*
- `potentialname` contains `2026`
*All of:*
- `cf_potentials_presentations` is `Inspire 1`
- `cf_potentials_presentations` is `Inspire 2`
- `cf_potentials_presentations` is `Inspire 3`
- `cf_potentials_presentations` is `Inspire 4`

**Actions:**

1. **Update Fields** — Update organisation Inspire field
   - `(related_to : (Accounts) cf_accounts_2026inspire)` → `cf_potentials_presentations`

---

### Workplace Booking Request - Automated Email

| Property | Value |
|----------|-------|
| **ID** | 100 |
| **Module** | Deals |
| **Trigger** | Deals creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Automated email sent to customers after they submit a Booking Request for wellbeing or connected parenting workshops |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=100&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` is `Ready to close`
- `cf_potentials_orgtype` is `Workplace - New`
- `source` is `WEBSERVICE`

**Actions:**

1. **Send Email** — Client email for booking request
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `enquiries@theresilienceproject.com.au`
   - **Subject:** The Resilience Project - Thank you for your booking request
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you so much for your interest in running a Resilience Project workshop at your organisation! As availability is limited, we are in the process of reviewing your request and will be back in touch as soon as possible to confirm. Warmest regards, Laura Lau

2. **Send Email** — Internal Email Notification
   - **To:** `enquiries@theresilienceproject.com.au,victor@theresilienceproject.com.au,kim@theresilienceproject.com.au,maddie@theresilienceproject.com.au`
   - **From:** `kim@theresilienceproject.com.au`
   - **Subject:** New workplace booking request!
   - **Body excerpt:** A workplace has submitted a workshop request! Please check on availability and email the client to confirm. Organisation Name: $related_to Contact Name: $contact_id Deal Stage: $sales_stage Extend: $cf_potentials_additionaloptions CRM Detail View URL

---

### Workplace deal won notification

| Property | Value |
|----------|-------|
| **ID** | 101 |
| **Module** | Deals |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Notify Workplace team of confirmed deal |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=101&mode=V7Edit) |

**Conditions:**

*All of:*
- `sales_stage` has changed to `Deal Won`
- `cf_potentials_orgtype` is `Workplace - New,Workplace - Existing`

**Actions:**

1. **Send Email** — Internal Email Notification
   - **To:** `laura.ng@theresilienceproject.com.au,victor@theresilienceproject.com.au,ryan.mcmurray@theresilienceproject.com.au,brendan.close@theresilienceproject.com.au,maddie@theresilienceproject.com.au`
   - **Subject:** A $cf_potentials_orgtype has confirmed! 
   - **Body excerpt:** A workplace has confirmed! Organisation Name: $related_to Org Type: $cf_potentials_orgtype S&D Member: $assigned_user_id Contact Name: $contact_id Purchased: $(productid : (Services) servicename) Number of Participants: $cf_potentials_numberofparticipants Preferred Program Start Date: $cf_potentials

---

## Enquiries

### New enquiry - send email to assignee

| Property | Value |
|----------|-------|
| **ID** | 128 |
| **Module** | Enquiries |
| **Trigger** | Enquiries creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email to the assignee when a new enquiry is created |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=128&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmenquiries_enquiryassignedto` is not `22`

**Actions:**

1. **Send Email** — Send email to assignee
   - **To:** `$(cf_vtcmenquiries_enquiryassignedto : (Users) email1)`
   - **From:** `aretha@theresilienceproject.com.au`
   - **Subject:** VTiger | New Enquiry ($fld_vtcmenquiriesname)
   - **Body excerpt:** Hello $(cf_vtcmenquiries_enquiryassignedto : (Users) first_name), A new enquiry has been submitted and assigned to you. To view the enquiry, please click here CRM Detail View URL Remember to update the status and add comments to keep track of the enquiry&#39;s progress. Note for SPMs: You have been 

---

### New enquiry - send email to enquirer

| Property | Value |
|----------|-------|
| **ID** | 127 |
| **Module** | Enquiries |
| **Trigger** | Enquiries creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email to the enquirer when a new enquiry is created |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=127&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmenquiries_enquirybody` does not contain `Participated in the 21 day journal challenge.`

**Actions:**

1. **Send Email** — Send new enquiry email to enquirer
   - **To:** `$(cf_vtcmenquiries_contact : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** TRP | Thanks for your enquiry
   - **Body excerpt:** Hi $(cf_vtcmenquiries_contact : (Contacts) firstname), Thanks for your enquiry. Our team have received it and will get back to you shortly. Kindest regards, The Resilience Project Team theresilienceproject.com.au

---

### New enquiry - send email to new assignee

| Property | Value |
|----------|-------|
| **ID** | 139 |
| **Module** | Enquiries |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email to the new assignee when an enquiry is reassigned |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=139&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmenquiries_enquiryassignedto` is not `22`
- `cf_vtcmenquiries_enquiryassignedto` has changed

**Actions:**

1. **Send Email** — Send email to new assignee
   - **To:** `$(cf_vtcmenquiries_enquiryassignedto : (Users) email1)`
   - **From:** `aretha@theresilienceproject.com.au`
   - **Subject:** VTiger | Reassigned Enquiry ($fld_vtcmenquiriesname)
   - **Body excerpt:** Hello $(cf_vtcmenquiries_enquiryassignedto : (Users) first_name), An enquiry has been reassigned to you. To view the enquiry, please click here CRM Detail View URL Remember to update the status and add comments to keep track of the enquiry&#39;s progress. --------------------------------------------

---

## Events

### 58619- Send Email - Information Session Form

| Property | Value |
|----------|-------|
| **ID** | 91 |
| **Module** | Events |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | 58619- Send Email - Information Session Form |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=91&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_events_workflowname` is `Send Email - Information Session Form`
- `(contact_id : (Contacts) cf_contacts_eyinformationsessiondate)` is not empty
- `cf_events_presentationworkshoptype` is `Information Session`

**Actions:**

1. **Send Email** — Send Email - Information Session Form
   - **To:** `,$(contact_id : (Contacts) email)`
   - **Subject:** TRP | Link to Join Information Session
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you so much for your interest in The Resilience Project&#39;s 2024 Early Years Program and for registering to attend the Information Session on $cf_events_shorteventname. Please click on the following link to join the session: $cf_events_zoomlink Please

---

### Event - Embedding TRP

| Property | Value |
|----------|-------|
| **ID** | 104 |
| **Module** | Events |
| **Trigger** | Events creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Event - Embedding TRP |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=104&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` contains `AP - [EMBEDDING TRP`
- `subject` contains `Embedding TRP`

**Actions:**

1. **Update Fields** — Update &quot;Embedding TRP&quot; Presentation Type
   - `cf_events_presentationworkshoptype` → `Embedding TRP`

2. **Update Fields** — Add Organisation
   - `account_id` → `(contact_id : (Contacts) account_id)`

---

### Event - Leading TRP

| Property | Value |
|----------|-------|
| **ID** | 108 |
| **Module** | Events |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Event - Leading TRP |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=108&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_events_workflowname` is `Leading TRP`

**Actions:**

1. **Update Fields** — Update Org&#039;s Leading TRP field
   - `(account_id : (Accounts) cf_accounts_leadingtrp)` → `date_start`

---

### Event - Program Planning

| Property | Value |
|----------|-------|
| **ID** | 105 |
| **Module** | Events |
| **Trigger** | Events creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Event - Program Planning |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=105&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `AP - [PROGRAM PLANNING]`
- `subject` contains `Program Planning`

**Actions:**

1. **Update Fields** — Update &quot;Retention Meeting&quot; Presentation Type
   - `cf_events_presentationworkshoptype` → `Retention Meeting`

2. **Update Fields** — Add Organisation
   - `account_id` → `(contact_id : (Contacts) account_id)`

---

### Event - Resilient Youth Survey

| Property | Value |
|----------|-------|
| **ID** | 103 |
| **Module** | Events |
| **Trigger** | Events creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Event - Resilient Youth Survey |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=103&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `AP - [RESILIENT YOUTH SURVEY]`
- `subject` contains `Resilient Youth Survey`

**Actions:**

1. **Update Fields** — Update &quot;RY Survey Meeting&quot; Presentation Type
   - `cf_events_presentationworkshoptype` → `RY Survey Meeting`

2. **Update Fields** — Add Organisation
   - `account_id` → `(contact_id : (Contacts) account_id)`

3. **Update Fields** — Update Organisation&#039;s &#039;RY Meeting&#039; field
   - `(account_id : (Accounts) cf_accounts_rymeeting)` → `date_start`

---

### Event - Teaching TRP

| Property | Value |
|----------|-------|
| **ID** | 106 |
| **Module** | Events |
| **Trigger** | Events creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Event - Teaching TRP |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=106&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `AP - [TEACHING TRP]`
- `subject` contains `Teaching TRP`

**Actions:**

1. **Update Fields** — Update &quot;Teaching TRP&quot; Presentation Type
   - `cf_events_presentationworkshoptype` → `Teaching TRP`

2. **Update Fields** — Add Organisation
   - `account_id` → `(contact_id : (Contacts) account_id)`

3. **Update Fields** — Update Organisation&#039;s &#039;Teaching TRP&#039; field
   - `(account_id : (Accounts) cf_accounts_teachingtrp)` → `date_start`

---

### Event - Welcome Meeting

| Property | Value |
|----------|-------|
| **ID** | 102 |
| **Module** | Events |
| **Trigger** | Events creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Event - Welcome Meeting |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=102&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` contains `WELCOME MEETING`
- `subject` contains `Welcome Meeting`

**Actions:**

1. **Update Fields** — Update &quot;Welcome Meeting&quot; Presentation Type
   - `cf_events_presentationworkshoptype` → `Welcome Meeting`

2. **Update Fields** — Add Organisation
   - `account_id` → `(contact_id : (Contacts) account_id)`

3. **Update Fields** — Update Organisation&#039;s &#039;Welcome Meeting&#039; field
   - `(account_id : (Accounts) cf_accounts_welcomemeeting)` → `date_start`

---

### Filter out Staff Calendar Items - TESTING INACTIVE

| Property | Value |
|----------|-------|
| **ID** | 86 |
| **Module** | Events |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Modifies the events from Staff members syncing their calendars to not interfere with |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=86&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `GOOGLE`

**Actions:**

1. **Update Fields** — Add values to ignore staff events from Google calendars
   - `cf_events_presentationworkshoptype` → `Staff Personal Calendar Event`
   - `eventstatus` → `Skipped`

---

### Update Meeting Notes Field (remove Agenda afterwards)

| Property | Value |
|----------|-------|
| **ID** | 41 |
| **Module** | Events |
| **Trigger** | Time Interval (Hourly) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update Agenda -> Meeting Notes |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=41&mode=V7Edit) |

**Conditions:**

*All of:*
- `description` is not empty
- `meeting_notes` is not empty
- `cf_events_recordid` is `Workflow 54287`

**Actions:**

1. **Update Fields** — Delete Meeting Notes after moving to Agenda field.
   - `meeting_notes` → ``

---

## Invoice

### 58850 - Update Xero Code Invoice Item

| Property | Value |
|----------|-------|
| **ID** | 112 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | 58850 - Update Xero Code Invoice Item |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=112&mode=V7Edit) |

**Conditions:**

```
All :   -NA-
Any : -NA-
```

**Actions:**

1. **Webhook** — Update Xero Code
   - **URL:** `https://theresilienceproject.com.au/resilience/Invoices/58850_updateXeroCodeInvoiceItem.php`
   - **Method:** POST
   - **Parameters:**
     - `invoice_id` ← `invoice_no`

---

### Accounts: Update deal to Closed INV when inv is imported to Xero

| Property | Value |
|----------|-------|
| **ID** | 115 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | When invoice status is changed to 'imported to Xero', related deal sales stage is updated to Closed INV |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=115&mode=V7Edit) |

**Conditions:**

*All of:*
- `invoicestatus` is `Imported to Xero`

**Actions:**

1. **Update Fields** — Update Deal to Closed INV
   - `(potential_id : (Potentials) sales_stage)` → `Closed INV`

---

### Create new school preview subscription in hub

| Property | Value |
|----------|-------|
| **ID** | 162 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Create 2025 new school preview hub subscription and update hub access date |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=162&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` is `2026 School Partnership Program`
- `invoicestatus` has changed to `Hub Subscription Created`
- `(potential_id : (Potentials) cf_potentials_orgtype)` is `School - New`
- `(account_id : (Accounts) cf_3502)` is empty
- `cf_invoice_ambassadoremail` is not empty
- `ship_code` is not empty

**Actions:**

1. **Webhook** — Create preview subscription in Hub
   - **URL:** `https://lh9sixjcw4.execute-api.ap-southeast-2.amazonaws.com/prod/schools/create`
   - **Method:** POST
   - **Parameters:**
     - `VTigerID` ← `(account_id : (Accounts) account_no)`
     - `name` ← `(account_id : (Accounts) accountname)`
     - `schoolType` ← `(account_id : (Accounts) cf_accounts_schoolyearlevels)`
     - `state` ← `ship_state`
     - `postcode` ← `ship_code`
     - `accountUserEmail` ← `cf_invoice_ambassadoremail`
     - `dealOrgType` ← `(potential_id : (Potentials) cf_potentials_orgtype)`
     - `courseCodes` ← `replace(cf_invoice_inspireteacherparenthubcourses,' |##| ',',')`
     - `subscriptionStart` ← `get_date('yesterday')`
     - `subscriptionEnd` ← `2025-12-31`

2. **Update Fields** — Set hub preview access date
   - `(account_id : (Accounts) cf_3502)` → `get_date('today')`

---

### Create Order in Ship Station 2025

| Property | Value |
|----------|-------|
| **ID** | 155 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Create an order in SS when a 2025 School or Workplace Invoice has been approved, update Invoice Approved Date |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=155&mode=V7Edit) |

**Conditions:**

*All of:*
- `invoicestatus` has changed to `Approved`
*All of:*
- `subject` contains `School Partnership Program`
- `subject` contains `Workplace`

**Actions:**

1. **Webhook** — Create 2025 Shipment
   - **URL:** `https://theresilienceproject.com.au/resilience/Invoices/create_shipment_2025.php`
   - **Method:** POST
   - **Parameters:**
     - `invoice_no` ← `invoice_no`
     - `recordid` ← `id`

2. **Update Fields** — Set Invoice Approved Date
   - `cf_invoice_invoiceapproved` → `get_date('today')`

---

### Create Shipment For Invoice Approved

| Property | Value |
|----------|-------|
| **ID** | 113 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Create Shipment For Invoice Approved |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=113&mode=V7Edit) |

**Conditions:**

*All of:*
- `invoicestatus` has changed to `Approved`
- `subject` is `2024 School Partnership Program`

**Actions:**

1. **Webhook** — Create Shipment
   - **URL:** `https://trpstaging.dev/resilience/Invoices/createShipment.php`
   - **Method:** POST
   - **Parameters:**
     - `invoice_id` ← `invoice_no`

---

### Create Workplace Hub Subscriptions

| Property | Value |
|----------|-------|
| **ID** | 206 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Creates subscriptions to OLS and DP in the hub for workplaces |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=206&mode=V7Edit) |

**Conditions:**

*All of:*
- `invoicestatus` is `Hub Subscription Created`
- `subject` is `2026 Workplace Partner`
- `(potential_id : (Potentials) cf_potentials_numberofparticipants)` is not empty
- `contact_id` is not empty
*All of:*
- `cf_invoice_resiliencethroughchangeambassadoremail` is not empty
- `cf_invoice_supportingteamwellbeingambassadoremail` is not empty
- `cf_4177` is not empty
- `cf_4181` is not empty
- `cf_4187` is not empty
- `cf_invoice_discoveringresiliencemhambassadoremail` is not empty

**Actions:**

1. **Webhook** — Create subscription in Hub
   - **URL:** `https://0huqyxwpyb.execute-api.ap-southeast-2.amazonaws.com/prod/subscriptions`
   - **Method:** POST

2. **Update Fields** — Set Hub subscription created data
   - `cf_invoice_hubsubscriptioncreatedon` → `get_date('today')`

---

### Create yearly subscription in Hub

| Property | Value |
|----------|-------|
| **ID** | 158 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Create 2026 hub subscription and update hub access date |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=158&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` is `2026 School Partnership Program`
- `invoicestatus` has changed to `Hub Subscription Created`
- `(account_id : (Accounts) cf_accounts_trphubambassadoraccess)` is empty
- `cf_invoice_ambassadoremail` is not empty
- `ship_code` is not empty

**Actions:**

1. **Webhook** — Create subscription in Hub
   - **URL:** `https://lh9sixjcw4.execute-api.ap-southeast-2.amazonaws.com/prod/schools/create`
   - **Method:** POST
   - **Parameters:**
     - `VTigerID` ← `(account_id : (Accounts) account_no)`
     - `name` ← `(account_id : (Accounts) accountname)`
     - `schoolType` ← `(account_id : (Accounts) cf_accounts_schoolyearlevels)`
     - `state` ← `ship_state`
     - `postcode` ← `ship_code`
     - `accountUserEmail` ← `cf_invoice_ambassadoremail`
     - `dealOrgType` ← `(potential_id : (Potentials) cf_potentials_orgtype)`
     - `courseCodes` ← `replace(replace(concat(concat(concat(concat(concat(cf_invoice_hubcourses,' |##| '), cf_invoice_inspirehubcourses), ' |##| '), cf_invoice_inspireteacherparenthubcourses), ' |##| '), ' |##| ',','),',,',',')`
     - `subscriptionStart` ← `2026-01-01`
     - `subscriptionEnd` ← `2026-12-31`

2. **Update Fields** — Set Hub access date
   - `(account_id : (Accounts) cf_accounts_trphubambassadoraccess)` → `get_date('today')`

---

### Notify of failed ship station integration

| Property | Value |
|----------|-------|
| **ID** | 200 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Notify of failed ship station integration |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=200&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_invoice_shipstationsuccess` has changed to `false`

**Actions:**

1. **Send Email** — Send email when ship station error
   - **To:** `dawn.maxa@theresilienceproject.com.au,$(assigned_user_id : (Users) email1)`
   - **Subject:** Failed Ship Station integration
   - **Body excerpt:** Hi Dawn, $(assigned_user_id : (Users) first_name), The following invoice for $account_id has not been imported to ShipStation. $cf_invoice_shipstationmessage CRM Detail View URL

---

### Notify of unconfirmed deal ordering curric

| Property | Value |
|----------|-------|
| **ID** | 154 |
| **Module** | Invoice |
| **Trigger** | Invoice creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email to notify Ed team of an unconfirmed school placing their curric order |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=154&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` is `2026 School Partnership Program`
- `source` is `WEBSERVICE`
- `(potential_id : (Potentials) sales_stage)` is not `Deal Won,Closed INV,Prepaid`

**Actions:**

1. **Send Email** — Send email notifying Ed team of curric ordered for unconfirmed school
   - **To:** `helenor@theresilienceproject.com.au,$(account_id : (Accounts) assigned_user_id : (Users) email1)`
   - **Subject:** Curriculum ordered for an unconfirmed school
   - **Body excerpt:** $account_id has placed a curriculum order but has not confirmed for 2026 Deal Status: $(potential_id : (Potentials) sales_stage) SPM: $(account_id : (Accounts) assigned_user_id) Please: 1. Contact the school 2. Complete the 2026 Existing School Confirmation Form 3. Re-order curriculum on their behal

2. **Update Fields** — Cancel Invoice
   - `invoicestatus` → `Cancelled`

---

### Send workplace program confirmation to EXISTING client

| Property | Value |
|----------|-------|
| **ID** | 179 |
| **Module** | Invoice |
| **Trigger** | Only first time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send workplace program confirmation to EXISTING client |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=179&mode=V7Edit) |

**Conditions:**

*All of:*
- `invoicestatus` has changed to `Approved`
- `subject` contains `Workplace`
- `(potential_id : (Potentials) cf_potentials_orgtype)` is `Workplace - Existing`

**Actions:**

1. **Send Email** — Send workplace program confirmation to EXISTING client
   - **To:** `$(contact_id : (Contacts) email)`
   - **From:** `ryan.mcmurray@theresilienceproject.com.au`
   - **Subject:** Welcome back to The Resilience Project's Workplace Wellbeing Program!
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you for continuing your workplace&#39;s involvement in The Resilience Project’s Workplace Wellbeing Program! We&#39;re thrilled to have the opportunity to partner with you again and support the wellbeing of your staff. Please find a program confirmation

---

### Send workplace program confirmation to NEW client

| Property | Value |
|----------|-------|
| **ID** | 178 |
| **Module** | Invoice |
| **Trigger** | Only first time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send workplace program confirmation to NEW client |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=178&mode=V7Edit) |

**Conditions:**

*All of:*
- `invoicestatus` has changed to `Approved`
- `subject` contains `Workplace`
- `(potential_id : (Potentials) cf_potentials_orgtype)` is `Workplace - New`

**Actions:**

1. **Send Email** — Send workplace confirmation to NEW client
   - **To:** `$(contact_id : (Contacts) email)`
   - **From:** `ryan.mcmurray@theresilienceproject.com.au`
   - **Subject:** Welcome to The Resilience Project's Workplace Wellbeing Program!
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname) My name is Ryan McMurray, and I am the Workplace Partnership Manager at The Resilience Project. I have the great pleasure of working with our wonderful partners once they have made the decision to use one of our services. I am looking forward to now being your

---

### Set Ambassador email

| Property | Value |
|----------|-------|
| **ID** | 163 |
| **Module** | Invoice |
| **Trigger** | Invoice creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Set ambassador email for hub access |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=163&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` contains `School Partnership Program`
- `invoicestatus` is `Auto Created`

**Actions:**

1. **Update Fields** — Set Ambassador email
   - `cf_invoice_ambassadoremail` → `(contact_id : (Contacts) email)`

---

### Set Curric ordered date

| Property | Value |
|----------|-------|
| **ID** | 157 |
| **Module** | Invoice |
| **Trigger** | Invoice creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Set the seip curric ordered date when curric is ordered |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=157&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` is `2026 School Partnership Program`
- `invoicestatus` is `Auto Created`

**Actions:**

1. **Update Fields** — Set Curric ordered date
   - `(cf_invoice_seip : (vtcmseip) cf_vtcmseip_curriculumordered)` → `get_date('today')`

---

### Set Due Date on Invoice creation

| Property | Value |
|----------|-------|
| **ID** | 97 |
| **Module** | Invoice |
| **Trigger** | Invoice creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Set Due Date on Invoice creation |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=97&mode=V7Edit) |

**Conditions:**

*All of:*
- `invoicestatus` is `Auto Created`
- `source` is `WEBSERVICE`
- `duedate` is empty

**Actions:**

1. **Update Fields** — Set Due Date on Invoice creation
   - `duedate` → `add_days(invoicedate, 14)`

---

### UpdateInventoryProducts On Every Save

| Property | Value |
|----------|-------|
| **ID** | 1 |
| **Module** | Invoice |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | UpdateInventoryProducts On Every Save |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=1&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` does not contain ``!``

---

### Workplace Booking Confirmation Email

| Property | Value |
|----------|-------|
| **ID** | 88 |
| **Module** | Invoice |
| **Trigger** | Invoice creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Booking Form |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=88&mode=V7Edit) |

**Conditions:**

*All of:*
- `(potential_id : (Potentials) sales_stage)` is `Closed INV`
- `invoicestatus` is `Auto Created`
- `cf_invoice_formlink` contains `/book-form/`

**Actions:**

1. **Send Email** — Workplace Booking Confirmation Email
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `enquiries@theresilienceproject.com.au`
   - **Subject:** The Resilience Project - Program Confirmation and Invoice
   - **Body excerpt:** Dear $(contact_id : (Contacts) firstname) Thank you so much for your booking with The Resilience Project - we are thrilled to have the opportunity to partner with you and support your staff wellbeing! An invoice for your program selection is attached and we will be back in touch shortly with your pr

---

## Organisations

### Assigned SPM updated

| Property | Value |
|----------|-------|
| **ID** | 107 |
| **Module** | Organisations |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Email notification when an SPM is assigned to a new school record |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=107&mode=V7Edit) |

**Conditions:**

*All of:*
- `assigned_user_id` has changed
*All of:*
- `assigned_user_id` is `9`
- `assigned_user_id` is `14`
- `assigned_user_id` is `16`
- `assigned_user_id` is `23`
- `assigned_user_id` is `34`
- `assigned_user_id` is `35`
- `assigned_user_id` is `36`

**Actions:**

1. **Send Email** — Assigned SPM has changed
   - **To:** `,$(assigned_user_id : (Users) email1)`
   - **CC:** `maddie@theresilienceproject.com.au`
   - **From:** `helenor@theresilienceproject.com.au`
   - **Subject:** A new school has been assigned to you
   - **Body excerpt:** $accountname has been assigned to you. Please send a welcome email to the primary/ambassador contact. CRM Detail View URL

---

## Quotes

### 58619 - Send Email To Contact - 2024 Early Years Program

| Property | Value |
|----------|-------|
| **ID** | 110 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | 58619 - Send Email To Contact - 2024 Early Years Program |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=110&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` is `2024 Early Years Program`
- `source` is `WEBSERVICE`
- `(potential_id : (Potentials) potentialname)` is `2024 Early Years Program`
- `quotestage` is `Delivered`

**Actions:**

1. **Send Email** — Send Email To Contact - 2024 Early Years Program
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `earlyyears@theresilienceproject.com.au`
   - **Subject:** The Resilience Project - 2024 Program Quote
   - **Body excerpt:** Dear $(contact_id : (Contacts) firstname), Thank you for confirming your service&#39;s intention to participate in The Resilience Project&#39;s 2024 Early Years Wellbeing Program. We are thrilled to have the opportunity to partner with you and support the wellbeing of your children, educators and pa

---

### Existing School - Send Mail on Deal Creation with Number Of Students greater than or equal to 200

| Property | Value |
|----------|-------|
| **ID** | 72 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Existing School - Send Mail on Deal Creation with Number Of Students greater than or equal to 200 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=72&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `WEBSERVICE`
- `(potential_id : (Potentials) cf_potentials_numberofparticipants)` greater than or equal to `200`
- `(potential_id : (Potentials) cf_potentials_formlink)` does not contain `new-schools-confirmation-2024`
- `(potential_id : (Potentials) potentialname)` is not `2024 School Partnership Program`
*All of:*
- `(contact_id : (Contacts) cf_contacts_confirmationform)` is `Existing School (F2F/Digital)`
- `(contact_id : (Contacts) cf_contacts_confirmationform)` is `Existing School (Digital Only)`

**Actions:**

1. **Send Email** — Existing School - Send Mail on Deal Creation with Number Of Students greater than or equal to 200
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `education@theresilienceproject.com.au`
   - **Subject:** Welcome to The Resilience Project’s 2024 Partnership Program!
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you for confirming your school’s involvement in The Resilience Project’s Partnership Program in 2024. We are thrilled to have the opportunity to continue to support the wellbeing of your whole school community. We will be in touch shortly to outline nex

---

### Existing School Confirmation 2026 - automated quote email

| Property | Value |
|----------|-------|
| **ID** | 148 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | multipath |
| **Status** | Enabled |
| **Description** | Send quote to contact if stage is delivered or notification to ed team to review held quotes |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=148&mode=V7Edit) |

**Conditions:**

*All of:*
- `potential_id` is not empty
- `subject` is `2026 School Partnership Program`
- `source` is `WEBSERVICE`
- `cf_quotes_type` is `School - Existing`

---

### New school - automated quote email

| Property | Value |
|----------|-------|
| **ID** | 96 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send to contact email |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=96&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `WEBSERVICE`
- `cf_quotes_formlink` contains `new-schools-confirmation-2024`
- `potential_id` is not empty
- `(potential_id : (Potentials) cf_potentials_numberofparticipants)` greater than or equal to `200`
- `subject` is `2024 School Partnership Program`

**Actions:**

1. **Send Email** — Send Mail on Quote Creation -  2024 New Schools Confirmation
   - **To:** `$cf_quotes_contactemail`
   - **From:** `education@theresilienceproject.com.au`
   - **Subject:** Welcome to The Resilience Project’s 2025 School Wellbeing Program!
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you for confirming your school’s involvement in The Resilience Project’s 2025 School Wellbeing Program! We are thrilled to have the opportunity to partner with you and support the wellbeing of your whole school community. Your dedicated School Partnersh

---

### New School - Send Mail on Deal Creation with Number Of Students greater than or equal to 200

| Property | Value |
|----------|-------|
| **ID** | 70 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | New School - Send Mail on Deal Creation with Number Of Students greater than or equal to 200 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=70&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `WEBSERVICE`
- `(potential_id : (Potentials) cf_potentials_numberofparticipants)` greater than or equal to `200`
- `(contact_id : (Contacts) cf_contacts_confirmationform)` is `New School`
- `(potential_id : (Potentials) cf_potentials_formlink)` does not contain `new-schools-confirmation-2024`

**Actions:**

1. **Send Email** — New School - Send Mail on Deal Creation with Number Of Students greater than or equal to 200
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `education@theresilienceproject.com.au`
   - **Subject:** Welcome to The Resilience Project’s 2023 Partnership Program!
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you for confirming your school’s involvement in The Resilience Project’s Partnership Program in 2023. We are thrilled to have the opportunity to partner with you and support the wellbeing of your whole school community. Your dedicated School Partnership

---

### New School Confirmation 2026 - automated quote email

| Property | Value |
|----------|-------|
| **ID** | 137 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send quote to contact |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=137&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` is `2026 School Partnership Program`
- `source` is `WEBSERVICE`
- `cf_quotes_type` is `School - New`
- `potential_id` is not empty

**Actions:**

1. **Send Email** — Send program quote to new school
   - **To:** `$(contact_id : (Contacts) email)`
   - **From:** `education@theresilienceproject.com.au`
   - **Subject:** Welcome to The Resilience Project’s 2026 School Wellbeing Program!
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you for confirming your school’s involvement in The Resilience Project’s 2026 School Wellbeing Program! We&#39;re thrilled to have the opportunity to partner with you and support the wellbeing of your school community. Please find a program quote attach

---

### Send Mail on Quote Creation

| Property | Value |
|----------|-------|
| **ID** | 40 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send Mail on Quote Creation |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=40&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `WEBSERVICE`

**Actions:**

1. **Send Email** — Send Mail On Quote Created
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `education@theresilienceproject.com.au`
   - **Subject:** Welcome to The Resilience Project’s 2023 Partnership Program!
   - **Body excerpt:** Hello $(contact_id : (Contacts) firstname) Thank you for confirming your school’s involvement in The Resilience Project’s Partnership Program in 2022. We are thrilled to have the opportunity to partner with you and support the wellbeing of your whole school community. Your dedicated School Partnersh

---

### Send Mail on Quote Creation -  2024 Existing Confirmation Form

| Property | Value |
|----------|-------|
| **ID** | 98 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send Mail on Quote Creation - 2024 Existing Confirmation Form |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=98&mode=V7Edit) |

**Conditions:**

*All of:*
- `subject` is `2024 School Partnership Program`
- `source` is `WEBSERVICE`
- `(potential_id : (Potentials) potentialname)` is `2024 School Partnership Program`
- `(potential_id : (Potentials) cf_potentials_schoolmentalhealthfunding)` is not `Yes`
- `(potential_id : (Potentials) cf_potentials_numberofparticipants)` greater than or equal to `200`
- `quotestage` is `Delivered`
- `cf_quotes_formlink` does not contain `new-schools-confirmation-2024`

**Actions:**

1. **Send Email** — Send Mail on Quote Creation -  2024 Existing Confirmation Form
   - **To:** `,$cf_quotes_contactemail`
   - **From:** `education@theresilienceproject.com.au`
   - **Subject:** Quote for your 2024 TRP Partnership Program
   - **Body excerpt:** Hi $(contact_id : (Contacts) firstname), Thank you for confirming your school’s involvement in The Resilience Project’s Partnership Program in 2024. We are thrilled to have the opportunity to continue to support the wellbeing of your school community. Please find a program quote attached for your re

---

### Update Quote Address

| Property | Value |
|----------|-------|
| **ID** | 76 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update Quote Address |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=76&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `WEBSERVICE`
- `potential_id` is not empty

**Actions:**

1. **Update Fields** — Update Quote Address
   - `bill_street` → `(potential_id : (Potentials) cf_potentials_schooladdress)`
   - `bill_city` → `(potential_id : (Potentials) cf_potentials_schoolcity)`
   - `bill_state` → `(potential_id : (Potentials) cf_potentials_state)`
   - `bill_code` → `(potential_id : (Potentials) cf_potentials_schoolpostcode)`

---

### Update Resource order URL

| Property | Value |
|----------|-------|
| **ID** | 117 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Resource order URL |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=117&mode=V7Edit) |

**Conditions:**

```
All :   -NA-
Any : -NA-
```

**Actions:**

1. **Update Fields** — Resource order URL
   - `cf_quotes_resourceorderurl` → `concat('https://forms.theresilienceproject.com.au/engage-teaching-and-learning-program?quote_id=',quote_no)`

---

### Update Resource order URL for old quotes

| Property | Value |
|----------|-------|
| **ID** | 120 |
| **Module** | Quotes |
| **Trigger** | Time Interval (Hourly) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Update Resource order URL for old quotes |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=120&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_quotes_resourceorderurl` is empty

**Actions:**

1. **Update Fields** — Resource order URL
   - `cf_quotes_resourceorderurl` → `concat('https://forms.theresilienceproject.com.au/engage-teaching-and-learning-program?quote_id=',quote_no)`

---

### Workplace Booking Request Email

| Property | Value |
|----------|-------|
| **ID** | 89 |
| **Module** | Quotes |
| **Trigger** | Quotes creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Workplace Booking Request Email |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=89&mode=V7Edit) |

**Conditions:**

*All of:*
- `quotestage` is `New`
- `potential_id` is not empty
- `cf_quotes_formlink` contains `/book-form/`
- `source` is `WEBSERVICE`

**Actions:**

1. **Send Email** — Workplace Booking Request Email
   - **To:** `,$(contact_id : (Contacts) email)`
   - **From:** `enquiries@theresilienceproject.com.au`
   - **Subject:** The Resilience Project - Thank you for your booking request
   - **Body excerpt:** Dear $(contact_id : (Contacts) firstname), Thank you so much for your interest in running a Resilience Project workshop at your organisation! As availability is limited, we are in the process of reviewing your request and will be back in touch as soon as possible to confirm. In the meantime, please 

---

## Registration

### Event Confirmation Registration Emails

| Property | Value |
|----------|-------|
| **ID** | 166 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | multipath |
| **Status** | Enabled |
| **Description** | Send an email to anyone who confirms they are attending an extend event |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=166&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Event Confirmation`

---

### EY Info Session Download - QLD

| Property | Value |
|----------|-------|
| **ID** | 174 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email with a link to the recording |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=174&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `source` is `EY INFO SESSION DOWNLOAD`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_state)` is `QLD`

**Actions:**

1. **Send Email** — Send email with link to info session recording
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `The Resilience Project<brendan.close@theresilienceproject.com.au>`
   - **Subject:** Thanks for your interest in our Early Years Wellbeing Program! 
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thanks so much for your interest in our 2026 Early Years Wellbeing Program! Here’s the link to more information about the program, including an info session and samples of our session plans, community projects, home-to-service initiat

---

### EY Info Session Download - ROA

| Property | Value |
|----------|-------|
| **ID** | 196 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email with a link to the recording |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=196&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `source` is `EY INFO SESSION DOWNLOAD`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_state)` is not `VIC,QLD`

**Actions:**

1. **Send Email** — Send email with link to info session recording
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `The Resilience Project<brendan.close@theresilienceproject.com.au>`
   - **Subject:** Thanks for your interest in our Early Years Wellbeing Program! 
   - **Body excerpt:** ​Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thanks so much for your interest in our 2026 Early Years Wellbeing Program! Here’s the link to more information about the program, including an info session and samples of our session plans, community projects, home-to-service initia

---

### EY Info Session Download - VIC

| Property | Value |
|----------|-------|
| **ID** | 195 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email with a link to the recording |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=195&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `source` is `EY INFO SESSION DOWNLOAD`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_state)` is `VIC`

**Actions:**

1. **Send Email** — Send email with link to info session recording
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `The Resilience Project<brendan.close@theresilienceproject.com.au>`
   - **Subject:** Thanks for your interest in our Early Years Wellbeing Program! 
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thanks so much for your interest in our 2026 Early Years Wellbeing Program! Here’s the link to more information about the program, including an info session and samples of our session plans, community projects, home-to-service initiat

---

### EY Info Session Registration Emails

| Property | Value |
|----------|-------|
| **ID** | 160 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email to EY info session registrant |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=160&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `EY Info Session Registration`

**Actions:**

1. **Send Email** — Send EY info session registration email
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Thanks for registering for our live info session!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thank you so much for your interest in our 2026 Early Years Wellbeing Program. We look forward to seeing you at our live information session on $cf_vtcmregistration_eventshortname All of our events are displayed in AEST, so please not

---

### Leading TRP Registration email NSW

| Property | Value |
|----------|-------|
| **ID** | 150 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send an email to a anyone who registers for leading trp |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=150&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `Leading TRP Registration`
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `(cf_vtcmregistration_eventregistrant : (Contacts) cf_contacts_statenew)` is `NSW`

**Actions:**

1. **Send Email** — Send Leading TRP registration email NSW
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Thanks for registering for Leading TRP!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thanks for registering for an upcoming Leading TRP session. Here are the details: LEADING TRP Date and Time: $cf_vtcmregistration_eventshortname Zoom link: $cf_vtcmregistration_eventzoomlink Please note the time difference if you are 

---

### Leading TRP Registration email QLD

| Property | Value |
|----------|-------|
| **ID** | 152 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send an email to a anyone who registers for leading trp |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=152&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `Leading TRP Registration`
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `(cf_vtcmregistration_eventregistrant : (Contacts) cf_contacts_statenew)` is `QLD`

**Actions:**

1. **Send Email** — Send Leading TRP registration email QLD
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Thanks for registering for Leading TRP!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thanks for registering for an upcoming Leading TRP session. Here are the details: LEADING TRP Date and Time: $cf_vtcmregistration_eventshortname Zoom link: $cf_vtcmregistration_eventzoomlink Please note the time difference if you are 

---

### Leading TRP Registration email ROA

| Property | Value |
|----------|-------|
| **ID** | 153 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send an email to a anyone who registers for leading trp |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=153&mode=V7Edit) |

**Conditions:**

*All of:*
- `source` is `Leading TRP Registration`
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
*All of:*
- `(cf_vtcmregistration_eventregistrant : (Contacts) cf_contacts_statenew)` is not `QLD`
- `(cf_vtcmregistration_eventregistrant : (Contacts) cf_contacts_statenew)` is not `NSW`

**Actions:**

1. **Send Email** — Send Leading TRP registration email ROA
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Thanks for registering for Leading TRP!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thanks for registering for an upcoming Leading TRP session. Here are the details: LEADING TRP Date and Time: $cf_vtcmregistration_eventshortname Zoom link: $cf_vtcmregistration_eventzoomlink Please note the time difference if you are 

---

### School Prog Info Session Recording Email -350

| Property | Value |
|----------|-------|
| **ID** | 177 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send info session recording email to -350 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=177&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Info Session Recording`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_numberofparticipants)` less than or equal to `350`

**Actions:**

1. **Send Email** — Send school info session recording email -350
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Thanks for your interest in our School Wellbeing Program!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thanks so much for your interest in our School Wellbeing Program. Here’s the link to more information about the program and its impact. Registrations for 2026 have now closed. If you&#39;re interested in exploring the program for 2027

---

### School Prog Info Session Recording Email +351

| Property | Value |
|----------|-------|
| **ID** | 182 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send info session recording email to +351 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=182&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Info Session Recording`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_numberofparticipants)` greater than or equal to `351`

**Actions:**

1. **Send Email** — Send school info session recording email +351
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Thanks for your interest in our School Wellbeing Program!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thanks so much for your interest in our School Wellbeing Program. Here’s the link to more information about the program and its impact. Registrations for 2026 have now closed. If you&#39;d like to explore how the program could work in

---

### School Prog Info Session Recording Follow Up 1 -350

| Property | Value |
|----------|-------|
| **ID** | 183 |
| **Module** | Registration |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send Follow Up 1 to -350 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=183&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `createdtime` more than days ago `7`
- `createdtime` less than days ago `8`
- `source` is `Info Session Recording`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_numberofparticipants)` less than or equal to `350`
- `(cf_vtcmregistration_deals : (Potentials) sales_stage)` is `Considering`

**Actions:**

1. **Send Email** — Send info session recording follow up 1
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(cf_vtcmregistration_replyto : (Users) first_name) $(cf_vtcmregistration_replyto : (Users) last_name)<$(cf_vtcmregistration_replyto : (Users) email1)>`
   - **Subject:** FW: Thanks for your interest in our School Wellbeing Program! 
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), I hope you’re well! Just wondering if you’ve had a chance to review the information you requested about our School Wellbeing Program? Registrations close very soon. If you’re ready to jump right in, you can confirm your school’s place

---

### School Prog Info Session Recording Follow Up 1 +351

| Property | Value |
|----------|-------|
| **ID** | 185 |
| **Module** | Registration |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send Follow Up 1 to +351 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=185&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Info Session Recording`
- `createdtime` more than days ago `7`
- `createdtime` less than days ago `8`
- `(cf_vtcmregistration_deals : (Potentials) sales_stage)` is `Considering`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_numberofparticipants)` greater than or equal to `351`

**Actions:**

1. **Send Email** — Send info session recording follow up 1
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(cf_vtcmregistration_replyto : (Users) first_name) $(cf_vtcmregistration_replyto : (Users) last_name)<$(cf_vtcmregistration_replyto : (Users) email1)>`
   - **Subject:** Fwd: Thanks for your interest in our School Wellbeing Program!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), I hope you’re doing well. Just following up to see if you’d like to jump on a quick call to discuss our School Wellbeing Program and how it could work for your school in 2026? If so, please feel free to book a time that suits you. A f

---

### School Prog Info Session Recording Follow Up 2 -350

| Property | Value |
|----------|-------|
| **ID** | 184 |
| **Module** | Registration |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send Follow Up 2 to -350 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=184&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Info Session Recording`
- `createdtime` more than days ago `14`
- `createdtime` less than days ago `15`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_numberofparticipants)` less than or equal to `350`
- `(cf_vtcmregistration_deals : (Potentials) sales_stage)` is `Considering`

**Actions:**

1. **Send Email** — Send info session recording follow up 2
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(cf_vtcmregistration_replyto : (Users) first_name) $(cf_vtcmregistration_replyto : (Users) last_name)<$(cf_vtcmregistration_replyto : (Users) email1)>`
   - **Subject:** Have more questions about our School Wellbeing Program?
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), We hope you’re doing well. Just checking in one last time to see if you’re still considering our School Wellbeing Program for 2026? If you’re keen to proceed, we’d love to set you and your school up for success with our onboarding pro

2. **Update Fields** — Move deal to In Campaign - Cold
   - `(cf_vtcmregistration_deals : (Potentials) sales_stage)` → `In Campaign`
   - `(cf_vtcmregistration_deals : (Potentials) cf_potentials_incampaignrating)` → `Cold`
   - `(cf_vtcmregistration_deals : (Potentials) closingdate)` → `add_days(get_date('today'), 7)`

---

### School Prog Info Session Recording Follow Up 2 +351

| Property | Value |
|----------|-------|
| **ID** | 186 |
| **Module** | Registration |
| **Trigger** | Time Interval (Daily) |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send Follow Up 2 to +351 |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=186&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Info Session Recording`
- `createdtime` more than days ago `14`
- `createdtime` less than days ago `15`
- `(cf_vtcmregistration_deals : (Potentials) sales_stage)` is `Considering`
- `(cf_vtcmregistration_deals : (Potentials) cf_potentials_numberofparticipants)` greater than or equal to `351`

**Actions:**

1. **Send Email** — Send info session recording follow up 2
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Have questions about our School Wellbeing Program?
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), We hope you’re doing well. Just checking in one last time to see if there&#39;s anything we can do to support you as you consider our School Wellbeing Program for 2026? We&#39;d be so happy to jump on a quick call to answer any specif

2. **Update Fields** — Move deal to In Campaign - Cold
   - `(cf_vtcmregistration_deals : (Potentials) sales_stage)` → `In Campaign`
   - `(cf_vtcmregistration_deals : (Potentials) cf_potentials_incampaignrating)` → `Cold`
   - `(cf_vtcmregistration_deals : (Potentials) closingdate)` → `add_days(get_date('today'), 7)`

---

### School Prog Info Session Registration Email

| Property | Value |
|----------|-------|
| **ID** | 176 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send email when someone registers for a school info session |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=176&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Info Session Registration`

**Actions:**

1. **Send Email** — Send school info session registration email
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Thanks for registering for our live info session!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thank you so much for your interest in The Resilience Project&#39;s 2026 School Wellbeing Program and for registering to attend the Information Session on $cf_vtcmregistration_eventshortname. Please note the time difference if you are

---

### Test attaching ical

| Property | Value |
|----------|-------|
| **ID** | 131 |
| **Module** | Registration |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Attach an ical file to email |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=131&mode=V7Edit) |

**Conditions:**

```
All :   -NA-
Any : -NA-
```

**Actions:**

1. **Send Email** — Send registration email
   - **To:** `aretha@theresilienceproject.com.au`
   - **From:** `aretha@theresilienceproject.com.au`
   - **Subject:** TRP | Thanks for registering!
   - **Body excerpt:** $_RELATED_DOCUMENT_LINKS_$

---

### Workplace webinar recording email 100-

| Property | Value |
|----------|-------|
| **ID** | 181 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email to anyone who requests the workplace webinar recording |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=181&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Workplace Webinar Recording 2025 <100`

**Actions:**

1. **Send Email** — Send 100- workplace webinar recording link
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** Workplace Wellbeing Webinar Recording
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thank you so much for your interest in our Workplace Wellbeing Webinar. We loved hearing our panelists explore why stress and burnout happen - and what leaders can do to mitigate them. We hope you enjoy the conversation and take away 

---

### Workplace webinar recording email 100+

| Property | Value |
|----------|-------|
| **ID** | 180 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Send an email to anyone who requests the workplace webinar recording |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=180&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Workplace Webinar Recording 2025 >100`

**Actions:**

1. **Send Email** — Send 100+ workplace webinar recording link
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `enquiries@theresilienceproject.com.au`
   - **Subject:** Workplace Wellbeing Webinar Recording
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thank you so much for registering for the recording of our 2025 Workplace Wellbeing Webinar. We were so grateful to have expert voices from government, corporate, and medical fields to explore why stress and burnout happen &mdash; and

---

### Workplace webinar registration email

| Property | Value |
|----------|-------|
| **ID** | 133 |
| **Module** | Registration |
| **Trigger** | Registration creation |
| **Type** | singlepath |
| **Status** | Disabled |
| **Description** | Send an email to anyone who registers for a workplace webinar |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=133&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmregistration_eventregistrant` is not empty
- `cf_vtcmregistration_eventno` is not empty
- `source` is `Workplace Webinar Registration 2025`

**Actions:**

1. **Send Email** — Send workplace registration email
   - **To:** `$(cf_vtcmregistration_eventregistrant : (Contacts) email)`
   - **From:** `$(assigned_user_id : (Users) first_name) $(assigned_user_id : (Users) last_name)<$(assigned_user_id : (Users) email1)>`
   - **Subject:** TRP | Thanks for registering!
   - **Body excerpt:** Hi $(cf_vtcmregistration_eventregistrant : (Contacts) firstname), Thank you so much for registering to attend The Resilience Project&#39;s Workplace Wellbeing Webinar on Wednesday, 2nd of April, 12-1pm (AEDT). As the session start time is based on Australian Eastern Daylight Time, please consider th

---

## SEIP

### Copy SEIP Priority to 2026 priority

| Property | Value |
|----------|-------|
| **ID** | 201 |
| **Module** | SEIP |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Copy SEIP Priority to 2026 priority |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=201&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmseip_priority` has changed ``
- `fld_vtcmseipname` is `2026 SEIP`

**Actions:**

1. **Update Fields** — Copy SEIP Priority to 2026 Priority
   - `(cf_vtcmseip_organisation : (Accounts) cf_accounts_2026priority)` → `cf_vtcmseip_priority`

---

### Set priority

| Property | Value |
|----------|-------|
| **ID** | 199 |
| **Module** | SEIP |
| **Trigger** | SEIP creation |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Set priority on newly created SEIP |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=199&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmseip_priority` is empty ``

**Actions:**

1. **Update Fields** — Set priority
   - `cf_vtcmseip_priority` → `if cf_vtcmseip_numberofparticipants >= 500 then 'Tier 1' else if cf_vtcmseip_numberofparticipants >= 300 then 'Tier 2' else if cf_vtcmseip_numberofparticipants >= 100 then 'Tier 3' else 'Tier 4' end`

---

### Update org details from SEIP

| Property | Value |
|----------|-------|
| **ID** | 197 |
| **Module** | SEIP |
| **Trigger** | Every time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Update org details from SEIP |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=197&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_vtcmseip_yearswithtrp` is `1st year`
*All of:*
- `assigned_user_id` has changed
- `fld_welcomemeeting` has changed

**Actions:**

1. **Update Fields** — Update assignee or welcome meeting
   - `(cf_vtcmseip_organisation : (Accounts) assigned_user_id)` → `assigned_user_id`
   - `(cf_vtcmseip_organisation : (Accounts) cf_accounts_welcomemeeting)` → `fld_welcomemeeting`

---

## creditnotes

### Notify accounts of returned items

| Property | Value |
|----------|-------|
| **ID** | 165 |
| **Module** | creditnotes |
| **Trigger** | Only first time conditions are met |
| **Type** | singlepath |
| **Status** | Enabled |
| **Description** | Notify accounts that a credit can be applied to a change to order |
| **Edit** | [Open in Vtiger](https://theresilienceproject.od2.vtiger.com/index.php?module=Workflows&parent=Settings&view=Edit&record=165&mode=V7Edit) |

**Conditions:**

*All of:*
- `cf_creditnotes_returntype` is `Change to order`
- `cf_creditnotes_itemsreceived` is `1`

**Actions:**

1. **Send Email** — Send email to accounts to apply credit
   - **To:** `accounts@theresilienceproject.com.au`
   - **From:** `aretha@theresilienceproject.com.au`
   - **Subject:** Returned items received
   - **Body excerpt:** Hi Helen and Mel Items received from: $account_id Link to credit note: CRM Detail View URL Thanks

---
