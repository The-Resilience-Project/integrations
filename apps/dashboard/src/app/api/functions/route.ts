import { NextResponse } from 'next/server';
import { FUNCTION_NAMES } from '@/lib/constants';

export async function GET() {
  return NextResponse.json({ functions: FUNCTION_NAMES });
}
