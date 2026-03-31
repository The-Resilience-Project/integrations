import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getLogs } from '@/lib/cloudwatch';

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ fn: string }> },
) {
  const { fn } = await params;
  const range = request.nextUrl.searchParams.get('range') ?? '1h';
  const filter = request.nextUrl.searchParams.get('filter') ?? '';

  try {
    const logs = await getLogs(fn, range, filter);
    return NextResponse.json({ logs });
  } catch (error: unknown) {
    console.error(`Failed to fetch logs for ${fn}:`, error);
    return NextResponse.json(
      { error: `Failed to fetch logs for ${fn}` },
      { status: 500 },
    );
  }
}
