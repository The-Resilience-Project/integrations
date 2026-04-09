<?php

/**
 * Cross-Form Validation -- School Name Input
 *
 * Validates that at least one of the school/organisation dropdown or
 * free-text checkbox field is populated across 12+ forms.
 */

require_once __DIR__ . '/form_ids.php';
require_once __DIR__ . '/form_fields.php';

// At least one of the dropdown or free text must be populated

add_filter('gform_validation', 'validate_school_name_input');

function validate_school_name_input($validation_result)
{
    $form = $validation_result['form'];
    $form_id = $form['id'];
    $service_type = 'a school';
    $test_page = rgpost('gform_source_page_number_' . $_POST['gform_submit']) ? rgpost('gform_target_page_number_' . $_POST['gform_submit']) : 1;

    // General enquiries form — special case: page/subtype-dependent
    if ($form_id == FORM_ENQUIRIES and rgpost('gform_source_page_number_' . $_POST['gform_submit']) == 2) {
        $enq_type = rgpost('input_14');
        if ($enq_type === 'School') {
            $dropdown_name = '21';
            $checkbox_name = 'input_22_1';
        } elseif ($enq_type === 'Early Years') {
            $dropdown_name = '27';
            $checkbox_name = 'input_28_1';
            $service_type = 'a service';
        } else {
            return $validation_result;
        }
    } else {
        // Form-specific field mappings: [dropdown field, checkbox field, page (optional), service_type (optional)]
        $form_field_map = [
            FORM_SCHOOL_ENQUIRIES_CONFERENCES      => ['dropdown' => '22',  'checkbox' => 'input_23_1'],
            FORM_SCHOOL_INFO_SESSION_2026           => ['dropdown' => '10',  'checkbox' => 'input_11_1'],
            FORM_SCHOOL_INFO_SESSION_2027           => ['dropdown' => '10',  'checkbox' => 'input_11_1'],
            FORM_MORE_INFO_REQUEST_2027             => ['dropdown' => '10',  'checkbox' => 'input_11_1'],
            FORM_SCHOOL_PRIZE_PACK                  => ['dropdown' => '5',   'checkbox' => 'input_7_1'],
            FORM_NEW_SCHOOLS_CONFIRMATION_2026      => ['dropdown' => '226', 'checkbox' => 'input_227_1', 'page' => 1],
            FORM_NEW_SCHOOLS_CONFIRMATION_2027      => ['dropdown' => '226', 'checkbox' => 'input_227_1', 'page' => 1],
            FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 => ['dropdown' => '5',   'checkbox' => 'input_7_1',   'page' => 1],
            FORM_LEADING_TRP_SESSIONS               => ['dropdown' => '10',  'checkbox' => 'input_11_1',  'page' => 1],
            FORM_EARLY_YEARS_CONFIRMATION_2025      => ['dropdown' => '233', 'checkbox' => 'input_235_1', 'service_type' => 'a service'],
            FORM_EY_INFO_SESSION                    => ['dropdown' => '10',  'checkbox' => 'input_11_1',  'service_type' => 'a service'],
            FORM_SCHOOL_INFO_SESSION_RECORDING_2026 => ['dropdown' => '10',  'checkbox' => 'input_11_1'],
            FORM_WORKPLACE_WEBINAR_RECORDING        => ['dropdown' => '20',  'checkbox' => 'input_21_1',  'service_type' => 'an organisation'],
            FORM_EY_PRIZE_PACK                      => ['dropdown' => '5',   'checkbox' => 'input_7_1',   'service_type' => 'a service'],
            FORM_EY_ENQUIRIES_CONFERENCES           => ['dropdown' => '22',  'checkbox' => 'input_23_1',  'service_type' => 'a service'],
        ];

        if (!isset($form_field_map[$form_id])) {
            return $validation_result;
        }

        $config = $form_field_map[$form_id];

        // Some forms only validate on a specific page
        if (isset($config['page']) and GFFormDisplay::get_current_page($form_id) != $config['page']) {
            return $validation_result;
        }

        $dropdown_name = $config['dropdown'];
        $checkbox_name = $config['checkbox'];
        if (isset($config['service_type'])) {
            $service_type = $config['service_type'];
        }
    }



    $dropdown_empty = empty(rgpost('input_'.$dropdown_name));
    $checkbox_unchecked = !rgpost($checkbox_name);

    if ($dropdown_empty and $checkbox_unchecked) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //find dropdown and mark it as failed validation
        foreach ($form['fields'] as &$field) {
            error_log($field->id);

            if ($field->id == $dropdown_name) {
                $field->failed_validation = true;
                $field->validation_message = 'Please select ' . $service_type . ' name or provide one below';
                break;
            }
        }

    }

    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
    return $validation_result;

}
