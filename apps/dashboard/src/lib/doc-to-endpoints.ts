/**
 * Reverse mapping: doc slug → endpoint URL patterns that belong to it.
 * Used to find which forms call endpoints documented on a given doc page.
 */

const DOC_TO_ENDPOINTS: Record<string, string[]> = {
  'v1/enquiries': ['enquiry.php'],
  'v1/confirmations': ['confirm.php', 'confirm_existing_schools.php'],
  'v1/registrations': ['register.php', 'seminar_registration.php'],
  'v1/school-operations': [
    'accept_dates.php',
    'submit_ca.php',
    'order_resources.php',
    'order_resources_2026.php',
  ],
  'v1/workplace': ['qualify.php', 'calendly_event.php'],
  'v1/shipping': ['calculate_shipping.php'],
  'v1/prize-pack': ['prize_pack.php'],
  'v1/form-details': [
    'school_confirmation_form_details.php',
    'ey_confirmation_form_details.php',
    'school_ltrp_details.php',
    'school_curric_ordering_details.php',
  ],
  'v1/invoices': ['createInvoice', 'createShipment'],
  'v1/events': ['sendInvitation'],
  'v2/schools': [
    '/api/v2/schools/enquiry',
    '/api/v2/schools/register',
    '/api/v2/schools/prize-pack',
  ],
};

export function getEndpointPatternsForDoc(slug: string): string[] {
  return DOC_TO_ENDPOINTS[slug] ?? [];
}
