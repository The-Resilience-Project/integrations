<?php

/**
 * Curriculum Ordering (89)
 *
 * Multi-page form data prepopulation, order summary generation,
 * shipping calculation, and validation for the curriculum resource
 * ordering form.
 */

require_once __DIR__ . '/form_ids.php';
require_once __DIR__ . '/form_fields.php';

add_filter('gform_pre_render_' . FORM_CURRICULUM_RESOURCE_ORDERING_2026, 'populate_curric_form_data_2026');
function populate_curric_form_data_2026($form)
{
    $form_id = $form['id'];
    $school_account_no_field = 'input_' . FIELD_CURRIC_SELECT_SCHOOL;
    if (GFFormDisplay::get_current_page($form_id) == 2) {
        $school_account_no = rgpost($school_account_no_field);
        $url = '"https://theresilienceproject.com.au/resilience/api/school_curric_ordering_details.php/?school_account_no='.$school_account_no.'&for_2026=1"';
        ?>
		<script>
			jQuery.ajax({
				url:<?php echo $url ?>,
				method:"GET",
				dataType: "JSON",
				success: function(result){
					const prepopulateData = result.data;
					if(prepopulateData) {
					    let engageCombo = "Journal Only"
					    if(prepopulateData.engage.includes('Journals') && prepopulateData.engage.includes('Planners')){
					        engageCombo = "Journals and Planners"
					    } else if (!prepopulateData.engage.includes('Journals') && prepopulateData.engage.includes('Planners')){
					        engageCombo = "Planner Only"
					    }
						const freeShipping = prepopulateData.free_shipping ? "YES" : "NO";
						const fundedSchool = prepopulateData.funded_years.includes("2026") ? "YES" : "NO"
						const newSchool = prepopulateData.deal_type === "School - New" ? "YES" : "NO"
						jQuery("#input_<?php echo FORM_CURRICULUM_RESOURCE_ORDERING_2026 ?>_<?php echo FIELD_CURRIC_ENGAGE_COMBO ?>").val(engageCombo);
						jQuery("#input_<?php echo FORM_CURRICULUM_RESOURCE_ORDERING_2026 ?>_<?php echo FIELD_CURRIC_FREE_SHIPPING ?>").val(freeShipping);
						jQuery("#input_<?php echo FORM_CURRICULUM_RESOURCE_ORDERING_2026 ?>_<?php echo FIELD_CURRIC_FUNDED_SCHOOL ?>").val(fundedSchool);
						jQuery("#input_<?php echo FORM_CURRICULUM_RESOURCE_ORDERING_2026 ?>_<?php echo FIELD_CURRIC_NEW_SCHOOL ?>").val(newSchool);
						jQuery(document).trigger('gform_post_render', [89, 2]);
					}
				}
			});
		</script>
		<?php
    }


    if (GFFormDisplay::get_current_page($form_id) == 5) {
        $free_shipping = rgpost('input_' . FIELD_CURRIC_FREE_SHIPPING);
        require_once('calculate_shipping.php');

        if ($free_shipping === 'YES') {
            $shipping_price = 0.00;
            $displayed_shipping_price = '$ ' . number_format($shipping_price, 2);
        } else {
            $curric_shipping = new CurricShipping();
            $shipping_price = $curric_shipping->get_shipping_price();
            if ($shipping_price == 0) {
                $displayed_shipping_price = "Unable to calculate shipping. We'll be in touch.";
            } else {
                $displayed_shipping_price = '$ ' . number_format($shipping_price, 2);
            }
        }
        $_POST['input_' . FIELD_CURRIC_SHIPPING_COST] = $shipping_price;

        $fields = $form['fields'];
        $description = '';

        $description .= '<h4>School Details</h4>';
        $description .= "<table class='school-details'>";
        $description .= '<tr><td>School Name</td><td>'.GFAPI::get_field($form_id, FIELD_CURRIC_SELECT_SCHOOL)->choices[0]['text'].'</td></tr>';
        $description .= '<tr><td>Your Name</td><td>'.rgpost('input_' . FIELD_CURRIC_CONTACT_NAME . '_3'). ' '. rgpost('input_' . FIELD_CURRIC_CONTACT_NAME . '_6').'</td></tr>';

        $description .= '<tr><td>Shipping Address</td><td>';
        $description .= rgpost('input_' . FIELD_CURRIC_SHIPPING_ADDRESS . '_1') .'<br/>';
        if (rgpost('input_' . FIELD_CURRIC_SHIPPING_ADDRESS . '_2')) {
            $description .= rgpost('input_' . FIELD_CURRIC_SHIPPING_ADDRESS . '_2') .'<br/>';
        }
        $description .= rgpost('input_' . FIELD_CURRIC_SHIPPING_ADDRESS . '_3') .'<br/>';
        $description .= rgpost('input_' . FIELD_CURRIC_SHIPPING_STATE) .' ';
        $description .= rgpost('input_' . FIELD_CURRIC_SHIPPING_ADDRESS . '_5');
        $description .= '</td></tr>';

        $description .= '<tr><td>Billing Address</td><td>';
        if (rgpost('input_' . FIELD_CURRIC_SAME_AS_SHIPPING . '_1')) {
            $description .= 'Same as Shipping Address';
        } else {
            $description .= rgpost('input_' . FIELD_CURRIC_BILLING_ADDRESS . '_1') .'<br/>';
            if (rgpost('input_' . FIELD_CURRIC_BILLING_ADDRESS . '_2')) {
                $description .= rgpost('input_' . FIELD_CURRIC_BILLING_ADDRESS . '_2') .'<br/>';
            }
            $description .= rgpost('input_' . FIELD_CURRIC_BILLING_ADDRESS . '_3') .'<br/>';
            $description .= rgpost('input_' . FIELD_CURRIC_BILLING_STATE) .' ';
            $description .= rgpost('input_' . FIELD_CURRIC_BILLING_ADDRESS . '_5');
            $description .= '</td></tr>';
        }

        $description .= '<tr><td>PO Number</td><td>'.rgpost('input_' . FIELD_CURRIC_PO_NUMBER).'</td></tr>';

        $description .= '</table><br/>';

        $student_table = '';
        $student_journal_qty = 0;

        // student numbers
        $year_levels = [
            ['Foundation', 'input_10_3'],
            ['Year 1', 'input_11_3'],
            ['Year 2', 'input_12_3'],
            ['Year 3', 'input_13_3'],
            ['Year 4', 'input_14_3'],
            ['Year 5', 'input_15_3'],
            ['Year 6', 'input_16_3'],
            ['Year 7', 'input_17_3'],
            ['Year 7 (Planners)', 'input_202_2'],
            ['Year 7 (Planners)', 'input_183_1'],
            ['Year 8', 'input_18_3'],
            ['Year 8 (Planners)', 'input_203_2'],
            ['Year 8 (Planners)', 'input_185_1'],
            ['Year 9', 'input_19_3'],
            ['Year 9 (Planners)', 'input_204_2'],
            ['Year 9 (Planners)', 'input_186_1'],
            ['Year 10', 'input_20_3'],
            ['Year 10 (Planners)', 'input_205_2'],
            ['Year 10 (Planners)', 'input_187_1'],
            ['Year 11', 'input_21_3'],
            ['Year 11 (Planners)', 'input_206_2'],
            ['Year 11 (Planners)', 'input_188_1'],
            ['Year 12', 'input_22_3'],
            ['Year 12 (Planners)', 'input_207_2'],
            ['Year 12 (Planners)', 'input_189_1'],


        ];
        foreach ($year_levels as [$year_level, $input_name]) {
            $num = rgpost($input_name);
            if ($num > 0) {
                if (str_contains($year_level, 'Planners')) {
                    $student_table .= '<tr><td>'.$year_level.'</td><td>Please confirm with Product Dynamics</td></tr>';
                } else {
                    $student_table .= '<tr><td>'.$year_level.'</td><td>'.$num.'</td></tr>';
                    $student_journal_qty += $num;
                }
            }
        }
        if ($student_table !== '') {
            $description .= '<h4>Student Curriculum</h4>';
            $description .= '<table><tr><th>Description</th><th>Quantity</th></tr>';
            $description .= $student_table;
            $description .= '<tr><td><b>Total</b></td><td><b>'.$student_journal_qty.'</b></td></tr>';

            $description .= '</table><br/>';
        }

        $teacher_table = '';
        // teacher
        $year_levels = [
            ['Foundation', 'input_24_3'],
            ['Year 1', 'input_66_3'],
            ['Year 2', 'input_67_3'],
            ['Year 3', 'input_68_3'],
            ['Year 4', 'input_69_3'],
            ['Year 5', 'input_70_3'],
            ['Year 6', 'input_71_3'],
            ['Year 7', 'input_72_3'],
            ['Year 8', 'input_73_3'],
            ['Year 9', 'input_74_3'],
            ['Year 10', 'input_75_3'],
            ['Year 11', 'input_76_3'],
            ['Year 12', 'input_77_3'],


        ];
        $teacher_resource_qty = 0;
        foreach ($year_levels as [$year_level, $input_name]) {
            $num = rgpost($input_name);
            if ($num > 0) {
                $teacher_table .= '<tr><td>'.$year_level.'</td><td>'.$num.'</td></tr>';
                $teacher_resource_qty += $num;
            }
        }
        if ($teacher_table !== '') {
            $description .= '<h4>Hard Copy Teacher Resources</h4>';
            $description .= '<table><tr><th>Description</th><th>Quantity</th></tr>';
            $description .= $teacher_table;
            $description .= '<tr><td><b>Total</b></td><td><b>'.$teacher_resource_qty.'</b></td></tr>';
            $description .= '</table><br/>';
        }

        // extra
        $extra_table = '';
        $shop_items = [
            ['Primary Reading Log', 'input_28_2', 'input_28_3'],
            ['Primary Student Planner', 'input_36_2', 'input_36_3'],
            ['GEM Conversation Cards', 'input_29_2', 'input_29_3'],
            ['Emotion Cards', 'input_178_2', 'input_178_3'],
            ['21 Day Wellbeing Journal', 'input_37_2', 'input_37_3'],
            ['6 Month Wellbeing Journal', 'input_38_2', 'input_38_3'],
            ['Fence Signs', 'input_115_2', 'input_115_3'],



        ];
        foreach ($shop_items as [$shop_item, $price_name, $input_name]) {
            $num = rgpost($input_name);
            $price = rgpost($price_name);
            if ($num > 0) {
                $extra_table .= '<tr><td>'.$shop_item.'</td><td>'.$num.'</td></tr>';
            }
        }
        $teacher_planner_num = rgpost('input_' . FIELD_CURRIC_TEACHER_PLANNER . '_3');
        if ($teacher_planner_num > 0) {
            $teacher_planner_details = explode(' - ', rgpost('input_' . FIELD_CURRIC_TEACHER_PLANNER_VAR));
            $teacher_planner_type = $teacher_planner_details[0];
            $extra_table .= '<tr><td>Teacher Planner ('.$teacher_planner_type.')</td><td>'.$teacher_planner_num.'</td></tr>';
        }

        $senior_planner_num = rgpost('input_' . FIELD_CURRIC_SENIOR_PLANNER . '_3');
        if ($senior_planner_num > 0) {
            $senior_planner_details = explode(' - ', rgpost('input_' . FIELD_CURRIC_SENIOR_PLANNER_VAR));
            $senior_planner_type = $senior_planner_details[0];
            $extra_table .= '<tr><td>Senior Planner '.$senior_planner_type.'</td><td>'.$senior_planner_num.'</td></tr>';
        }

        $teacher_seminar_num = rgpost('input_' . FIELD_CURRIC_TEACHER_SEMINAR . '_3');
        if ($teacher_seminar_num > 0) {
            $teacher_seminar_details = explode('|', rgpost('input_' . FIELD_CURRIC_TEACHER_SEMINAR_VAR));
            $teacher_seminar_type = $teacher_seminar_details[0];
            $extra_table .= '<tr><td>Teacher Seminar Pre-Order Ticket ('.$teacher_seminar_type.')</td><td>'.$teacher_seminar_num.'</td></tr>';
        }
        if ($extra_table !== '') {
            $description .= '<h4>Extra Resources</h4>';
            $description .= '<table><tr><th>Description</th><th>Quantity</th></tr>';
            $description .= $extra_table;
            $description .= '</table><br/>';
        }
        $description .= '<h4>Shipping</h4>';
        if ($shipping_price == 0) {
            $description .= '<p><i>Great news! Since you placed your first order early, this order qualifies for free shipping!</i></p>';
        }
        $description .= '<table><tr><th>Description</th><th>Unit Price (excl GST)</th></tr>';
        if (rgpost('input_' . FIELD_CURRIC_FUNDED_SCHOOL) === 'YES') {
            // free shipping for funded schools
            $description .= '<tr><td>Shipping</td><td>$0.00</td></tr>';
        } else {
            $description .= '<tr><td>Shipping</td><td>'. $displayed_shipping_price .'</td></tr>';
        }
        $description .= '</table>';

        foreach ($form['fields'] as &$field) {
            if ($field['id'] == FIELD_CURRIC_ORDER_SUMMARY) {
                $field['content'] = $description;
            }
        }

    }

    return $form;
}

