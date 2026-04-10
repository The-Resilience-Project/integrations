<?php

/**
 * Info Session Registration Pre-fill (form 91)
 *
 * When a contact_id URL parameter is present, fetches the contact's
 * details from Vtiger and pre-fills form fields via JavaScript
 * (name, email, phone, school, state, student count).
 */

require_once __DIR__ . '/form_ids.php';

add_filter('gform_pre_render_' . FORM_SCHOOL_INFO_SESSION_2027, 'prefill_info_session_from_contact');

function prefill_info_session_from_contact($form)
{
    $contact_id = isset($_GET['contact_id']) ? sanitize_text_field($_GET['contact_id']) : '';
    if (empty($contact_id)) {
        return $form;
    }

    // Fetch contact from Vtiger
    $contact = vtap_get_contact($contact_id);
    if (!$contact) {
        return $form;
    }

    $firstname = esc_js($contact['firstname'] ?? '');
    $lastname = esc_js($contact['lastname'] ?? '');
    $email = esc_js($contact['email'] ?? '');
    $mobile = esc_js($contact['mobile'] ?? $contact['phone'] ?? '');

    // Fetch the contact's organisation for school details
    $account_no = '';
    $state = '';
    $students = '';
    $account_id = $contact['account_id'] ?? '';
    if (!empty($account_id)) {
        $org = vtap_get_org_details($account_id);
        if ($org) {
            $account_no = esc_js($org['account_no'] ?? '');
            $state = esc_js($org['cf_accounts_statenew'] ?? '');
            $students = esc_js($org['cf_accounts_totalstudents'] ?? '');
        }
    }

    $form_id = FORM_SCHOOL_INFO_SESSION_2027;
    ?>
    <script>
    jQuery(document).on('gform_post_render', function(event, formId) {
        if (formId != <?php echo $form_id; ?>) return;

        // Contact details
        jQuery('#input_<?php echo $form_id; ?>_3_3').val('<?php echo $firstname; ?>');
        jQuery('#input_<?php echo $form_id; ?>_3_6').val('<?php echo $lastname; ?>');
        jQuery('#input_<?php echo $form_id; ?>_4').val('<?php echo $email; ?>');
        jQuery('#input_<?php echo $form_id; ?>_6').val('<?php echo $mobile; ?>');

        // School dropdown (value is ACC-format account number)
        <?php if (!empty($account_no)) : ?>
        jQuery('#input_<?php echo $form_id; ?>_10').val('<?php echo $account_no; ?>').trigger('change');
        <?php endif; ?>

        // State dropdown
        <?php if (!empty($state)) : ?>
        jQuery('#input_<?php echo $form_id; ?>_7').val('<?php echo $state; ?>').trigger('change');
        <?php endif; ?>

        // Number of students
        <?php if (!empty($students)) : ?>
        jQuery('#input_<?php echo $form_id; ?>_12').val('<?php echo $students; ?>');
        <?php endif; ?>
    });
    </script>
    <?php

    return $form;
}

/**
 * Fetch a contact record from Vtiger by internal ID.
 *
 * @param string $contact_id The numeric contact ID (without the 4x prefix)
 * @return array|null The contact record, or null on failure
 */
function vtap_get_contact($contact_id)
{
    $vtiger_id = '4x' . $contact_id;

    $response = vtap_webhook_call(
        'https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/getContactById',
        'RjlLbIhNjmR92dtek5YQfAcg',
        ['contactId' => $vtiger_id]
    );

    if (!$response || empty($response['result'])) {
        return null;
    }

    return $response['result'][0];
}

/**
 * Fetch an organisation record from Vtiger by internal ID.
 *
 * @param string $org_id The Vtiger organisation ID (e.g. '3x12345')
 * @return array|null The organisation record, or null on failure
 */
function vtap_get_org_details($org_id)
{
    $response = vtap_webhook_call(
        'https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/getOrgDetails',
        'DdtiDMSsq9ETjSe2FMEZBICu',
        ['organisationId' => $org_id]
    );

    if (!$response || empty($response['result'])) {
        return null;
    }

    return $response['result'][0];
}

/**
 * Make a VTAP webhook call.
 *
 * @param string $url The webhook URL
 * @param string $token The authentication token
 * @param array $payload The request body
 * @return array|null Decoded JSON response, or null on failure
 */
function vtap_webhook_call($url, $token, $payload)
{
    $request_handle = curl_init($url);
    curl_setopt_array($request_handle, [
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => [
            'token: ' . $token,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($request_handle);
    curl_close($request_handle);

    if (!$response) {
        return null;
    }

    return json_decode($response, true);
}
