/**
 * Vtiger Workflow Detail Scraper
 *
 * Enriches the workflow list data with detailed conditions and task/action
 * information by scraping each workflow's edit page and task detail pages.
 *
 * Prerequisites:
 *   Run the list scraper first:
 *   VT_SESSION=<PHPSESSID> npx tsx scripts/scrape-vtiger-workflows.ts
 *
 * Usage:
 *   VT_SESSION=<PHPSESSID> npx tsx scripts/scrape-vtiger-workflow-details.ts
 *   VT_SESSION=<PHPSESSID> npx tsx scripts/scrape-vtiger-workflow-details.ts --debug
 *   VT_SESSION=<PHPSESSID> npx tsx scripts/scrape-vtiger-workflow-details.ts --workflow=137
 *
 * Output:
 *   docs/vtiger/workflows-detail.json
 *   docs/vtiger/workflow-reference.md
 */

import * as fs from 'fs';
import * as path from 'path';

const VT_BASE = 'https://theresilienceproject.od2.vtiger.com';
const VT_SESSION = process.env.VT_SESSION || '';
const DATA_DIR = path.resolve(__dirname, '..', 'docs', 'vtiger');
const INPUT_FILE = path.join(DATA_DIR, 'workflows-data.json');
const OUTPUT_FILE = path.join(DATA_DIR, 'workflows-detail.json');
const DOCS_FILE = path.join(DATA_DIR, 'workflow-reference.md');
const DEBUG_DIR = path.join(DATA_DIR, 'debug');

const DEBUG = process.argv.includes('--debug');
const SINGLE_WORKFLOW = process.argv.find((a) => a.startsWith('--workflow='))?.split('=')[1];
const DELAY_MS = 600;

if (!VT_SESSION) {
  console.error('Error: VT_SESSION environment variable required.');
  console.error('Copy your PHPSESSID from browser DevTools:');
  console.error('  Vtiger > DevTools > Application > Cookies > PHPSESSID');
  console.error('');
  console.error('Usage: VT_SESSION=<value> npx tsx scripts/scrape-vtiger-workflow-details.ts');
  process.exit(1);
}

// Display name → Vtiger internal module name
const MODULE_INTERNAL_NAMES: Record<string, string> = {
  Quotes: 'Quotes',
  Invoice: 'Invoice',
  Contacts: 'Contacts',
  Organisations: 'Accounts',
  Deals: 'Potentials',
  Events: 'Events',
  Tasks: 'Calendar',
  Documents: 'Documents',
  Products: 'Products',
  Services: 'Services',
  FAQ: 'Faq',
  Vendors: 'Vendors',
  'Price Books': 'PriceBooks',
  Projects: 'Project',
  'Project Milestones': 'ProjectMilestone',
  Employees: 'Employees',
  'Internal Tickets': 'HelpDesk',
  Campaigns: 'Campaigns',
  creditnotes: 'CreditNotes',
  Enquiries: 'Enquiries',
  Registration: 'Registration',
  'Date Acceptances': 'DateAcceptances',
  Invitations: 'Invitations',
  Assessments: 'Assessments',
  Actions: 'Actions',
  SEIP: 'SEIP',
};

// ─── Types ───────────────────────────────────────────────────────────────────

