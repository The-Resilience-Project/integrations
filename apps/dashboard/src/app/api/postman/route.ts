import { NextResponse } from 'next/server';
import {
  getPostmanCollections,
  getPostmanEnvironments,
} from '@/lib/postman-parser';

export async function GET() {
  try {
    const [collections, environments] = await Promise.all([
      getPostmanCollections(),
      getPostmanEnvironments(),
    ]);
    return NextResponse.json({ collections, environments });
  } catch (error: unknown) {
    console.error('Failed to parse Postman collections:', error);
    return NextResponse.json(
      { error: 'Failed to load Postman collections' },
      { status: 500 },
    );
  }
}
