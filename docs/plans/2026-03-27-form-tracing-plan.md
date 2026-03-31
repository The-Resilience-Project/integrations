# Form-to-API Cross-linking & Entry Tracing — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add form-to-API cross-linking in docs and entry-level CloudWatch log tracing on form detail pages.

**Architecture:** A new `/api/trace` route searches CloudWatch logs by email + timestamp. The form detail page gets a "Trace" button on each entry row that triggers this search and displays results in a timeline. Endpoint docs get auto-generated "Called by Forms" sections via GF feeds data.

**Tech Stack:** Existing AWS CloudWatch SDK, React Query, GF REST API

---

### Task 1: URL-to-Function Mapping Utility

**Files:**
- Create: `apps/dashboard/src/lib/url-to-function.ts`

**Step 1: Create the mapping**

Maps webhook URLs (from GF feeds) to Lambda function names (from `FUNCTION_NAMES`).

```typescript
// apps/dashboard/src/lib/url-to-function.ts

/**
 * Maps a GF webhook URL to the Lambda function name it invokes.
 * e.g. "https://xxx.execute-api.../api/enquiry.php" → "enquiry"
 *      "https://xxx.execute-api.../api/v2/schools/enquiry" → "v2_enquiry"
 */
const URL_TO_FUNCTION: Record<string, string> = {
  'enquiry.php': 'enquiry',
  'confirm.php': 'confirm',
  'confirm_existing_schools.php': 'confirm_existing_schools',
  'register.php': 'register',
  'seminar_registration.php': 'seminar_registration',
  'qualify.php': 'qualify',
  'accept_dates.php': 'accept_dates',
  'submit_ca.php': 'submit_ca',
  'order_resources.php': 'order_resources',
  'order_resources_2026.php': 'order_resources_2026',
  'calculate_shipping.php': 'calculate_shipping',
  'prize_pack.php': 'prize_pack',
  'calendly_event.php': 'calendly_event',
  '/api/v2/schools/enquiry': 'v2_enquiry',
  '/api/v2/schools/register': 'v2_register',
  '/api/v2/schools/prize-pack': 'v2_prize_pack',
};

export function urlToFunctionName(webhookUrl: string): string | null {
  for (const [pattern, fn] of Object.entries(URL_TO_FUNCTION)) {
    if (webhookUrl.includes(pattern)) return fn;
  }
  return null;
}
```

**Step 2: Verify build**

Run: `cd apps/dashboard && npm run build`

---

### Task 2: Trace API Route

**Files:**
- Create: `apps/dashboard/src/app/api/trace/route.ts`
- Modify: `apps/dashboard/src/lib/cloudwatch.ts` — add `traceLogs()` function

**Step 1: Add `traceLogs` to cloudwatch.ts**

Add after the existing `getLogs` function:

```typescript
export async function traceLogs(
  functionName: string,
  email: string,
  timestamp: number,
  windowSeconds = 60,
): Promise<LogEntry[]> {
  const startTime = timestamp - windowSeconds * 1000;
  const endTime = timestamp + windowSeconds * 1000;
  const logGroupName = `/aws/lambda/${SERVICE_PREFIX}${functionName}`;

  try {
    const command = new FilterLogEventsCommand({
      logGroupName,
      startTime,
      endTime,
      filterPattern: `"${email}"`,
      limit: 50,
    });

    const response = await cwLogsClient.send(command);

    return (response.events ?? []).map((event) => ({
      timestamp: event.timestamp ?? 0,
      message: event.message ?? '',
      logStream: event.logStreamName ?? '',
    }));
  } catch (error: unknown) {
    if (
      error &&
      typeof error === 'object' &&
      'name' in error &&
      error.name === 'ResourceNotFoundException'
    ) {
      return [];
    }
    throw error;
  }
}
```

**Step 2: Create the trace API route**

```typescript
// apps/dashboard/src/app/api/trace/route.ts
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { traceLogs } from '@/lib/cloudwatch';

export async function GET(request: NextRequest) {
  const email = request.nextUrl.searchParams.get('email');
  const fn = request.nextUrl.searchParams.get('fn');
  const timestamp = request.nextUrl.searchParams.get('timestamp');
  const window = request.nextUrl.searchParams.get('window') ?? '60';

  if (!email || !fn || !timestamp) {
    return NextResponse.json(
      { error: 'Missing required params: email, fn, timestamp' },
      { status: 400 },
    );
  }

  try {
    const ts = new Date(timestamp).getTime();
    const logs = await traceLogs(fn, email, ts, parseInt(window, 10));
    return NextResponse.json({ logs });
  } catch (error: unknown) {
    console.error('Trace failed:', error);
    return NextResponse.json(
      { error: 'Failed to trace request' },
      { status: 500 },
    );
  }
}
```

