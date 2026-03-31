import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getDocContent } from '@/lib/docs-loader';

export async function GET(
  _request: NextRequest,
  { params }: { params: Promise<{ slug: string[] }> },
) {
  const { slug } = await params;
  const slugPath = slug.join('/');

  try {
    const doc = await getDocContent(slugPath);

    if (!doc) {
      return NextResponse.json(
        { error: `Document not found: ${slugPath}` },
        { status: 404 },
      );
    }

    return NextResponse.json(doc);
  } catch (error: unknown) {
    console.error(`Failed to load doc ${slugPath}:`, error);
    return NextResponse.json(
      { error: `Failed to load document` },
      { status: 500 },
    );
  }
}
