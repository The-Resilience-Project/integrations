import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { traceLogs } from '@/lib/cloudwatch';

export async function GET(request: NextRequest) {
  const email = request.nextUrl.searchParams.get('email');
  const fn = request.nextUrl.searchParams.get('fn');
  const timestamp = request.nextUrl.searchParams.get('timestamp');
  const window = request.nextUrl.searchParams.get('window') ?? '60';

  if (!email || !fn || !timestamp) {
    return NextResponse.json(
      { error: 'Missing required params: email, fn, timestamp' },
      { status: 400 },
    );
  }

  try {
    // GF date_created is UTC but has no timezone indicator — force UTC parsing
    const utcTimestamp = timestamp.includes('T') || timestamp.includes('Z')
      ? timestamp
      : timestamp.replace(' ', 'T') + 'Z';
    const ts = new Date(utcTimestamp).getTime();
    const logs = await traceLogs(fn, email, ts, parseInt(window, 10));
    return NextResponse.json({ logs });
  } catch (error: unknown) {
    console.error('Trace failed:', error);
    return NextResponse.json(
      { error: 'Failed to trace request' },
      { status: 500 },
    );
  }
}
