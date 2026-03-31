import { NextResponse } from 'next/server';
import fs from 'fs/promises';
import path from 'path';

const DETAIL_PATH = path.resolve(process.cwd(), '../../docs/vtiger/workflows-detail.json');
const LIST_PATH = path.resolve(process.cwd(), '../../docs/vtiger/workflows-data.json');

export async function GET() {
  // Prefer the enriched detail data, fall back to basic list data
  for (const filePath of [DETAIL_PATH, LIST_PATH]) {
    try {
      const content = await fs.readFile(filePath, 'utf-8');
      const data = JSON.parse(content);
      return NextResponse.json(data);
    } catch {
      continue;
    }
  }

  return NextResponse.json(
    {
      error: 'Workflows data not found. Run: VT_SESSION=<cookie> npx tsx scripts/scrape-vtiger-workflows.ts',
      count: 0,
      workflows: [],
    },
    { status: 404 },
  );
}
