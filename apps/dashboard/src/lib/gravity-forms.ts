const GF_BASE_URL =
  process.env.GF_BASE_URL ||
  'https://forms.theresilienceproject.com.au/wp-json/gf/v2';
const GF_CONSUMER_KEY = process.env.GF_CONSUMER_KEY || '';
const GF_CONSUMER_SECRET = process.env.GF_CONSUMER_SECRET || '';

function getAuthHeader(): string {
  return (
    'Basic ' +
    Buffer.from(`${GF_CONSUMER_KEY}:${GF_CONSUMER_SECRET}`).toString('base64')
  );
}

export function isGFConfigured(): boolean {
  return GF_CONSUMER_KEY !== '' && GF_CONSUMER_SECRET !== '';
}

export interface GFFormSummary {
  id: number;
  title: string;
  description: string;
  entries: string;
  is_active: string;
}

export interface GFFormDetail {
  id: number;
  title: string;
  description: string;
  fields: GFField[];
  pagination?: { pages?: string[] };
  is_active: string;
  date_created: string;
  entries: string;
}

export interface GFField {
  id: number;
  label: string;
  type: string;
  inputName?: string;
  isRequired?: boolean;
  pageNumber?: number;
  choices?: { text: string; value: string }[];
}

export interface GFEntry {
  id: string;
  form_id: string;
  date_created: string;
  status: string;
  [key: string]: unknown;
}

export interface GFEntriesParams {
  formId: number;
  pageSize?: number;
  currentPage?: number;
  status?: string;
  startDate?: string;
  endDate?: string;
  fieldFilters?: { key: string; value: string }[];
}

export interface GFFormResults {
  entry_count: number;
  field_data: Record<string, Record<string, number>>;
}

async function gfFetch<T>(path: string): Promise<T> {
  const response = await fetch(`${GF_BASE_URL}${path}`, {
    headers: { Authorization: getAuthHeader() },
    next: { revalidate: 300 },
  });

  if (!response.ok) {
    throw new Error(`GF API error: ${response.status} ${response.statusText}`);
  }

  return response.json() as Promise<T>;
}

export async function listForms(): Promise<GFFormSummary[]> {
  const data = await gfFetch<Record<string, GFFormSummary>>('/forms');
  return Object.values(data).sort((a, b) => a.id - b.id);
}

export async function getForm(id: number): Promise<GFFormDetail> {
  return gfFetch<GFFormDetail>(`/forms/${id}`);
}

export async function getEntries(
  params: GFEntriesParams,
): Promise<{ entries: GFEntry[]; total_count: number }> {
  const { formId, pageSize = 20, currentPage = 1, status, startDate, endDate, fieldFilters } = params;

  const qs = new URLSearchParams();
  qs.set('form_ids[]', String(formId));
  qs.set('paging[page_size]', String(pageSize));
  qs.set('paging[current_page]', String(currentPage));
  qs.set('sorting[key]', 'date_created');
  qs.set('sorting[direction]', 'DESC');

  // GF REST API v2 expects `search` as a JSON-encoded string, not bracket notation
  const search: Record<string, unknown> = {};
  if (status) search.status = status;
  if (startDate) search.start_date = startDate;
  if (endDate) search.end_date = endDate;
  if (fieldFilters) {
    search.field_filters = fieldFilters.map((f) => ({ key: f.key, value: f.value }));
  }
  if (Object.keys(search).length > 0) {
    qs.set('search', JSON.stringify(search));
  }

  return gfFetch<{ entries: GFEntry[]; total_count: number }>(`/entries?${qs.toString()}`);
}

export async function getEntry(entryId: number): Promise<GFEntry> {
  return gfFetch<GFEntry>(`/entries/${entryId}`);
}

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

export async function getLastEntryDate(formId: number): Promise<string | null> {
  const qs = new URLSearchParams();
  qs.set('paging[page_size]', '1');
  qs.set('paging[current_page]', '1');
  qs.set('sorting[key]', 'date_created');
  qs.set('sorting[direction]', 'DESC');

  const data = await gfFetch<{ entries: GFEntry[] }>(`/forms/${formId}/entries?${qs.toString()}`);
  return data.entries?.[0]?.date_created ?? null;
}

export async function getFormResults(formId: number): Promise<GFFormResults> {
  return gfFetch<GFFormResults>(`/forms/${formId}/results`);
}

// Feed types returned by the GF REST API
export interface GFFeed {
  id: string;
  form_id: string;
  addon_slug: string;
  meta: Record<string, unknown>;
}

export interface GFWebhookFeed {
  feedId: string;
  url: string;
  method: string;
  fieldMappings: { key: string; value: string; custom_key?: string }[];
}

export async function getFeeds(formId: number): Promise<GFFeed[]> {
  try {
    const data = await gfFetch<Record<string, GFFeed> | GFFeed[]>(
      `/forms/${formId}/feeds`,
    );
    return Array.isArray(data) ? data : Object.values(data);
  } catch {
    return [];
  }
}

/**
 * Extract webhook feeds from a form's feeds.
 * GF Webhooks Add-On feeds have addon_slug "gravityformswebhooks".
 */
export function extractWebhookEndpoints(feeds: GFFeed[]): GFWebhookFeed[] {
  return feeds
    .filter(
      (f) =>
        f.addon_slug === 'gravityformswebhooks' &&
        f.meta?.requestURL,
    )
    .map((f) => ({
      feedId: f.id,
      url: String(f.meta.requestURL ?? ''),
      method: String(f.meta.requestMethod ?? 'POST').toUpperCase(),
      fieldMappings: Array.isArray(f.meta.fieldValues)
        ? (f.meta.fieldValues as { key: string; value: string; custom_key?: string }[])
        : [],
    }));
}
