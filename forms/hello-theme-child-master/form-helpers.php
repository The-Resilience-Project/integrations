<?php

/**
 * Shared Helpers
 *
 * Progress bar visibility, default quantity, session dropdown
 * population from API, and readonly field script injection.
 */

require_once __DIR__ . '/form_ids.php';

add_filter('gform_progress_bar', 'hide_progress_bar_wrap', 10, 3);
function hide_progress_bar_wrap($progress_bar, $form, $confirmation_message)
{
    $progress_bar = '<span class="wrap_progress_bar" style="visibility:hidden;display:none">'.$progress_bar.'</span>';

    return $progress_bar;
}
add_filter('gform_field_value_default_quantity', 'endo_set_default_quantity');
function endo_set_default_quantity()
{
    return 1; // change this number to whatever you want the default quantity to be
}

// Populate session dropdown choices from the getEventPlanned API
function populate_sessions_from_api($form, $field_id, $api_type = null)
{
    foreach ($form['fields'] as &$field) {
        if ($field->id != $field_id) {
            continue;
        }
        $url = 'https://theresilienceproject.com.au/resilience/Potentials/getEventPlanned.php';
        if ($api_type) {
            $url .= '?type=' . $api_type;
        }
        $curl_handle = curl_init($url);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        $response = curl_exec($curl_handle);
        curl_close($curl_handle);
        $response = json_decode($response, true);
        $field->choices = $response['optionTextValueMapping'];
        $field->placeholder = '-- Select a session --';
    }

    return $form;
}

// School Info Sessions (2026 + 2027)
foreach (['gform_pre_render_', 'gform_pre_validation_', 'gform_pre_submission_filter_', 'gform_admin_pre_render_'] as $hook) {
    add_filter($hook . FORM_SCHOOL_INFO_SESSION_2026, function ($form) {
        return populate_sessions_from_api($form, 5);
    });
    add_filter($hook . FORM_SCHOOL_INFO_SESSION_2027, function ($form) {
        return populate_sessions_from_api($form, 5);
    });
}

// Leading TRP Sessions
foreach (['gform_pre_render_', 'gform_pre_validation_', 'gform_pre_submission_filter_', 'gform_admin_pre_render_'] as $hook) {
    add_filter($hook . FORM_LEADING_TRP_SESSIONS, function ($form) {
        return populate_sessions_from_api($form, 5, 'leading-trp');
    });
}

// Early Years Info Sessions
foreach (['gform_pre_render_', 'gform_pre_validation_', 'gform_pre_submission_filter_', 'gform_admin_pre_render_'] as $hook) {
    add_filter($hook . FORM_EY_INFO_SESSION, function ($form) {
        return populate_sessions_from_api($form, 5, 'early-year');
    });
}


// update '1' to the ID of your form
add_filter('gform_pre_render', 'add_readonly_script');
function add_readonly_script($form)
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            /* apply only to an input field with a class of gf_readonly */
            jQuery(".read-only input").attr("readonly","readonly");
			jQuery(".read-only input").attr("disabled","true");
        });
    </script>
    <?php
    return $form;
}
