/**
 * Inbound endpoint overrides for Gravity Forms.
 *
 * These define the API endpoints that pre-populate form fields before rendering.
 * They're configured as WordPress gform_pre_render hooks in the child theme
 * (forms/hello-theme-child-master/functions.php) and can't be fetched from
 * the GF REST API — so they're maintained here manually.
 *
 * Outbound endpoints (form → API webhooks) come live from the GF Feeds API.
 */

export interface InboundOverride {
  endpoint: string;
  trigger: string;
  fieldMappings: {
    apiParam: string;
    formFieldLabel: string;
    formInput: string;
    note?: string;
  }[];
}

export const INBOUND_OVERRIDES: Record<number, InboundOverride> = {
  76: {
    endpoint: 'GET /api/school_confirmation_form_details.php',
    trigger: 'gform_pre_render_76 (page 2, jQuery AJAX)',
    fieldMappings: [
      { apiParam: 'deal_status', formFieldLabel: 'Deal Confirmed', formInput: 'input_76_183', note: "'Deal Won'|'Closed INV' → 'YES', else 'NO'" },
    ],
  },
  80: {
    endpoint: 'GET /api/school_confirmation_form_details.php',
    trigger: 'gform_pre_render_80 (page 2, jQuery AJAX)',
    fieldMappings: [
      { apiParam: 'deal_status', formFieldLabel: 'Deal Confirmed', formInput: 'input_80_49', note: "'Deal Won'|'Closed INV' → 'YES', else 'NO'" },
      { apiParam: 'free_travel', formFieldLabel: 'Free Travel', formInput: 'input_80_61', note: "'1' → 'YES', else 'NO'" },
      { apiParam: 'f2f', formFieldLabel: 'Face to Face', formInput: 'input_80_62', note: "truthy → 'YES', else 'NO'" },
      { apiParam: 'funded_years', formFieldLabel: 'Funded 2026', formInput: 'input_80_118', note: "includes '2026' → 'YES', else 'NO'" },
    ],
  },
  29: {
    endpoint: 'GET /api/ey_confirmation_form_details.php',
    trigger: 'gform_pre_render_29 (page 2, jQuery AJAX)',
    fieldMappings: [
      { apiParam: 'deal_status', formFieldLabel: 'Deal Confirmed', formInput: 'input_29_183', note: "'Deal Won'|'Closed INV' → 'YES', else 'NO'" },
    ],
  },
  86: {
    endpoint: 'GET /api/school_ltrp_details.php',
    trigger: 'gform_pre_render_86 (page 2, server-side curl)',
    fieldMappings: [
      { apiParam: 'state', formFieldLabel: 'State', formInput: 'input_14' },
      { apiParam: 'name', formFieldLabel: 'Organisation Name', formInput: 'input_3' },
      { apiParam: 'id', formFieldLabel: 'Org ID', formInput: 'input_67' },
      { apiParam: 'ltrp', formFieldLabel: 'LTRP', formInput: 'input_10', note: "truthy → 'YES', else 'NO'" },
      { apiParam: 'ca', formFieldLabel: 'Culture Assessment', formInput: 'input_85', note: "truthy → 'YES', else 'NO'" },
      { apiParam: 'participants', formFieldLabel: 'Participants', formInput: 'input_89' },
    ],
  },
  63: {
    endpoint: 'GET /api/school_curric_ordering_details.php',
    trigger: 'gform_pre_render_63 (page 2, jQuery AJAX)',
    fieldMappings: [],
  },
  89: {
    endpoint: 'GET /api/school_curric_ordering_details.php?for_2026=1',
    trigger: 'gform_pre_render_89 (page 2, jQuery AJAX)',
    fieldMappings: [],
  },
  70: {
    endpoint: 'Vtiger VTAP direct (populate_dates.php)',
    trigger: 'gform_pre_render_70 (page 2, server-side)',
    fieldMappings: [],
  },
  72: {
    endpoint: 'Vtiger VTAP direct (populate_event_date.php)',
    trigger: 'gform_pre_render_72 (page 2, server-side)',
    fieldMappings: [],
  },
  52: {
    endpoint: 'GET /Potentials/getEventPlanned.php',
    trigger: 'gform_pre_render_52 (client-side jQuery AJAX)',
    fieldMappings: [],
  },
  61: {
    endpoint: 'GET /Potentials/getEventPlanned.php?type=leading-trp',
    trigger: 'gform_pre_render_61 (server-side curl)',
    fieldMappings: [],
  },
  67: {
    endpoint: 'GET /Potentials/getEventPlanned.php?type=early-year',
    trigger: 'gform_pre_render_67 (server-side curl)',
    fieldMappings: [],
  },
  69: {
    endpoint: 'Sendle API (calculate_shipping.php)',
    trigger: 'gform_pre_render_69 (page 2, server-side)',
    fieldMappings: [],
  },
};
