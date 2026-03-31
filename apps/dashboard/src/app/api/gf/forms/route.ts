import { NextResponse } from 'next/server';
import { listForms, isGFConfigured } from '@/lib/gravity-forms';

export async function GET() {
  if (!isGFConfigured()) {
    return NextResponse.json(
      {
        error: 'Gravity Forms API not configured',
        configured: false,
      },
      { status: 503 },
    );
  }

  try {
    const forms = await listForms();
    return NextResponse.json({ forms, configured: true });
  } catch (error: unknown) {
    console.error('Failed to fetch GF forms:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forms from Gravity Forms API' },
      { status: 500 },
    );
  }
}
