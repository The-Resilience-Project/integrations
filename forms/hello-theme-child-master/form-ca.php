<?php

/**
 * Standalone Wellbeing & Culture Assessment (FORM_WELLBEING_CA_2026)
 *
 * URL: https://forms.theresilienceproject.com.au/trp-wellbeing-culture-assessment/
 *
 * The hidden `organisation_id` field is auto-populated by Gravity Forms
 * dynamic population from the `school_id` URL parameter, e.g.
 *   /trp-wellbeing-culture-assessment?school_id=12345
 *
 * On render, this handler looks up the organisation in Vtiger via the
 * getOrgWithAccountNo VTAP webhook and:
 *   - Populates the welcome heading with the school name.
 *   - Sets FIELD_CA_ERROR to 'YES' if school_id is missing or the org is
 *     not found, otherwise 'NO'. Use GF conditional logic on FIELD_CA_ERROR
 *     to show the "contact your school partnership manager" page.
 *
 * Mirrors the LTRP form behaviour (form-ltrp.php) but for the standalone
 * CA flow — no SEIP is created here; the SEIP must already exist and is
 * linked to the assessment by the submit-ca-v2 endpoint on submission.
 */

require_once __DIR__ . '/form_ids.php';
require_once __DIR__ . '/form_fields.php';
require_once __DIR__ . '/form-info-session-prefill.php'; // for vtap_webhook_call()

add_filter('gform_pre_render_' . FORM_WELLBEING_CA_2026, 'populate_org_name_standalone_ca');
add_filter('gform_pre_validation_' . FORM_WELLBEING_CA_2026, 'populate_org_name_standalone_ca');
add_filter('gform_pre_submission_filter_' . FORM_WELLBEING_CA_2026, 'populate_org_name_standalone_ca');
add_filter('gform_admin_pre_render_' . FORM_WELLBEING_CA_2026, 'populate_org_name_standalone_ca');

function populate_org_name_standalone_ca($form)
{
    // Prefer the URL param (initial load); fall back to whatever GF has
    // already populated into the hidden field (page navigation/submission).
    $school_id = isset($_GET['school_id']) ? sanitize_text_field($_GET['school_id']) : '';
    if ($school_id === '') {
        $school_id = trim((string) rgpost('input_' . FIELD_CA_ORG_ID));
    }

    if ($school_id === '') {
        set_ca_field_default($form, FIELD_CA_ERROR, 'YES');
        return $form;
    }

    $org = vtap_get_org_by_account_no($school_id);
    if (!$org) {
        set_ca_field_default($form, FIELD_CA_ERROR, 'YES');
        return $form;
    }

    $school_name = $org['accountname'] ?? '';

    set_ca_field_default($form, FIELD_CA_ERROR, 'NO');
    set_ca_field_default($form, FIELD_CA_ORG_ID, $school_id);
    set_ca_field_default($form, FIELD_CA_SCHOOL_NAME, $school_name);

    foreach ($form['fields'] as &$field) {
        if ($field['id'] == FIELD_CA_WELCOME_HEADING && $school_name !== '') {
            $field['content'] = 'Welcome, ' . esc_html($school_name);
        }
    }

    return $form;
}

/**
 * Set both the field's defaultValue (for initial render) and the matching
 * $_POST entry (for re-renders during page navigation/submission).
 */
function set_ca_field_default(&$form, $field_id, $value)
{
    if (!$field_id) {
        return;
    }
    foreach ($form['fields'] as &$field) {
        if ($field['id'] == $field_id) {
            $field['defaultValue'] = $value;
        }
    }
    $_POST['input_' . $field_id] = $value;
}

/**
 * Look up an organisation in Vtiger by account number (school_id).
 *
 * @param string $account_no The Vtiger account number from ?school_id=
 * @return array|null The organisation record, or null on failure
 */
function vtap_get_org_by_account_no($account_no)
{
    $response = vtap_webhook_call(
        'https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/getOrgWithAccountNo',
        'iE9d32UPGTrbd89DUVY2grvg',
        ['organisationAccountNo' => $account_no]
    );

    if (!$response || empty($response['result'])) {
        return null;
    }

    return $response['result'][0];
}