add_filter('gform_pre_render_' . FORM_SHIPPING_CALCULATOR, 'calculate_shipping_for_spms');
function calculate_shipping_for_spms($form)
{
    $form_id = $form['id'];
    if (GFFormDisplay::get_current_page($form_id) == 2) {
        require_once('calculate_shipping.php');
        $curric_shipping = new CurricShipping(false);
        $shipping_price = $curric_shipping->get_shipping_price();
        $_POST['input_47'] = $shipping_price;
    }
    return $form;
}

add_filter('gform_validation_' . FORM_CURRICULUM_RESOURCE_ORDERING_2026, 'curric_ordering_validation');
function curric_ordering_validation($validation_result)
{
    $form = $validation_result['form'];


    // last date in 2025
    if (!empty(rgpost('input_' . FIELD_CURRIC_LAST_DATE_2025))) {
        $is_valid = DateTime::createFromFormat('d/m/Y', rgpost('input_' . FIELD_CURRIC_LAST_DATE_2025)) <= DateTime::createFromFormat('d/m/Y', '31/12/2025');
        if (!$is_valid) {
            // set the form validation to false
            $validation_result['is_valid'] = false;

            //finding Field with ID of 1 and marking it as failed validation
            foreach ($form['fields'] as &$field) {

                //NOTE: replace 1 with the field you would like to validate
                if ($field->id == FIELD_CURRIC_LAST_DATE_2025) {
                    $field->failed_validation = true;
                    $field->validation_message = 'Please enter a date before 31/12/2025';
                    break;
                }
            }
        }


    }

    // first date in 2026
    if (!empty(rgpost('input_' . FIELD_CURRIC_FIRST_DATE_2026))) {
        $is_valid = DateTime::createFromFormat('d/m/Y', rgpost('input_' . FIELD_CURRIC_FIRST_DATE_2026)) >= DateTime::createFromFormat('d/m/Y', '01/01/2026');
        if (!$is_valid) {
            // set the form validation to false
            $validation_result['is_valid'] = false;

            //finding Field with ID of 1 and marking it as failed validation
            foreach ($form['fields'] as &$field) {

                //NOTE: replace 1 with the field you would like to validate
                if ($field->id == FIELD_CURRIC_FIRST_DATE_2026) {
                    $field->failed_validation = true;
                    $field->validation_message = 'Please enter a date after 01/01/2026';
                    break;
                }
            }
        }


    }



    // primary reading log min qty = 10
    if (rgpost('input_' . FIELD_CURRIC_READING_LOG . '_3') < 10 and !empty(rgpost('input_' . FIELD_CURRIC_READING_LOG . '_3'))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach ($form['fields'] as &$field) {

            //NOTE: replace 1 with the field you would like to validate
            if ($field->id == FIELD_CURRIC_READING_LOG) {
                $field->failed_validation = true;
                $field->validation_message = 'Minumum 10 items are required';
                break;
            }
        }

    }

    // primary student planner min qty = 10
    if (rgpost('input_' . FIELD_CURRIC_STUDENT_PLANNER . '_3') < 10 and !empty(rgpost('input_' . FIELD_CURRIC_STUDENT_PLANNER . '_3'))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach ($form['fields'] as &$field) {

            //NOTE: replace 1 with the field you would like to validate
            if ($field->id == FIELD_CURRIC_STUDENT_PLANNER) {
                $field->failed_validation = true;
                $field->validation_message = 'Minumum 10 items are required';
                break;
            }
        }

    }

    // senior student planner min qty = 10
    if (rgpost('input_' . FIELD_CURRIC_SENIOR_PLANNER . '_3') < 10 and !empty(rgpost('input_' . FIELD_CURRIC_SENIOR_PLANNER . '_3'))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach ($form['fields'] as &$field) {

            //NOTE: replace 1 with the field you would like to validate
            if ($field->id == FIELD_CURRIC_SENIOR_PLANNER) {
                $field->failed_validation = true;
                $field->validation_message = 'Minumum 10 items are required';
                break;
            }
        }

    }

    // teacher sem max qty = 10
    if (rgpost('input_' . FIELD_CURRIC_TEACHER_SEMINAR . '_3') > 30 and !empty(rgpost('input_' . FIELD_CURRIC_TEACHER_SEMINAR . '_3'))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach ($form['fields'] as &$field) {

            //NOTE: replace 1 with the field you would like to validate
            if ($field->id == FIELD_CURRIC_TEACHER_SEMINAR) {
                $field->failed_validation = true;
                $field->validation_message = 'Maxiumum 10 tickets can be purchased';
                break;
            }
        }

    }

    // if journals picked, need a number
    $journal_checkbox_input_pairs = [
        ['202', '17'],
        ['203', '18'],
        ['204', '19'],
        ['205', '20'],
        ['206', '21'],
        ['207', '22'],
    ];
    foreach ($journal_checkbox_input_pairs as [$checkbox, $input]) {
        if (rgpost('input_'.$checkbox.'_1') and empty(rgpost('input_'.$input.'_3'))) {

            // set the form validation to false
            $validation_result['is_valid'] = false;

            //finding Field with ID of 1 and marking it as failed validation
            foreach ($form['fields'] as &$field) {

                //NOTE: replace 1 with the field you would like to validate
                if ($field->id == $input) {
                    $field->failed_validation = true;
                    $field->validation_message = 'Enter number of student journals';
                    break;
                }
            }

        }
    }

    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
    return $validation_result;


}
