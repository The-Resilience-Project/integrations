import { NextResponse } from 'next/server';
import { isGFConfigured, getRecentWebhookErrors } from '@/lib/gravity-forms';

export async function GET() {
  if (!isGFConfigured()) {
    return NextResponse.json(
      { errors: [], configured: false, error: 'GF API not configured' },
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
