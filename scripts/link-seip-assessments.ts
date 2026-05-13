/**
 * Backfill the SEIP ↔ W&C Assessment many-to-many.
 *
 * For each SEIP, reads the legacy 1-1 reference field
 *   cf_vtcmseip_wellbeingcultureassessment
 * and adds that assessment to the new "Wellbeing and Culture Assessments"
 * related list (the many-to-many tab) if not already linked.
 *
 * Defaults to DRY-RUN. Pass --apply to actually call add_related.
 *
 * Usage:
 *   # dry-run for one SEIP (recommended first)
 *   SEIP_IDS=93x818531 npx tsx scripts/link-seip-assessments.ts
 *
 *   # actually link, just for that SEIP
 *   SEIP_IDS=93x818531 npx tsx scripts/link-seip-assessments.ts --apply
 *
 *   # dry-run across all SEIPs (after the test passes)
 *   npx tsx scripts/link-seip-assessments.ts
 *
 *   # full backfill
 *   npx tsx scripts/link-seip-assessments.ts --apply
 *
 * Env (loaded from scripts/.env):
 *   VT_URL, VT_USERNAME, VT_ACCESSKEY   required
 *   SEIP_MODULE                         required (e.g. vtcmseip)
 *   ASSESSMENT_MODULE                   required (e.g. vtcmassessments)
 *   RELATED_LABEL                       default: "Wellbeing and Culture Assessments"
 *   LEGACY_FIELD                        default: cf_vtcmseip_wellbeingcultureassessment
 *   SEIP_IDS                            optional comma-separated list to scope
 *   THROTTLE_MS                         default: 250
 */

import { VtigerClient, requireEnv, sleep } from './lib/vtiger';

interface ActionLog {
  seipId: string;
  legacyAssessmentId: string;
  action: 'linked' | 'already-linked' | 'no-legacy' | 'error';
  detail: string;
}

function pickStr(record: Record<string, unknown>, ...keys: string[]): string {
  for (const key of keys) {
    const v = record[key];
    if (typeof v === 'string' && v) return v;
  }
  return '';
}

async function main(): Promise<void> {
  const apply = process.argv.includes('--apply');
  const client = await VtigerClient.login(
    requireEnv('VT_URL'),
    requireEnv('VT_USERNAME'),
    requireEnv('VT_ACCESSKEY'),
  );

  const seipModule = requireEnv('SEIP_MODULE');
  const assessmentModule = requireEnv('ASSESSMENT_MODULE');
  const relatedLabel = (
    process.env.RELATED_LABEL || 'Wellbeing and Culture Assessments'
  ).trim();
  const legacyField = (
    process.env.LEGACY_FIELD || 'cf_vtcmseip_wellbeingcultureassessment'
  ).trim();
  const throttleMs = Number(process.env.THROTTLE_MS ?? '250');
  const scope = (process.env.SEIP_IDS || '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);

  console.error(`Mode: ${apply ? 'APPLY (writes to vTiger)' : 'DRY-RUN (no writes)'}`);
  console.error(`Legacy field: ${legacyField}`);
  console.error(`Related label: "${relatedLabel}"`);

  const noFilter = process.argv.includes('--no-filter');

  let seips: Record<string, unknown>[];
  if (scope.length > 0) {
    console.error(`Scoping to ${scope.length} SEIP(s) by id...`);
    const idList = scope.map((id) => `'${id.replace(/'/g, '')}'`).join(',');
    seips = await client.query(
      `SELECT * FROM ${seipModule} WHERE id IN (${idList});`,
    );
  } else if (noFilter) {
    console.error(`Fetching all ${seipModule} (no server-side filter)...`);
    seips = await client.fetchAll(seipModule);
    seips = seips.filter((s) => pickStr(s, legacyField));
    console.error(`  filtered to those with ${legacyField} populated`);
  } else {
    console.error(`Fetching ${seipModule} where ${legacyField} IS NOT NULL...`);
    try {
      seips = await client.fetchAll(seipModule, `${legacyField} IS NOT NULL`);
    } catch (err) {
      console.error(
        `  WHERE clause failed (${err instanceof Error ? err.message : err})`,
      );
      console.error(`  falling back to fetch-all + client-side filter...`);
      seips = await client.fetchAll(seipModule);
      seips = seips.filter((s) => pickStr(s, legacyField));
    }
  }
  console.error(`  ${seips.length} SEIP(s) to process`);

  const log: ActionLog[] = [];

  for (const [i, seip] of seips.entries()) {
    if (i > 0 && throttleMs > 0) await sleep(throttleMs);
    const seipId = pickStr(seip, 'id');
    const legacyAssessmentId = pickStr(seip, legacyField);

    if (!legacyAssessmentId) {
      log.push({
        seipId,
        legacyAssessmentId: '',
        action: 'no-legacy',
        detail: `${legacyField} is empty`,
      });
      continue;
    }

    let related: Record<string, unknown>[];
    try {
      related = await client.retrieveRelated(seipId, relatedLabel, assessmentModule);
    } catch (err) {
      log.push({
        seipId,
        legacyAssessmentId,
        action: 'error',
        detail: `retrieveRelated: ${err instanceof Error ? err.message : err}`,
      });
      continue;
    }

    const linkedIds = new Set(related.map((r) => pickStr(r, 'id')).filter(Boolean));
    if (linkedIds.has(legacyAssessmentId)) {
      log.push({
        seipId,
        legacyAssessmentId,
        action: 'already-linked',
        detail: '',
      });
      continue;
    }

    if (!apply) {
      log.push({
        seipId,
        legacyAssessmentId,
        action: 'linked',
        detail: 'DRY-RUN — would call add_related',
      });
      continue;
    }

    try {
      await client.addRelated(seipId, legacyAssessmentId);
      log.push({
        seipId,
        legacyAssessmentId,
        action: 'linked',
        detail: 'add_related ok',
      });
    } catch (err) {
      log.push({
        seipId,
        legacyAssessmentId,
        action: 'error',
        detail: `add_related: ${err instanceof Error ? err.message : err}`,
      });
    }

    if ((i + 1) % 50 === 0 || i + 1 === seips.length) {
      console.error(`  ${i + 1}/${seips.length}`);
    }
  }

  console.log(['seip_id', 'legacy_assessment_id', 'action', 'detail'].join('\t'));
  for (const r of log) {
    console.log([r.seipId, r.legacyAssessmentId, r.action, r.detail].join('\t'));
  }

  const counts = log.reduce<Record<string, number>>((acc, r) => {
    acc[r.action] = (acc[r.action] ?? 0) + 1;
    return acc;
  }, {});
  console.error(
    `\nSummary: ${Object.entries(counts)
      .map(([k, v]) => `${k}=${v}`)
      .join(', ')}`,
  );
  if (!apply) {
    console.error('Dry-run only — re-run with --apply to write changes.');
  }
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exit(1);
});
