# Phase 1: Webhook Error Infrastructure — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Surface Gravity Forms webhook errors in the dashboard so the team can see which forms have failing webhooks and diagnose issues quickly.

**Architecture:** Add `getEntryNotes()` to the GF API client, expose it via a new API route, create a bulk webhook-errors endpoint for the health summary, show webhook status in the entry detail drawer, and add error indicators to the forms explorer.

**Tech Stack:** Next.js API routes, React Query hooks, GF REST API v2 `/entries/{id}/notes` endpoint.

---

### Task 1: Add entry notes function to GF API client

**Files:**
- Modify: `src/lib/gravity-forms.ts`

**Step 1: Add the EntryNote type and getEntryNotes function**

Add after the existing `getEntry` function (~line 121):

```typescript
export interface EntryNote {
  id: string;
  entry_id: string;
  note_type: string;
  sub_type: string;
  value: string;
  date_created: string;
  user_name: string;
}

export async function getEntryNotes(entryId: number): Promise<EntryNote[]> {
  const data = await gfFetch<Record<string, EntryNote>>(`/entries/${entryId}/notes`);
  return Object.values(data);
}
```

**Step 2: Verify TypeScript compiles**

Run: `cd apps/dashboard && npx tsc --noEmit`
Expected: No errors

**Step 3: Commit**

```
feat(dashboard): add getEntryNotes to GF API client
```

---

### Task 2: Create entry notes API route

**Files:**
- Create: `src/app/api/gf/entries/[entryId]/notes/route.ts`

**Step 1: Create the route handler**

Follow the exact pattern from `src/app/api/gf/entries/detail/[entryId]/route.ts`:

```typescript
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getEntryNotes, isGFConfigured } from '@/lib/gravity-forms';

export async function GET(
  _request: NextRequest,
  { params }: { params: Promise<{ entryId: string }> },
) {
  if (!isGFConfigured()) {
    return NextResponse.json(
      { error: 'Gravity Forms API not configured', configured: false },
      { status: 503 },
    );
  }

  const { entryId: entryIdStr } = await params;
  const entryId = parseInt(entryIdStr, 10);

  if (isNaN(entryId)) {
    return NextResponse.json(
      { error: 'Invalid entry ID' },
      { status: 400 },
    );
  }

  try {
    const notes = await getEntryNotes(entryId);
    return NextResponse.json({ notes, configured: true });
  } catch (error: unknown) {
    console.error(`Failed to fetch notes for entry ${entryIdStr}:`, error);
    return NextResponse.json(
      { error: `Failed to fetch notes for entry ${entryIdStr}` },
      { status: 500 },
    );
  }
}
```

**Step 2: Verify by hitting the endpoint manually**

Run: `curl -s http://localhost:3000/api/gf/entries/49338/notes | python3 -m json.tool | head -20`
Expected: JSON with `notes` array containing the webhook error for that entry

**Step 3: Commit**

```
feat(dashboard): add entry notes API route
```

---

### Task 3: Create useEntryNotes hook

**Files:**
- Create: `src/hooks/use-entry-notes.ts`

**Step 1: Create the hook**

Follow the pattern from `src/hooks/use-gf-entry.ts`:

```typescript
import { useQuery } from '@tanstack/react-query';
import type { EntryNote } from '@/lib/gravity-forms';

interface EntryNotesResponse {
  notes: EntryNote[];
  configured: boolean;
}

export function useEntryNotes(entryId: string | null) {
  return useQuery<EntryNotesResponse | null>({
    queryKey: ['gf-entry-notes', entryId],
    queryFn: async () => {
      const res = await fetch(`/api/gf/entries/${entryId}/notes`);
      if (res.status === 503) return null;
      if (!res.ok) throw new Error(`Failed to fetch notes for entry ${entryId}`);
      return res.json();
    },
    enabled: entryId != null,
    staleTime: 2 * 60 * 1000,
  });
}
```

**Step 2: Verify TypeScript compiles**

Run: `cd apps/dashboard && npx tsc --noEmit`

**Step 3: Commit**

