import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getEntries, isGFConfigured } from '@/lib/gravity-forms';
import type { GFEntriesParams } from '@/lib/gravity-forms';

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ formId: string }> },
) {
  if (!isGFConfigured()) {
    return NextResponse.json(
      { error: 'Gravity Forms API not configured', configured: false },
      { status: 503 },
    );
  }

  const { formId: formIdStr } = await params;
  const formId = parseInt(formIdStr, 10);

  if (isNaN(formId)) {
    return NextResponse.json(
      { error: 'Invalid form ID' },
      { status: 400 },
    );
  }

  const searchParams = request.nextUrl.searchParams;

  const entriesParams: GFEntriesParams = {
    formId,
    pageSize: parseInt(searchParams.get('page_size') ?? '20', 10),
    currentPage: parseInt(searchParams.get('page') ?? '1', 10),
  };

  const status = searchParams.get('status');
  if (status) entriesParams.status = status;

  const startDate = searchParams.get('start_date');
  if (startDate) entriesParams.startDate = startDate;

  const endDate = searchParams.get('end_date');
  if (endDate) entriesParams.endDate = endDate;

  const searchKey = searchParams.get('search_key');
  const searchValue = searchParams.get('search_value');
  if (searchKey && searchValue) {
    entriesParams.fieldFilters = [{ key: searchKey, value: searchValue }];
  }

  try {
    const data = await getEntries(entriesParams);
    return NextResponse.json({ ...data, configured: true });
  } catch (error: unknown) {
    console.error(`Failed to fetch entries for form ${formIdStr}:`, error);
    return NextResponse.json(
      { error: `Failed to fetch entries for form ${formIdStr}` },
      { status: 500 },
    );
  }
}
