<?php

/**
 * LTRP & Culture Assessment (86)
 *
 * Progress steps, save/continue link control, organisation data
 * prepopulation, and field content emphasis for the LTRP form.
 */

require_once __DIR__ . '/form_ids.php';
require_once __DIR__ . '/form_fields.php';

add_filter('gform_progress_steps_' . FORM_LTRP_AND_CA_2026, 'add_one_more_step', 10, 3);
function add_one_more_step($progress_steps, $form, $page)
{
    $search = '</div>';
    $new_step = '<div id="gf_step_86_4" class="gf_step gf_step_last gf_step_pending"><span class="gf_step_number">4</span><span class="gf_step_label">Welcome Meeting</span></div>'.$search;
    $nth = 3;
    $matches = [];
    $found = preg_match_all('#'.preg_quote($search).'#', $progress_steps, $matches, PREG_OFFSET_CAPTURE);
    if (false !== $found && $found > $nth) {
        return substr_replace($progress_steps, $new_step, $matches[0][$nth][1], strlen($search));
    }

    return $progress_steps;
}

// culture assessment disable save and continue for first two pages
add_filter('gform_savecontinue_link_' . FORM_LTRP_AND_CA_2026, function ($save_button, $form) {
    $form_id            = $form['id'];
    $page_number = GFFormDisplay::get_current_page($form_id);

    if ($page_number == 1 || $page_number == 2) {
        return null;
    }
    return '<div class="footer-spacer"></div>'.$save_button;

}, 10, 2);

add_filter('gform_pre_render_' . FORM_LTRP_AND_CA_2026, 'populate_org_name_state_ltrp');
function populate_org_name_state_ltrp($form)
{
    $form_id = $form['id'];
    if (GFFormDisplay::get_current_page($form_id) == 2) {

        $org_id = rgpost('input_' . FIELD_LTRP_ACCOUNT_ID);

        $request_header = [];
        $request_method = 'GET';

        $request_handle = curl_init('https://theresilienceproject.com.au/resilience/api/school_ltrp_details.php/?org_id='.$org_id);
        curl_setopt_array($request_handle, [
            CURLOPT_CUSTOMREQUEST => $request_method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request_header,
        ]);

        $response = curl_exec($request_handle);
        $json_response = json_decode($response, true);
        curl_close($request_handle);


        if ($json_response['data']['error']) {
            $_POST['input_' . FIELD_LTRP_ERROR] = 'YES';
            return $form;
        }



        $_POST['input_' . FIELD_LTRP_STATE] = $json_response['data']['state'];
        $_POST['input_' . FIELD_LTRP_SCHOOL] = $json_response['data']['name'];
        $_POST['input_' . FIELD_LTRP_ORG_ID] = $json_response['data']['id'];
        $_POST['input_' . FIELD_LTRP_LTRP_WATCHED] = $json_response['data']['ltrp'] ? 'YES' : 'NO';
        $_POST['input_' . FIELD_LTRP_CA_COMPLETED] = $json_response['data']['ca'] ? 'YES' : 'NO';
        $_POST['input_' . FIELD_LTRP_PARTICIPANTS] = $json_response['data']['participants'];
        $_POST['input_' . FIELD_LTRP_ERROR] = 'NO'; // error field

        foreach ($form['fields'] as &$field) {
            if ($field['id'] == FIELD_LTRP_WELCOME_HEADING) {
                $field['content'] = 'Welcome, ' . $json_response['data']['name'];
            }
        }

    }


    return $form;
}

add_filter('gform_field_content_' . FORM_LTRP_AND_CA_2026, function ($field_content, $field) {
    if ($field->id == FIELD_LTRP_SCORE_FORTNIGHT) {
        return str_replace('fortnight', "<span class='emph-wording'>fortnight</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_WEEK) {
        return str_replace('week', "<span class='emph-wording'>week</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_WEEKLY) {
        return str_replace('weekly', "<span class='emph-wording'>weekly</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_DAILY) {
        return str_replace('daily', "<span class='emph-wording'>daily</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_SOME) {
        return str_replace('some', "<span class='emph-wording'>some</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_MOST) {
        return str_replace('most', "<span class='emph-wording'>most</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_SEMESTERLY) {
        return str_replace('semesterly', "<span class='emph-wording'>semesterly</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_TERMLY) {
        return str_replace('termly', "<span class='emph-wording'>termly</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_SOME_CAPS) {
        return str_replace('Some', "<span class='emph-wording'>Some</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_ALL) {
        return str_replace('All', "<span class='emph-wording'>All</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_YEAR) {
        return str_replace('year', "<span class='emph-wording'>year</span>", $field_content);
    }
    if ($field->id == FIELD_LTRP_SCORE_SEMESTER) {
        return str_replace('semester', "<span class='emph-wording'>semester</span>", $field_content);
    }

    if ($field->id == FIELD_LTRP_SCORE_TERMLY_2) {
        return str_replace('termly', "<span class='emph-wording'>termly</span>", $field_content);
    }
    if ($field->id == FIELD_LTRP_SCORE_FORTNIGHTLY) {
        return str_replace('fortnightly', "<span class='emph-wording'>fortnightly</span>", $field_content);
    }
    if ($field->id == FIELD_LTRP_SCORE_EACH_SEMESTER) {
        return str_replace('each semester', "<span class='emph-wording'>each semester</span>", $field_content);
    }

    return $field_content;
}, 10, 2);
