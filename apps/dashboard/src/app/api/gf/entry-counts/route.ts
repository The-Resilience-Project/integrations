import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getEntries, isGFConfigured } from '@/lib/gravity-forms';

/**
 * GET /api/gf/entry-counts?form_ids=1,45,76&start_date=2026-03-29
 *
 * Returns entry counts per form for a given date range.
 * Uses page_size=1 to minimise data transfer — we only need total_count.
 */
export async function GET(request: NextRequest) {
  if (!isGFConfigured()) {
    return NextResponse.json(
      { error: 'Gravity Forms API not configured', configured: false },
      { status: 503 },
    );
  }

  const searchParams = request.nextUrl.searchParams;
  const formIdsParam = searchParams.get('form_ids');
  const startDate = searchParams.get('start_date');
  const endDate = searchParams.get('end_date');

  if (!formIdsParam) {
    return NextResponse.json({ error: 'form_ids required' }, { status: 400 });
  }

  const formIds = formIdsParam
    .split(',')
    .map((id) => parseInt(id.trim(), 10))
    .filter((id) => !isNaN(id));

  try {
    const counts: Record<number, number> = {};

    // Batch in groups of 5 to avoid overwhelming the GF API
    const batchSize = 5;
    for (let i = 0; i < formIds.length; i += batchSize) {
      const batch = formIds.slice(i, i + batchSize);
      await Promise.all(
        batch.map(async (formId) => {
          try {
            const data = await getEntries({
              formId,
              pageSize: 1,
              currentPage: 1,
              startDate: startDate ?? undefined,
              endDate: endDate ?? undefined,
            });
            counts[formId] = data.total_count;
          } catch (err) {
            console.error(`Entry count failed for form ${formId}:`, err);
            // Fall back to -1 so the UI can show a fallback
            counts[formId] = -1;
          }
        }),
      );
    }

    return NextResponse.json({ counts, configured: true });
  } catch (error: unknown) {
    console.error('Failed to fetch entry counts:', error);
    return NextResponse.json(
      { error: 'Failed to fetch entry counts' },
      { status: 500 },
    );
  }
}
