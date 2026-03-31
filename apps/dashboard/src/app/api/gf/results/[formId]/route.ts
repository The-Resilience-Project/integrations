import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getFormResults, isGFConfigured } from '@/lib/gravity-forms';

export async function GET(
  _request: NextRequest,
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

  try {
    const results = await getFormResults(formId);
    return NextResponse.json({ ...results, configured: true });
  } catch (error: unknown) {
    // The results endpoint may not be available for all forms
    console.error(`Failed to fetch results for form ${formIdStr}:`, error);
    return NextResponse.json(
      { error: `Failed to fetch results for form ${formIdStr}`, configured: true },
      { status: 404 },
    );
  }
}
