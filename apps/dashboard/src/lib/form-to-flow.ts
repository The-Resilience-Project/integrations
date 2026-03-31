/**
 * Mapping of Gravity Forms form IDs to their related business flows.
 *
 * Delegates to the central pipeline-map.ts model. This file preserves
 * the original `getFlowForForm` signature for backward compatibility.
 */

import { getPipelineForForm, type PipelineEntry } from './pipeline-map';

export interface FormFlowLink {
  slug: string;
  title: string;
  description: string;
}

/**
 * Per-form descriptions that add context beyond what PipelineEntry provides
 * (e.g. which year version the form belongs to).
 */
const FORM_DESCRIPTIONS: Record<number, string> = {
  53: 'Conference enquiry form — same CRM flow as standard school enquiry',
  72: 'Ambassador, teacher, or parent confirms event attendance',
  76: '2025 school program confirmation — deal, quote, SEIP creation',
  80: '2026 school program confirmation — deal, quote, SEIP creation',
  63: '2025 curriculum resource ordering — invoice creation',
  89: '2026 curriculum resource ordering — invoice creation',
  70: 'School accepts proposed event dates',
  86: 'Assessment with domain scores linked to SEIP',
  29: 'Early Years program confirmation',
};

function pipelineToFlowLink(
  pipeline: PipelineEntry,
  formId: number,
): FormFlowLink {
  return {
    slug: pipeline.flowSlug,
    title: pipeline.label + ' Flow',
    description:
      FORM_DESCRIPTIONS[formId] ?? `${pipeline.label} — end-to-end CRM flow`,
  };
}

export function getFlowForForm(formId: number): FormFlowLink | null {
  const pipeline = getPipelineForForm(formId);
  if (!pipeline) return null;
  return pipelineToFlowLink(pipeline, formId);
}
