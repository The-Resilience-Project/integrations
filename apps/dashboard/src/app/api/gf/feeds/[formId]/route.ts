import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import {
  getFeeds,
  extractWebhookEndpoints,
  isGFConfigured,
} from '@/lib/gravity-forms';

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
    const feeds = await getFeeds(formId);
    const webhooks = extractWebhookEndpoints(feeds);
    return NextResponse.json({ feeds, webhooks, configured: true });
  } catch (error: unknown) {
    console.error(`Failed to fetch feeds for form ${formIdStr}:`, error);
    return NextResponse.json(
      { error: `Failed to fetch feeds for form ${formIdStr}` },
      { status: 500 },
    );
  }
}