**Step 3: Verify build**

Run: `cd apps/dashboard && npm run build`

---

### Task 3: Trace Hook

**Files:**
- Create: `apps/dashboard/src/hooks/use-trace.ts`

```typescript
import { useQuery } from '@tanstack/react-query';
import type { LogEntry } from '@/lib/types';

interface TraceResponse {
  logs: LogEntry[];
}

interface TraceParams {
  email: string;
  fn: string;
  timestamp: string;
}

export function useTrace(params: TraceParams | null) {
  return useQuery<TraceResponse>({
    queryKey: ['trace', params?.email, params?.fn, params?.timestamp],
    queryFn: async () => {
      const sp = new URLSearchParams({
        email: params!.email,
        fn: params!.fn,
        timestamp: params!.timestamp,
        window: '120',
      });
      const res = await fetch(`/api/trace?${sp}`);
      if (!res.ok) throw new Error('Trace failed');
      return res.json();
    },
    enabled: params != null,
    staleTime: 30 * 1000,
  });
}
```

---

### Task 4: Trace Timeline Component

**Files:**
- Create: `apps/dashboard/src/components/trace-timeline.tsx`

Build a vertical timeline showing GF entry data at the top, then matched CloudWatch log entries below, sorted by timestamp. Each log entry is colour-coded: green for INFO, amber for WARNING, red for ERROR/EXCEPTION.

Key elements:
- GF entry node: email, form ID, date_created
- Log entry nodes: timestamp, message (syntax highlighted), log level badge
- Vertical connecting line between nodes
- Loading skeleton while trace is running
- Empty state if no logs found

---

### Task 5: Wire Tracing into Form Detail Page

**Files:**
- Modify: `apps/dashboard/src/app/(portal)/forms/[id]/page.tsx`

Add to the entries table:
1. Import `useTrace`, `urlToFunctionName`, `TraceTimeline`
2. Add state: `tracingEntry` (the entry being traced)
3. Derive `functionName` from the form's outbound webhook URL using `urlToFunctionName`
4. Add a "Trace" button on each entry row (only shown if `functionName` is resolved)
5. When clicked, extract `contact_email` from the entry fields and set `tracingEntry`
6. Below the entries table, render `<TraceTimeline>` when an entry is being traced

The email field key varies by form — GF entries store field values as `{fieldId: value}`. The email field is typically the one with type "email" in the form definition. Use the form's fields to find `type === 'email'` and read that field ID from the entry.

---

### Task 6: Cross-link Docs with "Called by Forms"

**Files:**
- Create: `apps/dashboard/src/hooks/use-form-endpoint-map.ts`
- Modify: `apps/dashboard/src/app/(portal)/docs/[...slug]/page.tsx`

**Step 1: Create a hook that builds endpoint → forms mapping**

Uses the existing `useForms()` data to build a reverse map: for each outbound webhook URL, which forms call it.

```typescript
// use-form-endpoint-map.ts
import { useMemo } from 'react';
import { useForms } from './use-forms';

interface FormRef {
  id: number;
  title: string;
}

export function useFormEndpointMap(): Record<string, FormRef[]> {
  const { data } = useForms();
  return useMemo(() => {
    const map: Record<string, FormRef[]> = {};
    if (!data?.forms) return map;
    for (const form of data.forms) {
      for (const ep of form.endpoints) {
        if (ep.direction === 'outbound') {
          // Extract the path portion
          const urlPath = ep.endpoint.replace(/^(GET|POST|PUT|DELETE)\s+/, '');
          const existing = map[urlPath] ?? [];
          existing.push({ id: form.id, title: form.purpose || form.title });
          map[urlPath] = existing;
        }
      }
    }
    return map;
  }, [data]);
}
```

**Step 2: Add "Called by Forms" section to doc pages**

At the bottom of the doc content in `docs/[...slug]/page.tsx`, render a "Called by Forms" card if any forms reference endpoints mentioned in this doc. Match by checking if the doc slug maps to known endpoint patterns (reuse `ENDPOINT_DOC_MAP` in reverse).

---

### Task 7: Commit

```bash
git add apps/dashboard/
git commit -m "Add form entry tracing via CloudWatch and form-to-API cross-linking"
```