```
feat(dashboard): add useEntryNotes hook
```

---

### Task 4: Show webhook status in entry detail drawer

**Files:**
- Modify: `src/components/entry-detail-drawer.tsx`

**Step 1: Add webhook notes section to the drawer**

Import the hook and add a webhook status section between the field values and the raw data toggle. Show webhook notes with `note_type === 'gravityformswebhooks'`:

- Import `useEntryNotes` from `@/hooks/use-entry-notes`
- Import `AlertTriangle` and `CheckCircle2` from `lucide-react`
- Call `useEntryNotes(open ? entryId : null)` alongside the existing `useGFEntry` call
- Filter notes to `note_type === 'gravityformswebhooks'`
- Render each note as:
  - Green check + "Webhook sent" for `sub_type === 'success'`
  - Amber warning + error message for `sub_type === 'error'`
- Place this section after the field values `</dl>` and before the raw data toggle

The section should look like:

```tsx
{/* Webhook status */}
{webhookNotes.length > 0 && (
  <div className="space-y-2">
    <p className="text-[10px] uppercase tracking-wider text-muted-foreground">
      Webhook Status
    </p>
    {webhookNotes.map((note) => (
      <div
        key={note.id}
        className={`flex items-start gap-2 rounded-lg border p-2.5 text-xs ${
          note.sub_type === 'error'
            ? 'border-[var(--rose-accent)]/20 bg-[var(--rose-accent)]/5'
            : 'border-[var(--teal-accent)]/20 bg-[var(--teal-accent)]/5'
        }`}
      >
        {note.sub_type === 'error' ? (
          <AlertTriangle className="h-3.5 w-3.5 text-[var(--rose-accent)] shrink-0 mt-0.5" />
        ) : (
          <CheckCircle2 className="h-3.5 w-3.5 text-[var(--teal-accent)] shrink-0 mt-0.5" />
        )}
        <span className="break-words">{note.value}</span>
      </div>
    ))}
  </div>
)}
```

**Step 2: Test in browser**

Navigate to form 75, click an entry, verify the webhook error shows as an amber alert in the drawer.
Navigate to an entry with a successful webhook (older entry), verify green check.

**Step 3: Commit**

```
feat(dashboard): show webhook status in entry detail drawer
```

---

### Task 5: Create webhook errors health endpoint

**Files:**
- Create: `src/app/api/health/webhook-errors/route.ts`
- Modify: `src/lib/gravity-forms.ts` (add helper)

**Step 1: Add getRecentWebhookErrors to gravity-forms.ts**

This function fetches the last N entries per form and checks their notes for errors. Uses in-memory cache (same pattern as wordpress-pages.ts):

```typescript
export interface WebhookError {
  formId: number;
  formTitle: string;
  entryId: string;
  feedName: string;
  error: string;
  dateCreated: string;
}

const WEBHOOK_ERRORS_CACHE_TTL = 5 * 60 * 1000;
let webhookErrorsCache: { errors: WebhookError[]; expiresAt: number } | null = null;

export async function getRecentWebhookErrors(): Promise<WebhookError[]> {
  if (webhookErrorsCache && Date.now() < webhookErrorsCache.expiresAt) {
    return webhookErrorsCache.errors;
  }

  const summaries = await listForms();
  const activeForms = summaries.filter((s) => s.is_active === '1');

  const allErrors: WebhookError[] = [];

  await Promise.all(
    activeForms.map(async (form) => {
      try {
        const { entries } = await getEntries({
          formId: Number(form.id),
          pageSize: 5,
          currentPage: 1,
        });

        const noteResults = await Promise.all(
          entries.map(async (entry) => {
            try {
              const notes = await getEntryNotes(Number(entry.id));
              return notes
                .filter((n) => n.note_type === 'gravityformswebhooks' && n.sub_type === 'error')
                .map((n) => ({
                  formId: Number(form.id),
                  formTitle: form.title,
                  entryId: entry.id,
                  feedName: n.value.split(':')[0] || 'Unknown',
                  error: n.value,
                  dateCreated: n.date_created,
                }));
            } catch {
              return [];
            }
          }),
        );

        allErrors.push(...noteResults.flat());
      } catch {
        // Skip forms that fail
      }
    }),
  );

  allErrors.sort((a, b) => b.dateCreated.localeCompare(a.dateCreated));

  webhookErrorsCache = { errors: allErrors, expiresAt: Date.now() + WEBHOOK_ERRORS_CACHE_TTL };
  return allErrors;
}
```

