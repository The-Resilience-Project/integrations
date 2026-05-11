/**
 * Shared helpers for one-off scripts that talk to vTiger's webservice API.
 *
 * Auto-loads `scripts/.env` (KEY=VALUE; existing process.env wins) and
 * exposes auth + paged-query helpers. Stdlib only — no external deps.
 */

import * as crypto from 'crypto';
import * as fs from 'fs';
import * as path from 'path';

const PAGE_SIZE = 100; // vTiger query op caps results at 100 per request

function loadDotenv(filePath: string): void {
  if (!fs.existsSync(filePath)) return;
  for (const raw of fs.readFileSync(filePath, 'utf8').split(/\r?\n/)) {
    const line = raw.trim();
    if (!line || line.startsWith('#')) continue;
    const eq = line.indexOf('=');
    if (eq === -1) continue;
    const key = line.slice(0, eq).trim();
    let value = line.slice(eq + 1).trim();
    if (
      (value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'"))
    ) {
      value = value.slice(1, -1);
    }
    if (process.env[key] === undefined) {
      process.env[key] = value;
    }
  }
}

loadDotenv(path.join(__dirname, '..', '.env'));

export function requireEnv(name: string): string {
  const value = (process.env[name] || '').trim();
  if (!value) {
    console.error(`Error: ${name} environment variable is required.`);
    process.exit(2);
  }
  return value;
}

interface VtigerResponse<T> {
  success: boolean;
  result?: T;
  error?: { code: string; message: string };
}

export class VtigerClient {
  private endpoint: string;
  private session = '';

  constructor(baseUrl: string) {
    this.endpoint = baseUrl.replace(/\/$/, '') + '/webservice.php';
  }

  static async login(
    baseUrl: string,
    username: string,
    accessKey: string,
  ): Promise<VtigerClient> {
    const client = new VtigerClient(baseUrl);
    const challenge = await client.call<{ token: string }>(
      `${client.endpoint}?operation=getchallenge&username=${encodeURIComponent(username)}`,
    );
    const accessHash = crypto
      .createHash('md5')
      .update(challenge.token + accessKey)
      .digest('hex');
    const login = await client.call<{ sessionName: string }>(client.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        operation: 'login',
        username,
        accessKey: accessHash,
      }).toString(),
    });
    client.session = login.sessionName;
    return client;
  }

  async listModules(): Promise<string[]> {
    const result = await this.call<{ types: string[] }>(
      `${this.endpoint}?operation=listtypes&sessionName=${this.session}`,
    );
    return result.types ?? [];
  }

  async query(vtql: string): Promise<Record<string, unknown>[]> {
    return this.call<Record<string, unknown>[]>(
      `${this.endpoint}?operation=query&sessionName=${this.session}` +
        `&query=${encodeURIComponent(vtql)}`,
    );
  }

  async fetchAll(module: string, where = ''): Promise<Record<string, unknown>[]> {
    const results: Record<string, unknown>[] = [];
    let offset = 0;
    const whereClause = where ? ` WHERE ${where}` : '';
    while (true) {
      const query = `SELECT * FROM ${module}${whereClause} LIMIT ${offset},${PAGE_SIZE};`;
      const page = await this.call<Record<string, unknown>[]>(
        `${this.endpoint}?operation=query&sessionName=${this.session}` +
          `&query=${encodeURIComponent(query)}`,
      );
      results.push(...page);
      if (page.length < PAGE_SIZE) return results;
      offset += PAGE_SIZE;
    }
  }

  async retrieveRelated(
    parentId: string,
    relatedLabel: string,
    relatedType: string,
  ): Promise<Record<string, unknown>[]> {
    const all: Record<string, unknown>[] = [];
    let page = 1;
    while (true) {
      const url =
        `${this.endpoint}?operation=retrieve_related&sessionName=${this.session}` +
        `&id=${encodeURIComponent(parentId)}` +
        `&relatedLabel=${encodeURIComponent(relatedLabel)}` +
        `&relatedType=${encodeURIComponent(relatedType)}` +
        `&page=${page}`;
      const records = await this.call<Record<string, unknown>[]>(url);
      all.push(...records);
      if (records.length < PAGE_SIZE) return all;
      page += 1;
    }
  }

  async addRelated(
    sourceRecordId: string,
    relatedRecordId: string,
    relationIdLabel = '',
  ): Promise<Record<string, unknown>> {
    return this.call<Record<string, unknown>>(this.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        operation: 'add_related',
        sessionName: this.session,
        sourceRecordId,
        relatedRecordId,
        relationIdLabel,
      }).toString(),
    });
  }

  async describe(module: string): Promise<Record<string, unknown>> {
    return this.call<Record<string, unknown>>(
      `${this.endpoint}?operation=describe&sessionName=${this.session}` +
        `&elementType=${encodeURIComponent(module)}`,
    );
  }

  private async call<T>(url: string, init: RequestInit = {}): Promise<T> {
    const maxRetries = 6;
    let attempt = 0;
    while (true) {
      const resp = await fetch(url, init);

      if (resp.status === 429 && attempt < maxRetries) {
        const retryAfter = Number(resp.headers.get('retry-after')) || 0;
        const backoff = retryAfter > 0 ? retryAfter * 1000 : 1000 * 2 ** attempt;
        await sleep(backoff);
        attempt += 1;
        continue;
      }

      const text = await resp.text();
      let payload: VtigerResponse<T>;
      try {
        payload = JSON.parse(text) as VtigerResponse<T>;
      } catch {
        throw new Error(`vTiger non-JSON response (${resp.status}): ${text.slice(0, 200)}`);
      }

      if (!payload.success) {
        const code = payload.error?.code ?? '';
        if (code === 'TOO_MANY_REQUESTS' && attempt < maxRetries) {
          await sleep(1000 * 2 ** attempt);
          attempt += 1;
          continue;
        }
        throw new Error(`vTiger API error: ${JSON.stringify(payload.error)}`);
      }
      return payload.result as T;
    }
  }
}

export function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Run `--list-modules` mode if the flag is present. Returns true when handled
 * so callers can short-circuit.
 */
export async function maybeListModules(client: VtigerClient): Promise<boolean> {
  if (!process.argv.includes('--list-modules')) return false;
  const modules = await client.listModules();
  console.log(JSON.stringify(modules, null, 2));
  console.error(`Total modules: ${modules.length}`);
  return true;
}

/**
 * Wrap a fetchAll call with a discovery fallback: on access-denied / not-found
 * errors, list modules and suggest matches by `nameHint` regex.
 */
export async function fetchAllOrSuggest(
  client: VtigerClient,
  module: string,
  nameHint: RegExp,
  envVarName: string,
  scriptPath: string,
): Promise<Record<string, unknown>[]> {
  try {
    return await client.fetchAll(module);
  } catch (err) {
    const msg = err instanceof Error ? err.message : String(err);
    if (!msg.includes('ACCESS_DENIED') && !msg.includes('not found')) throw err;

    const modules = await client.listModules();
    const matches = modules.filter((m) => nameHint.test(m));
    console.error(`\nCouldn't query module "${module}".`);
    if (matches.length) {
      console.error(`Possible matches: ${matches.join(', ')}`);
      console.error(`Re-run with: ${envVarName}=<name> npx tsx ${scriptPath}`);
    } else {
      console.error(
        `No modules matched ${nameHint}. Run with --list-modules to see all accessible modules.`,
      );
    }
    process.exit(1);
  }
}
