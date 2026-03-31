/**
 * Vtiger Workflow Scraper
 *
 * Fetches all workflows from Vtiger's Settings > Automation > Workflows page
 * by scraping the server-rendered HTML (no JSON API available for workflows).
 *
 * Usage:
 *   VT_SESSION=<PHPSESSID> npx tsx scripts/scrape-vtiger-workflows.ts
 *
 * Output:
 *   docs/vtiger/workflows-data.json
 */

import * as fs from 'fs';
import * as path from 'path';

const VT_BASE = 'https://theresilienceproject.od2.vtiger.com';
const VT_SESSION = process.env.VT_SESSION || '';
const OUTPUT_DIR = path.resolve(__dirname, '..', 'docs', 'vtiger');
const OUTPUT_FILE = path.join(OUTPUT_DIR, 'workflows-data.json');

if (!VT_SESSION) {
  console.error('Error: VT_SESSION environment variable required.');
  console.error('Copy your PHPSESSID from browser DevTools:');
  console.error('  Vtiger > DevTools > Application > Cookies > PHPSESSID');
  console.error('');
  console.error('Usage: VT_SESSION=<value> npx tsx scripts/scrape-vtiger-workflows.ts');
  process.exit(1);
}

// Module icon class → human-readable name
const ICON_TO_MODULE: Record<string, string> = {
  'vicon-quotes': 'Quotes',
  'vicon-invoice': 'Invoice',
  'vicon-potentials': 'Deals',
  'vicon-contacts': 'Contacts',
  'vicon-accounts': 'Organisations',
  'vicon-calendar': 'Events',
  'vicon-task': 'Tasks',
  'vicon-documents': 'Documents',
  'vicon-products': 'Products',
  'vicon-services': 'Services',
  'vicon-faq': 'FAQ',
  'vicon-vendors': 'Vendors',
  'vicon-pricebooks': 'Price Books',
  'vicon-project': 'Projects',
  'vicon-projectmilestone': 'Project Milestones',
  'vicon-employees': 'Employees',
  'vicon-internaltickets': 'Internal Tickets',
  'vicon-campaigns': 'Campaigns',
};

// Custom module abbreviation → name (from Vtiger's title attributes)
const CUSTOM_MODULE_MAP: Record<string, string> = {
  'En': 'Enquiries',
  'Re': 'Registration',
  'Da': 'Date Acceptances',
  'In': 'Invitations',
  'As': 'Assessments',
  'Ac': 'Actions',
  'SE': 'SEIP',
};

interface VtigerWorkflow {
  id: number;
  name: string;
  module: string;
  trigger: string;
  conditions: string;
  actions: string[];
  enabled: boolean;
  workflowType: string;
  editUrl: string;
}

async function fetchPage(page: number, workflowType: string): Promise<string> {
  const url = `${VT_BASE}/index.php?module=Workflows&parent=Settings&view=List&page=${page}&workflowtype=${workflowType}`;
  console.log(`  Fetching page ${page} (${workflowType})...`);

  const res = await fetch(url, {
    headers: {
      Cookie: `PHPSESSID=${VT_SESSION}`,
      'User-Agent': 'TRP-Workflow-Scraper/1.0',
    },
  });

  if (!res.ok) {
    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
  }

  return res.text();
}

function hasNextPage(html: string): boolean {
  const match = html.match(/id="nextPageExist"\s+value="([^"]*)"/);
  return match ? match[1] === '1' : false;
}

function parseModule(rowHtml: string): string {
  // Check for standard module icon
  const iconMatch = rowHtml.match(/class="(vicon-\w+)"/);
  if (iconMatch) {
    return ICON_TO_MODULE[iconMatch[1]] || iconMatch[1].replace('vicon-', '');
  }

  // Check for custom module (span with title)
  const customTitleMatch = rowHtml.match(/class="custom-module[^"]*"\s+title="([^"]+)"/);
  if (customTitleMatch) {
    return customTitleMatch[1];
  }

  // Check for custom module abbreviation
  const customAbbrMatch = rowHtml.match(/class="custom-module[^"]*">(\w+)<\/span>/);
  if (customAbbrMatch) {
    return CUSTOM_MODULE_MAP[customAbbrMatch[1]] || customAbbrMatch[1];
  }

  return 'Unknown';
}

function parseName(rowHtml: string): string {
  // The name is in the 3rd <td> — look for the link text after the recordurl
  const cells = rowHtml.split('</td>');
  if (cells.length >= 3) {
    const nameCell = cells[2];
    const linkMatch = nameCell.match(/<a[^>]*>([^<]+)<\/a>/);
    if (linkMatch) return linkMatch[1].trim();
  }
  return 'Unknown';
}

function parseTrigger(rowHtml: string): string {
  const cells = rowHtml.split('</td>');
  if (cells.length >= 4) {
    const triggerCell = cells[3];
    const linkMatch = triggerCell.match(/<a[^>]*style="color:black">([^<]+)<\/a>/);
    if (linkMatch) return linkMatch[1].trim();
  }
  return 'Unknown';
}

function parseConditions(rowHtml: string): string {
  const cells = rowHtml.split('</td>');
  if (cells.length >= 5) {
    const condCell = cells[4];
    // Extract condition text from spans, strip HTML
    const conditions = condCell
      .replace(/<br\s*\/?>/g, '\n')
      .replace(/<[^>]+>/g, '')
      .replace(/&nbsp;/g, ' ')
      .replace(/&amp;/g, '&')
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/\n\s*\n/g, '\n')
      .trim();
    return conditions;
  }
  return '';
}

