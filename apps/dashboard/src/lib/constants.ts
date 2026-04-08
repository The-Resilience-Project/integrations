export const AWS_REGION = 'ap-southeast-2';
export const AWS_PROFILE = 'trp-integrations';
export const SERVICE_PREFIX = 'trp-api-dev-';

export const FUNCTION_NAMES = [
  'enquiry',
  'qualify',
  'confirm_existing_schools',
  'seminar_registration',
  'order_resources',
  'school_confirmation_form_details',
  'prize_pack',
  'register',
  'confirm',
  'accept_dates',
  'ey_confirmation_form_details',
  'calculate_shipping',
  'calendly_event',
  'school_ltrp_details',
  'submit_ca',
  'school_curric_ordering_details',
  'order_resources_2026',
  'createShipment',
  'createInvoice',
  'updateXeroCodeInvoiceItem',
  'create_shipment_2025',
  'createNewProgramBooking',
  'getEventPlanned',
  'sendInvitation',
  'v2_schools_enquiry',
  'v2_schools_more_info',
  'v2_schools_prize_pack',
  'woocommerce_order',
] as const;

export type FunctionName = (typeof FUNCTION_NAMES)[number];

export const TIME_RANGES = [
  { label: 'Last 1 hour', value: '1h' },
  { label: 'Last 6 hours', value: '6h' },
  { label: 'Last 24 hours', value: '24h' },
  { label: 'Last 7 days', value: '7d' },
  { label: 'Last 30 days', value: '30d' },
  { label: 'Last 90 days', value: '90d' },
  { label: 'Last 6 months', value: '6mo' },
  { label: 'Last 1 year', value: '1y' },
] as const;

const HOUR = 60 * 60 * 1000;
const DAY = 24 * HOUR;

export function getTimeRangeMs(range: string): number {
  switch (range) {
    case '1h': return HOUR;
    case '6h': return 6 * HOUR;
    case '24h': return DAY;
    case '7d': return 7 * DAY;
    case '30d': return 30 * DAY;
    case '90d': return 90 * DAY;
    case '6mo': return 182 * DAY;
    case '1y': return 365 * DAY;
    default: return DAY;
  }
}

export function getPeriodSeconds(range: string): number {
  switch (range) {
    case '1h':
    case '6h':
    case '24h':
      return 300;       // 5 min
    case '7d':
      return 3600;      // 1 hour
    case '30d':
      return 21600;     // 6 hours
    case '90d':
    case '6mo':
      return 86400;     // 1 day
    case '1y':
      return 86400;     // 1 day
    default:
      return 300;
  }
}
