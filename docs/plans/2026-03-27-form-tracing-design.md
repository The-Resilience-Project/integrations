# Form-to-API Integration Map & Entry Tracing — Design

## Problem

The dev portal shows forms and API endpoints separately. There's no way to:
1. See which GF forms call which API endpoints (and with what data)
2. Trace a specific form submission through GF → Lambda → CRM to debug issues

## Solution

Two connected features built into the existing dev portal.

### Feature 1: Form-to-API Cross-linking

**On endpoint doc pages** — a "Called by Forms" section showing which GF forms submit to this endpoint, derived from live GF Feeds API data.

**On form detail pages** — outbound webhook URLs link to the relevant endpoint documentation page.

Data source: GF Feeds API (already fetched) + the existing `ENDPOINT_DOC_MAP` in `endpoint-card.tsx`.

### Feature 2: Entry-Level Tracing

On the form detail page's entries table, each entry has a "Trace" button.

**Flow:**
1. Extract `contact_email` from the GF entry fields
2. Determine the Lambda function from the webhook URL (e.g., `enquiry.php` → `trp-api-dev-enquiry`)
3. Search CloudWatch logs for that function, filtered by the email, within ±60s of the entry's `date_created`
4. Display matched logs in a timeline view alongside the GF entry data

**Correlation method:** Email + timestamp. No Lambda code changes required. The existing logs already include `contact_email` in every request.

**New API route:** `GET /api/trace?email=<email>&fn=<functionName>&timestamp=<iso>&window=60`
- Calls `FilterLogEventsCommand` with `filterPattern` matching the email
- Time range: `timestamp - window` to `timestamp + window` (seconds)
- Returns matched log events

**New components:**
- `<EntryTraceButton>` — on each entry row, triggers the trace
- `<TraceTimeline>` — displays GF entry + matched CloudWatch logs as a vertical timeline

**Limitations:**
- Email-based correlation may match multiple requests if the same person submits twice within the window
- No Vtiger response data visible (CRM calls aren't logged with responses currently)
- Requires AWS credentials for CloudWatch access

## Architecture

```
GF Entry (email, date_created)
    ↓
/api/trace?email=...&fn=...&timestamp=...
    ↓
CloudWatch FilterLogEventsCommand
    ↓
Matched log entries (request start, processing, success/fail)
    ↓
TraceTimeline component
```

## Implementation Phases

1. Cross-linking: add "Called by Forms" to docs, link form webhooks to docs
2. Trace API route: CloudWatch search by email + time window
3. Trace UI: button on entries, timeline component
