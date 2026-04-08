/**
 * Central data model connecting all four layers of the form-to-CRM pipeline:
 * Gravity Forms → API Endpoints → VTAP Endpoints → CRM Workflows.
 *
 * Data transcribed from the Quick Reference tables in docs/flows/.
 */

export interface ApiEndpoint {
  version: 'v1' | 'v2';
  method: string;
  path: string;
  docSlug?: string;
}

export interface PipelineEntry {
  /** Human-readable label, e.g. "School Enquiry" */
  label: string;
  /** Which business journey this belongs to */
  journey: 'schools' | 'conference' | 'enquiries';
  /** Flow doc slug under docs/flows/ */
  flowSlug: string;
  /** Gravity Forms form IDs that feed into this pipeline */
  formIds: number[];
  /** API endpoints called (outbound from form webhook) */
  apiEndpoints: ApiEndpoint[];
  /** VTAP endpoints called by the API handler, in order */
  vtapEndpoints: string[];
  /** Vtiger workflow names triggered as a side-effect */
  workflowNames: string[];
}

export const PIPELINE_MAP: Record<string, PipelineEntry> = {
  /* ── Enquiry flows ─────────────────────────────────────────────── */

  enquiry: {
    label: 'School Enquiry',
    journey: 'enquiries',
    flowSlug: 'enquiry',
    formIds: [53],
    apiEndpoints: [
      { version: 'v2', method: 'POST', path: '/api/v2/schools/enquiry', docSlug: 'v2/schools' },
      { version: 'v1', method: 'POST', path: '/api/enquiry.php', docSlug: 'v1/enquiries' },
    ],
    vtapEndpoints: [
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'getOrCreateDeal',
      'createEnquiry',
    ],
    workflowNames: ['New enquiry -- send email to enquirer'],
  },

  'workplace-enquiry': {
    label: 'Workplace Enquiry',
    journey: 'enquiries',
    flowSlug: 'workplace-enquiry',
    formIds: [],
    apiEndpoints: [
      { version: 'v1', method: 'POST', path: '/api/enquiry.php', docSlug: 'v1/enquiries' },
    ],
    vtapEndpoints: [
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'getOrCreateDeal',
      'createEnquiry',
    ],
    workflowNames: ['New enquiry -- send email to enquirer'],
  },

  'early-years-enquiry': {
    label: 'Early Years Enquiry',
    journey: 'enquiries',
    flowSlug: 'early-years-enquiry',
    formIds: [],
    apiEndpoints: [
      { version: 'v1', method: 'POST', path: '/api/enquiry.php', docSlug: 'v1/enquiries' },
    ],
    vtapEndpoints: [
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'getOrCreateDeal',
      'createEnquiry',
    ],
    workflowNames: ['New enquiry -- send email to enquirer'],
  },

  'general-enquiry': {
    label: 'General Enquiry',
    journey: 'enquiries',
    flowSlug: 'general-enquiry',
    formIds: [],
    apiEndpoints: [
      { version: 'v1', method: 'POST', path: '/api/enquiry.php', docSlug: 'v1/enquiries' },
    ],
    vtapEndpoints: ['getContactByEmail', 'createEnquiry'],
    workflowNames: ['New enquiry -- send email to enquirer'],
  },

  /* ── Conference flows ──────────────────────────────────────────── */

  'conference-enquiry': {
    label: 'Conference Enquiry',
    journey: 'conference',
    flowSlug: 'conference-enquiry',
    formIds: [53],
    apiEndpoints: [
      { version: 'v2', method: 'POST', path: '/api/v2/schools/enquiry', docSlug: 'v2/schools' },
      { version: 'v1', method: 'POST', path: '/api/enquiry.php', docSlug: 'v1/enquiries' },
    ],
    vtapEndpoints: [
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'getOrCreateDeal',
      'createEnquiry',
    ],
    workflowNames: ['New enquiry -- send email to enquirer'],
  },

  'conference-delegate': {
    label: 'Conference Delegate Registration',
    journey: 'conference',
    flowSlug: 'conference-delegate',
    formIds: [],
    apiEndpoints: [
      { version: 'v2', method: 'POST', path: '/api/v2/schools/prize-pack', docSlug: 'v2/schools' },
      { version: 'v1', method: 'POST', path: '/api/prize_pack.php' },
    ],
    vtapEndpoints: [
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'updateOrganisation',
    ],
    workflowNames: [],
  },

  'conference-import': {
    label: 'Conference Import',
    journey: 'conference',
    flowSlug: 'conference-import',
    formIds: [],
    apiEndpoints: [
      { version: 'v1', method: 'POST', path: '/api/enquiry.php', docSlug: 'v1/enquiries' },
      { version: 'v1', method: 'POST', path: '/api/prize_pack.php' },
    ],
    vtapEndpoints: [
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'getOrCreateDeal',
      'createEnquiry',
    ],
    workflowNames: ['New enquiry -- send email to enquirer'],
  },

  'conference-prize-pack': {
    label: 'Conference Prize Pack',
    journey: 'conference',
    flowSlug: 'conference-prize-pack',
    formIds: [],
    apiEndpoints: [
      { version: 'v2', method: 'POST', path: '/api/v2/schools/prize-pack', docSlug: 'v2/schools' },
      { version: 'v1', method: 'POST', path: '/api/prize_pack.php' },
    ],
    vtapEndpoints: [
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'updateOrganisation',
    ],
    workflowNames: [],
  },

  /* ── School operations flows ───────────────────────────────────── */

  'more-info': {
    label: 'School More Info',
    journey: 'schools',
    flowSlug: 'more-info',
    formIds: [],
    apiEndpoints: [
      { version: 'v2', method: 'POST', path: '/api/v2/schools/more-info', docSlug: 'v2/schools' },
    ],
    vtapEndpoints: [
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'getOrCreateDeal',
      'getEventDetails',
      'checkContactRegisteredForEvent',
      'registerContact',
    ],
    workflowNames: [],
  },

  registration: {
    label: 'School Registration',
    journey: 'schools',
    flowSlug: 'registration',
    formIds: [],
    apiEndpoints: [
      { version: 'v2', method: 'POST', path: '/api/v2/schools/register', docSlug: 'v2/schools' },
      { version: 'v1', method: 'POST', path: '/api/register.php', docSlug: 'v1/registrations' },
    ],
    vtapEndpoints: [
      'getEventDetails',
      'setContactsInactive',
      'captureCustomerInfo',
      'getOrgDetails',
      'updateOrganisation',
      'updateContactById',
      'getOrCreateDeal',
      'updateDeal',
      'checkContactRegisteredForEvent',
      'registerContact',
    ],
    workflowNames: ['New enquiry -- send email to enquirer'],
  },

  'event-confirmation': {
    label: 'Event Confirmation',
    journey: 'schools',
    flowSlug: 'event-confirmation',
    formIds: [72],
    apiEndpoints: [
      { version: 'v2', method: 'POST', path: '/api/v2/schools/register', docSlug: 'v2/schools' },
      { version: 'v1', method: 'POST', path: '/api/register.php', docSlug: 'v1/registrations' },
    ],
    vtapEndpoints: [
      'getEventDetails',
      'getContactById',
      'captureCustomerInfo',
      'setContactsInactive',
      'getOrgDetails',
      'updateOrganisation',
      'createOrUpdateInvitation',
      'checkContactRegisteredForEvent',
      'registerContact',
    ],
    workflowNames: [],
  },

  'program-confirmation': {
    label: 'Program Confirmation',
    journey: 'schools',
    flowSlug: 'program-confirmation',
    formIds: [76, 80, 29],
    apiEndpoints: [
      { version: 'v1', method: 'POST', path: '/api/confirm.php', docSlug: 'v1/confirmations' },
    ],
    vtapEndpoints: [
      'captureCustomerInfo',
      'getOrCreateDeal',
      'getServices',
      'updateDeal',
      'setDealLineItems',
      'createQuote',
      'updateOrganisation',
      'createOrUpdateSEIP',
      'updateContactById',
    ],
    workflowNames: [],
  },

  'order-resources': {
    label: 'Order Resources',
    journey: 'schools',
    flowSlug: 'order-resources',
    formIds: [63, 89],
    apiEndpoints: [
      { version: 'v1', method: 'POST', path: '/api/order_resources.php' },
      { version: 'v1', method: 'POST', path: '/api/order_resources_2026.php' },
    ],
    vtapEndpoints: [
      'getQuoteWithAccountNo',
      'getDealDetailsFromAccountNo',
      'getOrgWithAccountNo',
      'getInvoicesFromAccountNo',
      'getServices',
      'getProducts',
      'createOrUpdateSEIP',
      'createInvoice',
    ],
    workflowNames: [],
  },

  'date-acceptance': {
    label: 'Date Acceptance',
    journey: 'schools',
    flowSlug: 'date-acceptance',
    formIds: [70],
    apiEndpoints: [
      { version: 'v1', method: 'POST', path: '/api/accept_dates.php' },
    ],
    vtapEndpoints: ['createDateAcceptance', 'updateDateAcceptance'],
    workflowNames: [],
  },

  assessment: {
    label: 'Wellbeing Culture Assessment',
    journey: 'schools',
    flowSlug: 'assessment',
    formIds: [86],
    apiEndpoints: [
      { version: 'v1', method: 'POST', path: '/api/submit_ca.php' },
    ],
    vtapEndpoints: [
      'getQuoteWithAccountNo',
      'createAssessment',
      'createOrUpdateSEIP',
    ],
    workflowNames: [],
  },
};

/* ── Helper functions ──────────────────────────────────────────── */

export function getPipelineForForm(formId: number): PipelineEntry | null {
  return (
    Object.values(PIPELINE_MAP).find((entry) =>
      entry.formIds.includes(formId),
    ) ?? null
  );
}

export function getPipelineByKey(key: string): PipelineEntry | null {
  return PIPELINE_MAP[key] ?? null;
}

export function getPipelinesForJourney(
  journey: PipelineEntry['journey'],
): PipelineEntry[] {
  return Object.values(PIPELINE_MAP).filter(
    (entry) => entry.journey === journey,
  );
}

export function getPipelinesForVtapEndpoint(name: string): PipelineEntry[] {
  return Object.values(PIPELINE_MAP).filter((entry) =>
    entry.vtapEndpoints.includes(name),
  );
}

export function getAllPipelines(): PipelineEntry[] {
  return Object.values(PIPELINE_MAP);
}

export function getAllPipelineEntries(): [string, PipelineEntry][] {
  return Object.entries(PIPELINE_MAP);
}
