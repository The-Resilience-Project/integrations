import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getForm, isGFConfigured } from '@/lib/gravity-forms';

export async function GET(
  _request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  if (!isGFConfigured()) {
    return NextResponse.json(
      { error: 'Gravity Forms API not configured', configured: false },
      { status: 503 },
    );
  }

  const { id } = await params;
  const formId = parseInt(id, 10);

  if (isNaN(formId)) {
    return NextResponse.json(
      { error: 'Invalid form ID' },
      { status: 400 },
    );
  }

  try {
    const form = await getForm(formId);
    return NextResponse.json({ form, configured: true });
  } catch (error: unknown) {
    console.error(`Failed to fetch GF form ${id}:`, error);
    return NextResponse.json(
      { error: `Failed to fetch form ${id}` },
      { status: 500 },
    );
  }
}
