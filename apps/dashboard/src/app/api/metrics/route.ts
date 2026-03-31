import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getMetrics } from '@/lib/cloudwatch';

export async function GET(request: NextRequest) {
  const range = request.nextUrl.searchParams.get('range') ?? '1h';

  try {
    const metrics = await getMetrics(range);
    return NextResponse.json(metrics);
  } catch (error: unknown) {
    console.error('Failed to fetch metrics:', error);
    return NextResponse.json(
      { error: 'Failed to fetch metrics from CloudWatch' },
      { status: 500 },
    );
  }
}
