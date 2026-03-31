/**
 * Maps GF webhook URLs to Lambda function names.
 *
 * Webhook URLs look like: https://xxx.execute-api.../api/enquiry.php
 * Function names match FUNCTION_NAMES in constants.ts (e.g. "enquiry").
 */

const URL_TO_FUNCTION: Record<string, string> = {
  'enquiry.php': 'enquiry',
  'confirm.php': 'confirm',
  'confirm_existing_schools.php': 'confirm_existing_schools',
  'register.php': 'register',
  'seminar_registration.php': 'seminar_registration',
  'qualify.php': 'qualify',
  'accept_dates.php': 'accept_dates',
  'submit_ca.php': 'submit_ca',
  'order_resources_2026.php': 'order_resources_2026',
  'order_resources.php': 'order_resources',
  'calculate_shipping.php': 'calculate_shipping',
  'prize_pack.php': 'prize_pack',
  'calendly_event.php': 'calendly_event',
  '/api/v2/schools/enquiry': 'v2_enquiry',
  '/api/v2/schools/register': 'v2_register',
  '/api/v2/schools/prize-pack': 'v2_prize_pack',
};

export function urlToFunctionName(webhookUrl: string): string | null {
  // order_resources_2026 must match before order_resources, so iterate the map
  for (const [pattern, fn] of Object.entries(URL_TO_FUNCTION)) {
    if (webhookUrl.includes(pattern)) return fn;
  }
  return null;
}
