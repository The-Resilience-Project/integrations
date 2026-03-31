import { NextResponse } from 'next/server';
import { getDocsTree } from '@/lib/docs-loader';

export async function GET() {
  try {
    const tree = await getDocsTree();
    return NextResponse.json({ tree });
  } catch (error: unknown) {
    console.error('Failed to load docs tree:', error);
    return NextResponse.json(
      { error: 'Failed to load docs tree' },
      { status: 500 },
    );
  }
}
