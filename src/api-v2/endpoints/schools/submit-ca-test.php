<?php

/**
 * Standalone Culture Assessment submission — capture endpoint.
 *
 * Receives the Gravity Forms webhook payload from the draft form at
 *   https://forms.theresilienceproject.com.au/leading-trp-culture-assessment/
 * (standalone version — culture-assessment section only) and logs the full
 * body to CloudWatch so we can inspect the payload shape while the form is
 * still a draft.
 *
 * Once the form is finalised, the next iteration should:
 *   - introduce a CultureAssessmentRequest domain VO with fromFormData()
 *   - move processing into a SubmitCultureAssessmentHandler in Application/
 *   - persist via Vtiger (create record in the assessment module)
 *
 * URL: POST /api/v2/schools/submit-ca-test
 * Auth: none for now — Gravity Forms webhook hits this directly.
 */

require dirname(__FILE__).'/../../../api/utils.php';
require dirname(__FILE__).'/../../../init.php';
require dirname(__FILE__).'/../../../../vendor/autoload.php';

use ApiV2\Domain\CultureAssessmentPayload;
use ApiV2\Infrastructure\VtigerWebhookClient;

/**
 * Discover the SEIP module's reference field that points at Accounts.
 * Cached in a static so we only `describe` once per request.
 *
 * @param object $vtod Initialised dhvt client (src/lib is outside PHPStan)
 */
function find_seip_org_field($vtod, string $module): ?string
{
    static $cache = [];
    if (array_key_exists($module, $cache)) {
        return $cache[$module];
    }

    $describe = $vtod->describe($module);
    foreach ($describe['fields'] ?? [] as $field) {
        $typeName = $field['type']['name'] ?? '';
        $refs = $field['type']['refersTo'] ?? [];
        if ($typeName === 'reference' && in_array('Accounts', $refs, true)) {
            return $cache[$module] = $field['name'];
        }
    }
    return $cache[$module] = null;
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();

if ($method === 'OPTIONS') {
    send_response([], 204);
    exit;
}

if ($method !== 'POST') {
    send_response(['status' => 'fail', 'message' => 'Method not allowed'], 405);
    exit;
}

try {
    $data = get_request_data();

    log_info('v2 Standalone CA submission received', [
        'endpoint' => 'v2/schools/submit-ca-test',
        'field_count' => is_array($data) ? count($data) : 0,
        'payload' => $data,
    ]);

    $accountNo = trim((string) ($data['organisation_id'] ?? ''));
    if ($accountNo === '') {
        log_warning('v2 Standalone CA submission missing organisation_id — skipping');
        send_response(['status' => 'skipped', 'reason' => 'missing organisation_id']);
        exit;
    }

    $tokens = require dirname(__FILE__).'/../../Config/webhook_tokens.php';
    $client = new VtigerWebhookClient(
        'https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/',
        $tokens,
    );

    $orgResponse = $client->post('getOrgWithAccountNo', [
        'organisationAccountNo' => $accountNo,
    ], true);

    $org = $orgResponse->result[0] ?? null;
    if ($org === null) {
        log_warning('v2 Standalone CA — no organisation found for account number, skipping creation', [
            'organisation_id' => $accountNo,
        ]);
        send_response(['status' => 'skipped', 'reason' => 'organisation not found']);
        exit;
    }

    $crmOrgId = (string) ($org->id ?? '');
    log_info('v2 Standalone CA — organisation resolved', [
        'account_no' => $accountNo,
        'organisation_id' => $crmOrgId,
        'organisation_name' => $org->accountname ?? '',
    ]);

    $payload = CultureAssessmentPayload::build($crmOrgId, $data);
    log_info('v2 Standalone CA — creating assessment', [
        'organisation_id' => $crmOrgId,
        'scores' => array_intersect_key($payload, array_flip([
            'visionAndPractice', 'explicitTeaching', 'habitBuilding',
            'staffCapacity', 'staffWellbeing', 'familyCapacity', 'partnerships',
        ])),
    ]);

    $assessmentResponse = $client->post('createAssessment', $payload);
    $assessmentId = $assessmentResponse->result->id ?? '';

    log_info('v2 Standalone CA — assessment created', [
        'organisation_id' => $crmOrgId,
        'assessment_id' => $assessmentId,
    ]);

    // Find the existing SEIP for this org (any name) and link the new
    // assessment to it via the "Wellbeing and Culture Assessments"
    // many-to-many related list. We deliberately do NOT create a SEIP if
    // none exists — the SEIP should be set up before the assessment.
    global $vtod;
    $seipId = '';
    $seipModule = 'vtcmseip';

    $seipOrgField = find_seip_org_field($vtod, $seipModule);
    if ($seipOrgField === null) {
        log_warning('v2 Standalone CA — could not detect SEIP org-reference field, skipping SEIP link');
    } else {
        $escapedOrgId = str_replace("'", '', $crmOrgId);
        $query = "SELECT * FROM {$seipModule} WHERE {$seipOrgField} = '{$escapedOrgId}' ORDER BY createdtime DESC LIMIT 1;";
        $seips = $vtod->query($query);

        if (empty($seips)) {
            log_warning('v2 Standalone CA — no existing SEIP found for org, skipping SEIP link', [
                'organisation_id' => $crmOrgId,
            ]);
        } else {
            $seipId = (string) ($seips[0]['id'] ?? '');
            log_info('v2 Standalone CA — linking assessment to existing SEIP', [
                'seip_id' => $seipId,
                'seip_name' => $seips[0]['seipname'] ?? '',
                'assessment_id' => $assessmentId,
            ]);
            $vtod->addRelated($seipId, $assessmentId);
            log_info('v2 Standalone CA — SEIP link complete');
        }
    }

    send_response([
        'status' => 'success',
        'assessment_id' => $assessmentId,
        'organisation_id' => $crmOrgId,
        'seip_id' => $seipId,
    ]);
} catch (Exception $e) {
    log_exception($e, ['endpoint' => 'v2/schools/submit-ca-test']);
    send_response([
        'status' => 'fail',
        'message' => 'Error capturing CA submission: '.$e->getMessage(),
    ], 500);
}