**Step 2: Create the API route**

```typescript
// src/app/api/health/webhook-errors/route.ts
import { NextResponse } from 'next/server';
import { isGFConfigured, getRecentWebhookErrors } from '@/lib/gravity-forms';

export async function GET() {
  if (!isGFConfigured()) {
    return NextResponse.json(
      { errors: [], configured: false },
      { status: 503 },
    );
  }

  try {
    const errors = await getRecentWebhookErrors();
    return NextResponse.json({ errors, configured: true });
  } catch (error: unknown) {
    console.error('Failed to fetch webhook errors:', error);
    return NextResponse.json(
      { errors: [], configured: true, error: 'Failed to fetch webhook errors' },
      { status: 500 },
    );
  }
}
```

**Step 3: Verify the endpoint**

Run: `curl -s http://localhost:3000/api/health/webhook-errors | python3 -c "import json,sys; d=json.load(sys.stdin); print(f'{len(d[\"errors\"])} errors'); [print(f'  Form {e[\"formId\"]}: {e[\"feedName\"]} ({e[\"dateCreated\"]})') for e in d['errors'][:5]]"`
Expected: List of recent webhook errors across forms

**Step 4: Commit**

```
feat(dashboard): add webhook errors health endpoint
```

---

### Task 6: Add webhook error indicators to forms explorer

**Files:**
- Create: `src/hooks/use-webhook-errors.ts`
- Modify: `src/components/forms-explorer.tsx`

**Step 1: Create the hook**

```typescript
// src/hooks/use-webhook-errors.ts
import { useQuery } from '@tanstack/react-query';
import type { WebhookError } from '@/lib/gravity-forms';

interface WebhookErrorsResponse {
  errors: WebhookError[];
  configured: boolean;
}

export function useWebhookErrors() {
  return useQuery<WebhookErrorsResponse>({
    queryKey: ['webhook-errors'],
    queryFn: async () => {
      const res = await fetch('/api/health/webhook-errors');
      if (res.status === 503) return { errors: [], configured: false };
      if (!res.ok) throw new Error('Failed to fetch webhook errors');
      return res.json();
    },
    staleTime: 5 * 60 * 1000,
  });
}
```

**Step 2: Add error counts to forms explorer**

In `forms-explorer.tsx`:

- Import `useWebhookErrors`
- Import `AlertTriangle` from `lucide-react`
- Call `useWebhookErrors()` in `FormsExplorer`
- Build an error count map: `Map<number, number>` from `formId -> error count`
- Pass the error count to `FormRow` as a prop
- In the table, show a red error count badge next to the form purpose when errors > 0:
  ```tsx
  {errorCount > 0 && (
    <Badge variant="secondary" className="text-[10px] bg-[var(--rose-accent)]/10 text-[var(--rose-accent)]">
      <AlertTriangle className="h-2.5 w-2.5 mr-0.5" />
      {errorCount}
    </Badge>
  )}
  ```

**Step 3: Test in browser**

Navigate to `/forms`, verify forms 53, 75, 89 etc. show red error badges.

**Step 4: Commit**

```
feat(dashboard): add webhook error indicators to forms explorer
```

---

### Task 7: Final verification and commit

**Step 1: TypeScript check**

Run: `cd apps/dashboard && npx tsc --noEmit`

**Step 2: Full smoke test**

- `/forms` — verify groups, pages, last entry dates, error badges all show
- Click form 75 → click an entry → verify webhook error in drawer
- Hit `http://localhost:3000/api/health/webhook-errors` — verify error list

**Step 3: Final commit if any cleanup needed**
