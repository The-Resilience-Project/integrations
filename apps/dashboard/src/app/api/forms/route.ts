import { NextResponse } from 'next/server';
import {
  isGFConfigured,
  listForms,
  getForm,
  getFeeds,
  getLastEntryDate,
  extractWebhookEndpoints,
} from '@/lib/gravity-forms';
import { INBOUND_OVERRIDES } from '@/lib/forms-inbound-overrides';
import { fetchFormPageMap } from '@/lib/wordpress-pages';
import type { GravityForm, FormEndpoint } from '@/lib/types';

export async function GET() {
  if (!isGFConfigured()) {
    return NextResponse.json(
      { forms: [], configured: false, error: 'GF API not configured' },
      { status: 503 },
    );
  }

  try {
    // Fetch all form IDs + WordPress page map in parallel
    const [formSummaries, pageMap] = await Promise.all([
      listForms(),
      fetchFormPageMap().catch((err) => {
        console.error('Failed to fetch WordPress pages:', err);
        return new Map<number, { id: number; title: string; url: string; slug: string }>();
      }),
    ]);

    // Fetch full details + feeds for each form in parallel
    const forms: GravityForm[] = await Promise.all(
      formSummaries.map(async (summary) => {
        const [detail, feeds, lastEntryDate] = await Promise.all([
          getForm(summary.id),
          getFeeds(summary.id),
          getLastEntryDate(Number(summary.id)).catch(() => null),
        ]);

        const webhooks = extractWebhookEndpoints(feeds);
        const inbound = INBOUND_OVERRIDES[summary.id];

        // Build endpoints array
        const endpoints: FormEndpoint[] = [];

        if (inbound) {
          endpoints.push({
            direction: 'inbound',
            endpoint: inbound.endpoint,
            method: inbound.endpoint.startsWith('GET') ? 'GET' : 'POST',
            trigger: inbound.trigger,
            fieldMappings: inbound.fieldMappings.map((m) => ({
              apiParam: m.apiParam,
              formFieldLabel: m.formFieldLabel,
              formInput: m.formInput,
              note: m.note,
            })),
          });
        }

        for (const wh of webhooks) {
          endpoints.push({
            direction: 'outbound',
            endpoint: `${wh.method} ${wh.url}`,
            method: wh.method as 'GET' | 'POST',
            trigger: 'GF Webhooks Add-On',
            fieldMappings: wh.fieldMappings.map((m) => ({
              apiParam: m.custom_key || m.key,
              formFieldLabel: m.value,
              formInput: m.value,
            })),
          });
        }

        const pageCount = detail.pagination?.pages?.length ?? 1;

        return {
          id: detail.id,
          title: detail.title,
          description: detail.description,
          purpose: detail.title,
          pageCount,
          entryCount: parseInt(String(summary.entries || detail.entries || '0'), 10),
          isActive: summary.is_active === '1' || detail.is_active === '1',
          fields: (detail.fields || []).map((f) => ({
            id: f.id,
            label: f.label,
            type: f.type,
            inputName: f.inputName || `input_${f.id}`,
            isRequired: f.isRequired ?? false,
            page: f.pageNumber ?? 1,
            choices: f.choices,
          })),
          endpoints,
          wordpressPage: pageMap.get(Number(summary.id)),
          lastEntryDate: lastEntryDate ?? undefined,
        };
      }),
    );

    return NextResponse.json({ forms, configured: true });
  } catch (error: unknown) {
    console.error('Failed to fetch forms from GF API:', error);
    return NextResponse.json(
      { forms: [], configured: true, error: 'Failed to fetch forms' },
      { status: 500 },
    );
  }
}