interface WorkflowListEntry {
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

interface WorkflowCondition {
  fieldname: string;
  operation: string;
  value: string | null;
  valuetype: string;
  joincondition: string;
  groupjoin: string;
  groupid: number;
}

interface WorkflowTask {
  id: number;
  title: string;
  taskType: string;
  active: boolean;
  details: Record<string, unknown>;
}

interface WorkflowDetail extends WorkflowListEntry {
  internalModule: string;
  conditionsParsed: WorkflowCondition[];
  tasks: WorkflowTask[];
  description: string;
  scrapeErrors: string[];
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function getInternalModuleName(displayName: string): string {
  return MODULE_INTERNAL_NAMES[displayName] || displayName;
}

async function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function fetchWithAuth(url: string): Promise<string> {
  const res = await fetch(url, {
    headers: {
      Cookie: `PHPSESSID=${VT_SESSION}`,
      'User-Agent': 'TRP-Workflow-Scraper/1.0',
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  if (!res.ok) {
    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
  }

  const text = await res.text();

  if (text.includes('module=Users&action=Login') || text.includes('<title>Login</title>')) {
    throw new Error('Session expired — get a fresh PHPSESSID');
  }

  return text;
}

function stripHtml(html: string): string {
  return html
    .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
    .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
    .replace(/<head[^>]*>[\s\S]*?<\/head>/gi, '')
    .replace(/<br\s*\/?>/g, '\n')
    .replace(/<[^>]+>/g, '')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&rsquo;/g, '\u2019')
    .replace(/&ldquo;/g, '\u201C')
    .replace(/&rdquo;/g, '\u201D')
    .replace(/\s+/g, ' ')
    .trim();
}

function decodeHtmlEntities(text: string): string {
  return text
    .replace(/&quot;/g, '"')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&#039;/g, "'")
    .replace(/&apos;/g, "'");
}

function saveDebugFile(workflowId: number, suffix: string, content: string): void {
  if (!DEBUG) return;
  if (!fs.existsSync(DEBUG_DIR)) {
    fs.mkdirSync(DEBUG_DIR, { recursive: true });
  }
  fs.writeFileSync(path.join(DEBUG_DIR, `wf-${workflowId}-${suffix}.html`), content);
}

// ─── Edit Page — description + internal module name ─────────────────────────

async function fetchEditPage(workflowId: number): Promise<string> {
  const url =
    `${VT_BASE}/index.php?module=Workflows&parent=Settings` +
    `&view=Edit&record=${workflowId}&mode=V7Edit`;
  const html = await fetchWithAuth(url);
  saveDebugFile(workflowId, 'edit', html);
  return html;
}

function extractModuleFromEditPage(html: string): string | null {
  // <input type='hidden' id="module_name" name='module_name' value="Quotes" >
  const match = html.match(/name=['"']module_name['"'][^>]*value=['""]([^'"]+)['"]/);
  return match ? match[1] : null;
}

function extractDescriptionFromEditPage(html: string): string {
  // <textarea ... name="summary" id="summary" ...>Send quote to contact</textarea>
  const match = html.match(/<textarea[^>]*name=["']summary["'][^>]*>([\s\S]*?)<\/textarea>/);
  if (match && match[1].trim()) {
    return stripHtml(match[1]);
  }
  return '';
}

// ─── Conditions page — structured conditions + task list ────────────────────

interface TaskRef {
  id: number;
  title: string;
  taskType: string;
  active: boolean;
}

async function fetchConditionsPage(
  workflowId: number,
  internalModule: string,
  workflowType: string,
): Promise<{ conditions: WorkflowCondition[]; taskRefs: TaskRef[] }> {
  const url =
    `${VT_BASE}/index.php?module=Workflows&parent=Settings` +
    `&view=EditAjax&mode=getWorkflowConditions` +
    `&record=${workflowId}&module_name=${internalModule}&workflowtype=${workflowType}`;

  const html = await fetchWithAuth(url);
  saveDebugFile(workflowId, 'conditions', html);

  const conditions = parseConditionsFromPage(html);
  const taskRefs = extractTaskListFromPage(html);

  return { conditions, taskRefs };
}

function parseConditionsFromPage(html: string): WorkflowCondition[] {
  // The conditions are in: <input type="hidden" id="olderConditions" value='[JSON]' />
  // The JSON uses HTML entities: &quot; for "
  const match = html.match(/id=["']olderConditions["']\s+value='([^']*)'/);
  if (!match) return [];

  const decoded = decodeHtmlEntities(match[1]);
  if (!decoded || decoded === '[]') return [];

  try {
    const parsed = JSON.parse(decoded);
    if (Array.isArray(parsed)) {
      return parsed.map((c: Record<string, unknown>) => ({
        fieldname: String(c.fieldname || ''),
        operation: String(c.operation || ''),
        value: c.value != null ? String(c.value) : null,
        valuetype: String(c.valuetype || 'rawtext'),
        joincondition: String(c.joincondition || ''),
        groupjoin: String(c.groupjoin || 'and'),
        groupid: Number(c.groupid || 0),
      }));
    }
  } catch (err) {
    console.warn(`    ⚠ Failed to parse conditions JSON: ${(err as Error).message}`);
  }

  return [];
}

function extractTaskListFromPage(html: string): TaskRef[] {
  const tasks: TaskRef[] = [];
  const seen = new Set<number>();

  // Vtiger task rows:
  // <tr class="listViewEntries" data-task-status='1' data-id='235'>
  //   ...
  //   <input ... data-taskType="VTEmailTask" ... checked="" .../>
  //   ...
  //   <td class="listViewEntryValue">Send Mail...</td>
  //   <td><span class="pull-left">Send program quote to new school</span></td>
  // </tr>
  const rowRegex =
    /class="listViewEntries"[^>]*data-task-status='(\d+)'[^>]*data-id='(\d+)'[^>]*>([\s\S]*?)(?=<tr\s|<\/tbody)/g;
  let match;
  while ((match = rowRegex.exec(html)) !== null) {
    const active = match[1] === '1';
    const id = parseInt(match[2], 10);
    const rowContent = match[3];

    // Skip the template row and duplicates
    if (seen.has(id)) continue;
    seen.add(id);

    // Task type from data-taskType attribute on checkbox
    const typeMatch = rowContent.match(/data-taskType="([^"]+)"/);
    const taskType = typeMatch ? typeMatch[1] : 'Unknown';

    // Task title from <span class="pull-left">...</span> in last <td>
    const titleMatch = rowContent.match(/<span class="pull-left">([^<]+)<\/span>/);
    const title = titleMatch ? titleMatch[1].trim() : '';

    if (title) {
      tasks.push({ id, title, taskType, active });
    }
  }

  return tasks;
}

// ─── Task Detail Scraping ────────────────────────────────────────────────────

async function fetchTaskDetail(
  workflowId: number,
  taskId: number,
  taskType: string,
): Promise<string> {
  const url =
    `${VT_BASE}/index.php?module=Workflows&parent=Settings` +
    `&view=EditTask&type=${taskType}&task_id=${taskId}&for_workflow=${workflowId}`;
  const html = await fetchWithAuth(url);
  saveDebugFile(workflowId, `task-${taskId}`, html);
  return html;
}

function parseTaskDetail(html: string, taskType: string): Record<string, unknown> {
  const details: Record<string, unknown> = {};

  // Extract all <input name="X" value="Y"> fields
  const formFields: Record<string, string> = {};
  const inputRegex = /<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"[^>]*/g;
  let match;
  while ((match = inputRegex.exec(html)) !== null) {
    const name = match[1];
    const value = match[2];
    // Skip framework/CSRF fields
    if (name.startsWith('__') || name === 'module' || name === 'parent' || name === 'action') {
      continue;
    }
    formFields[name] = value;
  }

  // Also try value="X" name="Y" order
  const inputRegex2 = /<input[^>]*value="([^"]*)"[^>]*name="([^"]+)"[^>]*/g;
  while ((match = inputRegex2.exec(html)) !== null) {
    const name = match[2];
    const value = match[1];
    if (name.startsWith('__') || name === 'module' || name === 'parent' || name === 'action') {
      continue;
    }
    if (!formFields[name]) formFields[name] = value;
  }

  // Email body — <textarea id="content">...</textarea>
  const bodyMatch = html.match(/<textarea[^>]*id=["']content["'][^>]*>([\s\S]*?)<\/textarea>/);
  if (bodyMatch) {
    formFields['_emailBody'] = bodyMatch[1].trim();
  }

  // ─── Classify by task type ─────────────────────────────────────────────

  if (taskType.includes('Email')) {
    details.type = 'Email';
    details.summary = formFields['summary'] || '';
    details.to = formFields['recepient'] || formFields['recipient'] || '';
    details.cc = formFields['emailcc'] || '';
    details.bcc = formFields['emailbcc'] || '';
    details.subject = formFields['subject'] || '';
    details.fromEmail = formFields['fromEmail'] || '';
    details.replyTo = formFields['replyTo'] || '';
    details.signature = formFields['signature'] || '';
    details.emailTracking = formFields['email_tracking'] || '';
    if (formFields['_emailBody']) {
      // Store a plain-text excerpt for docs (the full HTML is huge)
      const plainBody = stripHtml(formFields['_emailBody']);
      details.bodyExcerpt = plainBody.length > 500 ? plainBody.substring(0, 500) + '…' : plainBody;
      details.bodyHtml = formFields['_emailBody'];
    }
  } else if (taskType.includes('Webhook')) {
    details.type = 'Webhook';
    details.summary = formFields['summary'] || '';
    details.url = formFields['notify_url'] || formFields['url'] || formFields['webhookUrl'] || '';
    // HTTP method and content type are in <option selected> elements
    const methodMatch = html.match(/<select[^>]*id="[^"]*method[^"]*"[^>]*>[\s\S]*?selected[^>]*>([^<]+)/i);
    details.method = methodMatch ? methodMatch[1].trim() : '';
    const contentTypeMatch = html.match(/<select[^>]*id="[^"]*(?:content|data)[^"]*type[^"]*"[^>]*>[\s\S]*?selected[^>]*>([^<]+)/i);
    details.contentType = contentTypeMatch ? contentTypeMatch[1].trim() : '';
    // Also try simple selected option after method/content selects
    if (!details.method) {
      const simpleMethodMatch = html.match(/selected>(\s*(?:POST|GET|PUT|DELETE|PATCH)\s*)</i);
      if (simpleMethodMatch) details.method = simpleMethodMatch[1].trim();
    }
    // Field value mapping — JSON payload structure
    const fvmMatch = html.match(/id=["']fieldValueMapping["'][^>]*value='([^']*)'/);
    if (fvmMatch) {
      try {
        const decoded = decodeHtmlEntities(fvmMatch[1]);
        details.parameterMapping = JSON.parse(decoded);
      } catch {
        // ignore parse errors
      }
    }
  } else if (taskType.includes('UpdateFields')) {
    details.type = 'FieldUpdate';
    details.summary = formFields['summary'] || '';
    // Field update mappings are in JSON format in a hidden input
    const fvmMatch = html.match(
      /id=["']fieldValueMapping["'][^>]*value='([^']*)'/,
    );
    if (fvmMatch) {
      try {
        const decoded = decodeHtmlEntities(fvmMatch[1]);
        details.fieldUpdates = JSON.parse(decoded);
      } catch {
        details.fieldUpdatesRaw = fvmMatch[1];
      }
    }
  } else if (taskType.includes('EntityMethod')) {
    details.type = 'CustomFunction';
    details.summary = formFields['summary'] || '';
    details.methodName = formFields['methodName'] || formFields['method_name'] || '';
  } else if (taskType.includes('CreateTodo')) {
    details.type = 'CreateTodo';
    details.summary = formFields['summary'] || '';
    details.todo = formFields['todo'] || '';
    details.priority = formFields['priority'] || '';
    details.status = formFields['status'] || '';
  } else if (taskType.includes('CreateEvent')) {
    details.type = 'CreateEvent';
    details.summary = formFields['summary'] || '';
    details.eventName = formFields['eventName'] || formFields['event_name'] || '';
    details.eventType = formFields['eventType'] || formFields['event_type'] || '';
  } else if (taskType.includes('CreateEntity')) {
    details.type = 'CreateEntity';
    details.summary = formFields['summary'] || '';
    details.entityType = formFields['entity_type'] || formFields['entityType'] || '';
  } else if (taskType.includes('PushNotification')) {
    details.type = 'PushNotification';
    details.summary = formFields['summary'] || '';
  } else {
    details.type = taskType;
    details.summary = formFields['summary'] || '';
  }

  // Include all form fields for reference (exclude email body HTML to save space)
  const cleanFields = { ...formFields };
  delete cleanFields['_emailBody'];
  details._formFields = cleanFields;

  return details;
}

// ─── Workflow Enrichment ─────────────────────────────────────────────────────

async function enrichWorkflow(wf: WorkflowListEntry): Promise<WorkflowDetail> {
  const errors: string[] = [];
  let internalModule = getInternalModuleName(wf.module);
  let conditionsParsed: WorkflowCondition[] = [];
  let tasks: WorkflowTask[] = [];
  let description = '';

  // 1. Fetch edit page — get description + confirm internal module name
  try {
    const editHtml = await fetchEditPage(wf.id);
    description = extractDescriptionFromEditPage(editHtml);

    const pageModule = extractModuleFromEditPage(editHtml);
    if (pageModule) {
      internalModule = pageModule;
    }
  } catch (err) {
    const msg = `Edit page failed: ${(err as Error).message}`;
    console.warn(`  ⚠ ${msg}`);
    errors.push(msg);
  }

  // 2. Fetch conditions page — get structured conditions + task list
  await sleep(DELAY_MS);
  let taskRefs: TaskRef[] = [];
  try {
    const result = await fetchConditionsPage(wf.id, internalModule, wf.workflowType);
    conditionsParsed = result.conditions;
    taskRefs = result.taskRefs;
    if (conditionsParsed.length > 0) {
      console.log(`    ${conditionsParsed.length} condition(s)`);
    }
    if (taskRefs.length > 0) {
      console.log(`    ${taskRefs.length} task(s): ${taskRefs.map((t) => t.taskType.replace('VT', '')).join(', ')}`);
    }
  } catch (err) {
    const msg = `Conditions page failed: ${(err as Error).message}`;
    console.warn(`  ⚠ ${msg}`);
    errors.push(msg);
  }

  // 3. Fetch detail for each task
  for (const taskRef of taskRefs) {
    await sleep(DELAY_MS);
    try {
      const taskHtml = await fetchTaskDetail(wf.id, taskRef.id, taskRef.taskType);
      const details = parseTaskDetail(taskHtml, taskRef.taskType);
      tasks.push({
        id: taskRef.id,
        title: taskRef.title,
        taskType: taskRef.taskType,
        active: taskRef.active,
        details,
      });
    } catch (err) {
      const msg = `Task ${taskRef.id} (${taskRef.taskType}) failed: ${(err as Error).message}`;
      console.warn(`    ⚠ ${msg}`);
      errors.push(msg);
      tasks.push({
        id: taskRef.id,
        title: taskRef.title,
        taskType: taskRef.taskType,
        active: taskRef.active,
        details: { error: msg },
      });
    }
  }

  return {
    ...wf,
    internalModule,
    conditionsParsed,
    tasks,
    description,
    scrapeErrors: errors,
  };
}

// ─── Documentation Generation ────────────────────────────────────────────────

const TASK_TYPE_LABELS: Record<string, string> = {
  VTEmailTask: 'Send Email',
  VTWebhook: 'Webhook',
  VTUpdateFieldsTask: 'Update Fields',
  VTEntityMethodTask: 'Custom Function',
  VTCreateTodoTask: 'Create To-do',
  VTCreateEventTask: 'Create Event',
  VTCreateEntityTask: 'Create Record',
  VTPushNotificationTask: 'Push Notification',
};

function generateDocs(workflows: WorkflowDetail[]): string {
  const lines: string[] = [];

  lines.push('# Vtiger Workflow Reference');
  lines.push('');
  lines.push(
    `> Auto-generated on ${new Date().toLocaleDateString('en-AU', { day: '2-digit', month: 'short', year: 'numeric' })} by \`scripts/scrape-vtiger-workflow-details.ts\``,
  );
  lines.push(`> ${workflows.length} workflows documented`);
  lines.push('');

  // Group by module
  const byModule: Record<string, WorkflowDetail[]> = {};
  for (const wf of workflows) {
    if (!byModule[wf.module]) byModule[wf.module] = [];
    byModule[wf.module].push(wf);
  }

  // Table of contents
  lines.push('## Contents');
  lines.push('');
  for (const mod of Object.keys(byModule).sort()) {
    const count = byModule[mod].length;
    const anchor = mod.toLowerCase().replace(/\s+/g, '-');
    lines.push(`- [${mod}](#${anchor}) (${count})`);
  }
  lines.push('');
  lines.push('---');
  lines.push('');

  // Each module section
  for (const mod of Object.keys(byModule).sort()) {
    const moduleWorkflows = byModule[mod];
    lines.push(`## ${mod}`);
    lines.push('');

    for (const wf of moduleWorkflows.sort((a, b) => a.name.localeCompare(b.name))) {
      const status = wf.enabled ? 'Enabled' : 'Disabled';
      lines.push(`### ${wf.name}`);
      lines.push('');

      // Summary table
      lines.push('| Property | Value |');
      lines.push('|----------|-------|');
      lines.push(`| **ID** | ${wf.id} |`);
      lines.push(`| **Module** | ${wf.module} |`);
      lines.push(`| **Trigger** | ${wf.trigger} |`);
      lines.push(`| **Type** | ${wf.workflowType} |`);
      lines.push(`| **Status** | ${status} |`);
      if (wf.description) {
        lines.push(`| **Description** | ${wf.description} |`);
      }
      lines.push(`| **Edit** | [Open in Vtiger](${wf.editUrl}) |`);
      lines.push('');

      // Conditions
      if (wf.conditionsParsed.length > 0) {
        lines.push('**Conditions:**');
        lines.push('');
        let currentGroup = -1;
        for (const cond of wf.conditionsParsed) {
          if (cond.groupid !== currentGroup) {
            currentGroup = cond.groupid;
            const joinLabel = cond.groupjoin === 'or' ? 'Any' : 'All';
            lines.push(`*${joinLabel} of:*`);
          }
          const val = cond.value != null ? ` \`${cond.value}\`` : '';
          lines.push(`- \`${cond.fieldname}\` ${cond.operation}${val}`);
        }
        lines.push('');
      } else if (wf.conditions) {
        lines.push('**Conditions:**');
        lines.push('');
        lines.push('```');
        lines.push(wf.conditions);
        lines.push('```');
        lines.push('');
      }

      // Tasks/Actions
      if (wf.tasks.length > 0) {
        lines.push('**Actions:**');
        lines.push('');
        for (let i = 0; i < wf.tasks.length; i++) {
          const task = wf.tasks[i];
          const taskLabel = TASK_TYPE_LABELS[task.taskType] || task.taskType;
          const activeLabel = task.active ? '' : ' *(inactive)*';
          lines.push(`${i + 1}. **${taskLabel}**${activeLabel} — ${task.title}`);

          const d = task.details;
          if (d.type === 'Email') {
            if (d.to) lines.push(`   - **To:** \`${d.to}\``);
            if (d.cc) lines.push(`   - **CC:** \`${d.cc}\``);
            if (d.fromEmail) lines.push(`   - **From:** \`${d.fromEmail}\``);
            if (d.subject) lines.push(`   - **Subject:** ${d.subject}`);
            if (d.bodyExcerpt) {
              lines.push(`   - **Body excerpt:** ${(d.bodyExcerpt as string).substring(0, 300).replace(/\n/g, ' ')}`);
            }
          } else if (d.type === 'Webhook') {
            if (d.url) lines.push(`   - **URL:** \`${d.url}\``);
            if (d.method) lines.push(`   - **Method:** ${d.method}`);
            if (d.contentType) lines.push(`   - **Content-Type:** ${d.contentType}`);
            if (Array.isArray(d.parameterMapping)) {
              lines.push('   - **Parameters:**');
              for (const p of d.parameterMapping as Array<{ fieldname: string; value: string }>) {
                lines.push(`     - \`${p.fieldname}\` ← \`${p.value}\``);
              }
            }
          } else if (d.type === 'FieldUpdate') {
            if (Array.isArray(d.fieldUpdates)) {
              for (const u of d.fieldUpdates as Array<{ fieldname: string; value: string }>) {
                lines.push(`   - \`${u.fieldname}\` → \`${u.value}\``);
              }
            }
          } else if (d.type === 'CustomFunction') {
            if (d.methodName) lines.push(`   - **Method:** \`${d.methodName}\``);
          }
          lines.push('');
        }
      } else if (wf.actions.length > 0) {
        lines.push('**Actions:** ' + wf.actions.join(', '));
        lines.push('');
      }

      lines.push('---');
      lines.push('');
    }
  }

  return lines.join('\n');
}

// ─── Main ────────────────────────────────────────────────────────────────────

async function main() {
  console.log('Vtiger Workflow Detail Scraper');
  console.log('=============================');

  if (!fs.existsSync(INPUT_FILE)) {
    console.error(`Error: ${INPUT_FILE} not found.`);
    console.error('Run the list scraper first:');
    console.error('  VT_SESSION=<cookie> npx tsx scripts/scrape-vtiger-workflows.ts');
    process.exit(1);
  }

  const inputData = JSON.parse(fs.readFileSync(INPUT_FILE, 'utf-8'));
  let workflows: WorkflowListEntry[] = inputData.workflows;
  console.log(`Loaded ${workflows.length} workflows from list data`);

  // Deduplicate by ID (list scraper may produce duplicates across pages)
  const seen = new Set<number>();
  workflows = workflows.filter((w) => {
    if (seen.has(w.id)) return false;
    seen.add(w.id);
    return true;
  });
  console.log(`${workflows.length} unique workflows after dedup`);

  if (SINGLE_WORKFLOW) {
    const id = parseInt(SINGLE_WORKFLOW, 10);
    workflows = workflows.filter((w) => w.id === id);
    if (workflows.length === 0) {
      console.error(`Workflow ${SINGLE_WORKFLOW} not found in list data`);
      process.exit(1);
    }
    console.log(`Filtering to workflow ${id}: ${workflows[0].name}`);
  }

  if (DEBUG) {
    console.log(`Debug mode: raw HTML saved to ${DEBUG_DIR}`);
  }

  const enriched: WorkflowDetail[] = [];
  let totalTasks = 0;
  let totalConditions = 0;
  let errorCount = 0;

  for (let i = 0; i < workflows.length; i++) {
    const wf = workflows[i];
    console.log(`\n[${i + 1}/${workflows.length}] ${wf.name} (id=${wf.id}, ${wf.module})`);

    try {
      const detail = await enrichWorkflow(wf);
      enriched.push(detail);
      totalTasks += detail.tasks.length;
      totalConditions += detail.conditionsParsed.length;
      errorCount += detail.scrapeErrors.length;
    } catch (err) {
      console.error(`  ✗ Failed: ${(err as Error).message}`);
      errorCount++;
      enriched.push({
        ...wf,
        internalModule: getInternalModuleName(wf.module),
        conditionsParsed: [],
        tasks: [],
        description: '',
        scrapeErrors: [(err as Error).message],
      });
    }

    if (i < workflows.length - 1) await sleep(DELAY_MS);
  }

  // Summary
  console.log('\n=============================');
  console.log(`Enriched: ${enriched.length} workflows`);
  console.log(`Conditions: ${totalConditions}`);
  console.log(`Tasks: ${totalTasks}`);
  if (errorCount > 0) {
    console.log(`Errors: ${errorCount}`);
  }

  // Write enriched JSON (strip bodyHtml to keep file manageable)
  const outputWorkflows = enriched.map((wf) => ({
    ...wf,
    tasks: wf.tasks.map((t) => {
      const { _formFields, bodyHtml, ...cleanDetails } = t.details as Record<string, unknown>;
      return { ...t, details: cleanDetails };
    }),
  }));

  const output = {
    generated: new Date().toISOString(),
    count: outputWorkflows.length,
    workflows: outputWorkflows,
  };
  fs.writeFileSync(OUTPUT_FILE, JSON.stringify(output, null, 2));
  console.log(`\nWritten to ${OUTPUT_FILE}`);

  // Generate documentation
  const docs = generateDocs(enriched);
  fs.writeFileSync(DOCS_FILE, docs);
  console.log(`Docs written to ${DOCS_FILE}`);
}

main().catch((err) => {
  console.error('Scraper failed:', err);
  process.exit(1);
});