function parseActions(rowHtml: string): string[] {
  const cells = rowHtml.split('</td>');
  if (cells.length >= 6) {
    const actionCell = cells[5];
    const actions: string[] = [];
    const actionMatches = actionCell.matchAll(/<span>([^<]+)<\/span>/g);
    for (const match of actionMatches) {
      const action = match[1].replace(/&nbsp;/g, ' ').trim();
      if (action) actions.push(action);
    }
    return actions;
  }
  return [];
}

function parseWorkflows(html: string, workflowType: string): VtigerWorkflow[] {
  const workflows: VtigerWorkflow[] = [];

  // Split by table rows with listViewEntries class
  const rowRegex = /<tr\s+class="listViewEntries"[^>]*data-id="(\d+)"[^>]*data-recordurl="([^"]*)"[^>]*data-workflowtype='([^']*)'[^>]*>([\s\S]*?)(?=<tr\s+class="(?:listViewEntries|rules_))/g;

  let match;
  while ((match = rowRegex.exec(html)) !== null) {
    const [, idStr, recordUrl, wfType, rowContent] = match;
    const id = parseInt(idStr, 10);

    const enabled = rowContent.includes('checked');
    const module = parseModule(rowContent);
    const name = parseName(rowContent);
    const trigger = parseTrigger(rowContent);
    const conditions = parseConditions(rowContent);
    const actions = parseActions(rowContent);

    const editUrl = `${VT_BASE}/${recordUrl.replace(/&amp;/g, '&')}`;

    workflows.push({
      id,
      name,
      module,
      trigger,
      conditions,
      actions,
      enabled,
      workflowType: wfType || workflowType,
      editUrl,
    });
  }

  return workflows;
}

async function scrapeAll(): Promise<VtigerWorkflow[]> {
  const allWorkflows: VtigerWorkflow[] = [];

  for (const wfType of ['singlepath', 'multipath']) {
    console.log(`\nScraping ${wfType} workflows...`);
    let page = 1;
    let hasMore = true;

    while (hasMore) {
      const html = await fetchPage(page, wfType);

      // Check if we got redirected to login
      if (html.includes('module=Users&action=Login') || html.includes('<title>Login</title>')) {
        console.error('Error: Session cookie expired or invalid. Please get a fresh PHPSESSID.');
        process.exit(1);
      }

      const workflows = parseWorkflows(html, wfType);
      console.log(`  Page ${page}: found ${workflows.length} workflows`);
      allWorkflows.push(...workflows);

      hasMore = hasNextPage(html) && workflows.length > 0;
      page++;
    }
  }

  return allWorkflows;
}

async function main() {
  console.log('Vtiger Workflow Scraper');
  console.log('======================');

  const workflows = await scrapeAll();

  console.log(`\nTotal: ${workflows.length} workflows`);

  // Group by module for summary
  const byModule: Record<string, number> = {};
  for (const wf of workflows) {
    byModule[wf.module] = (byModule[wf.module] || 0) + 1;
  }
  console.log('\nBy module:');
  for (const [mod, count] of Object.entries(byModule).sort((a, b) => b[1] - a[1])) {
    console.log(`  ${mod}: ${count}`);
  }

  // Ensure output directory exists
  if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
  }

  // Write output
  const output = {
    generated: new Date().toISOString(),
    count: workflows.length,
    workflows,
  };

  fs.writeFileSync(OUTPUT_FILE, JSON.stringify(output, null, 2));
  console.log(`\nWritten to ${OUTPUT_FILE}`);
}

main().catch((err) => {
  console.error('Scraper failed:', err);
  process.exit(1);
});
