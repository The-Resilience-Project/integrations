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
