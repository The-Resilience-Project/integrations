# Flow-First Dashboard Redesign

## Problem

The dashboard is currently organised around technical components (Lambda functions, forms, workflows) rather than the actual data flow: **Page -> Form -> Webhook -> API -> Vtiger**. When troubleshooting, the team has to mentally stitch together information from separate pages. Webhook errors — like the cURL timeouts currently affecting multiple forms — are invisible.

## Design

### Navigation Structure

```
Health              /health              New landing page — errors + system status
──────
Schools             /schools             Journey index — list of school flows
  Enquiry           /schools/enquiry     Flow detail page
  Registration      /schools/registration
  Confirmation      /schools/confirmation
  Resources         /schools/resources
  Date Acceptance   /schools/date-acceptance
  Assessment        /schools/assessment
  Info Sessions     /schools/info-sessions
  More Info         /schools/more-info
Early Years         /early-years         Journey index
  Enquiry           /early-years/enquiry
  Confirmation      /early-years/confirmation
  Info Sessions     /early-years/info-sessions
  DET Confirmation  /early-years/det-confirmation
Workplaces          /workplaces          Journey index
  Enquiry           /workplaces/enquiry
  Webinar           /workplaces/webinar
Shared              /shared              Journey index
  General Enquiry   /shared/enquiry
──────
Pipeline            /pipeline            Keep — cross-cutting view
Docs                /docs                Keep
API Reference       /api-reference       Keep
```

Monitor is replaced by Health. Forms and Workflows are absorbed into journey/flow pages.

### Health Page (/health)

The new landing page. Answers: "Is everything working right now?"

**Sections:**
1. **Status summary** — count of healthy flows vs flows with errors, total submissions and errors in last 24h
2. **Recent webhook errors** — table of recent errors across all forms, showing feed name, form, error message, and relative timestamp
3. **Lambda health** — condensed version of existing metrics (invocations, errors, durations)

**Data source for webhook errors:** GF REST API `/entries/{id}/notes` endpoint. Notes with `note_type: "gravityformswebhooks"` contain webhook results. `sub_type: "error"` for failures, `sub_type: "success"` for successful deliveries. Error values contain the cURL error or HTTP status.

**Fetching strategy:** For each active form, fetch the last 5 entries and check their notes. Run in parallel, cache results for 5 minutes in-memory (same pattern as WordPress pages — response too large for Next.js fetch cache).

### Journey Pages (/schools, /early-years, /workplaces, /shared)

Each journey page shows its flows as cards with at-a-glance status:

- Flow name and description
- Form entry count and last submission date
- Webhook health indicator (green/amber/red)
- Quick links to WP page, form entries, Lambda logs

### Flow Detail Pages (/schools/enquiry, etc.)

Each flow shows the full chain as a vertical timeline of connected cards:

```
WordPress Page
  → Page title + URL + external link
Gravity Form
  → Form ID, title, entry count, last entry, field count
  → [View entries] tab
Webhook
  → Feed name, endpoint URL, method
  → Recent errors count + detail
  → Field mappings
Lambda Function
  → Function name, invocations, errors, P95 duration
  → [View logs] link
VTAP Chain
  → Ordered list of VTAP endpoints called
Vtiger Workflow
  → Workflow names triggered
```

Tabs within the flow page: Overview (the timeline), Entries, Analytics, Errors.

### Webhook Error Infrastructure

New types:

```typescript
interface EntryNote {
  id: string;
  entryId: string;
  noteType: string;     // 'gravityformswebhooks'
  subType: string;      // 'error' | 'success'
  value: string;        // error message or "Webhook sent. Response code: 200."
  dateCreated: string;
}

interface WebhookError {
  formId: number;
  formTitle: string;
  entryId: string;
  feedName: string;
  error: string;
  dateCreated: string;
}
```

New API routes:
- `GET /api/gf/entries/[entryId]/notes` — fetch notes for a single entry
- `GET /api/health/webhook-errors` — aggregated recent errors across all forms

New GF API functions:
- `getEntryNotes(entryId)` — calls `/entries/{id}/notes`
- `getRecentWebhookErrors(formIds)` — batch fetch recent entries + notes

### Data Sources (existing, reused)

| Data | Source | Already exists |
|------|--------|---------------|
| Form list, fields, entries | GF REST API | Yes |
| Webhook feeds/config | GF REST API `/forms/{id}/feeds` | Yes |
| Webhook errors | GF REST API `/entries/{id}/notes` | **New** |
| WordPress pages | WP REST API `/wp/v2/pages` | Yes (just added) |
| Lambda metrics | CloudWatch via `/api/metrics/` | Yes |
| Lambda logs | CloudWatch via `/api/logs/` | Yes |
| Flow definitions | `pipeline-map.ts` | Yes |
| VTAP endpoints | `pipeline-map.ts` | Yes |

## Phasing

### Phase 1: Webhook Error Infrastructure
- Add `getEntryNotes()` to gravity-forms.ts
- Add `/api/gf/entries/[entryId]/notes` route
- Add `/api/health/webhook-errors` route (batch check recent entries)
- Show webhook status in entry detail drawer
- Commit last entry date feature (already built, uncommitted)

### Phase 2: Health Page
- Build `/health` page with status summary, error feed, Lambda health
- Update nav: replace Monitor with Health as landing page
- Add journey health indicators to sidebar

### Phase 3: Journey Pages
- Build `/schools`, `/early-years`, `/workplaces`, `/shared` index pages
- Flow cards with status, entry counts, last activity
- Extend `pipeline-map.ts` with WP page and form group data
- Update sidebar nav with journey sections

### Phase 4: Flow Detail Pages
- Build `/{journey}/{flow}` pages with vertical timeline
- Migrate entries table, analytics, and error views into flow context
- Remove standalone `/forms` page (absorbed into flows)
- Remove `/workflows` page (absorbed into flows)

## Key Files

### Existing (to modify)
- `src/lib/nav-config.ts` — sidebar navigation
- `src/lib/pipeline-map.ts` — flow definitions (add WP page + form group)
- `src/lib/gravity-forms.ts` — add entry notes function
- `src/lib/types.ts` — add webhook error types
- `src/app/(portal)/layout.tsx` — portal layout

### New
- `src/app/(portal)/health/page.tsx`
- `src/app/(portal)/[journey]/page.tsx`
- `src/app/(portal)/[journey]/[flow]/page.tsx`
- `src/app/api/gf/entries/[entryId]/notes/route.ts`
- `src/app/api/health/webhook-errors/route.ts`
- `src/components/flow-timeline.tsx`
- `src/components/webhook-errors-table.tsx`
- `src/components/journey-flow-card.tsx`
