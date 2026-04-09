<?php

/**
 * Confirmation Forms (76, 80, 29)
 *
 * Deal prepopulation, pricing page logic, field content filters,
 * and extend options for school and early years confirmation forms.
 */

require_once __DIR__ . '/form_ids.php';
require_once __DIR__ . '/form_fields.php';

/*--------------------------------------------------------------------------
 * Deal Prepopulation
 *------------------------------------------------------------------------*/

// Populate school confirmation form fields
add_filter('gform_pre_render_' . FORM_NEW_SCHOOLS_CONFIRMATION_2026, 'populate_deal_confirmed');
add_filter('gform_pre_render_' . FORM_EXISTING_SCHOOLS_CONFIRMATION_2026, 'populate_deal_confirmed');
add_filter('gform_pre_render_' . FORM_EARLY_YEARS_CONFIRMATION_2025, 'populate_deal_confirmed');
function populate_deal_confirmed($form)
{
    $form_id = $form['id'];
    if ($form_id == FORM_NEW_SCHOOLS_CONFIRMATION_2026) {
        $school_account_no_field = 'input_' . FIELD_NEW_CONF_SELECT_SCHOOL;
        $school_name_field = 'input_' . FIELD_NEW_CONF_SCHOOL_NAME;
        $deal_confirmed_input = '"#input_' . FORM_NEW_SCHOOLS_CONFIRMATION_2026 . '_' . FIELD_NEW_CONF_DEAL_CONFIRMED . '"';
    } elseif ($form_id == FORM_EXISTING_SCHOOLS_CONFIRMATION_2026) {
        $school_account_no_field = 'input_' . FIELD_EXIST_CONF_SELECT_SCHOOL;
        $school_name_field = 'input_' . FIELD_EXIST_CONF_SCHOOL_NAME;
        $deal_confirmed_input = '"#input_' . FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 . '_' . FIELD_EXIST_CONF_DEAL_CONFIRMED . '"';
    } elseif ($form_id == FORM_EARLY_YEARS_CONFIRMATION_2025) {
        $deal_confirmed_input = '"#input_' . FORM_EARLY_YEARS_CONFIRMATION_2025 . '_' . FIELD_EY_CONF_DEAL_CONFIRMED . '"';
    }
    if (GFFormDisplay::get_current_page($form_id) == 2) {
        if ($form_id == FORM_NEW_SCHOOLS_CONFIRMATION_2026 or $form_id == FORM_EXISTING_SCHOOLS_CONFIRMATION_2026) {
            $school_account_no = rgpost($school_account_no_field);
            $school_name = rgpost($school_name_field);
            $url;
            if (!empty($school_account_no)) {
                $url = '"https://theresilienceproject.com.au/resilience/api/school_confirmation_form_details.php/?school_account_no='.$school_account_no.'"';
            } else {
                $url = '"https://theresilienceproject.com.au/resilience/api/school_confirmation_form_details.php/?school_name='.$school_name.'"';
            }
        } else {
            $school_account_no = rgpost('input_' . FIELD_EY_CONF_SELECT_SERVICE);
            $school_name = rgpost('input_' . FIELD_EXIST_CONF_SELECT_SCHOOL);
            if (!empty($school_account_no)) {
                $url = '"https://theresilienceproject.com.au/resilience/api/ey_confirmation_form_details.php/?school_account_no='.$school_account_no.'"';
            } else {
                $url = '"https://theresilienceproject.com.au/resilience/api/ey_confirmation_form_details.php/?school_name='.$school_name.'"';
            }
        }
        ?>
		<script>
			const dealConfirmedInput = <?php echo $deal_confirmed_input ?>;
			const formId = <?php echo $form_id ?>;
			jQuery.ajax({
				url:<?php echo $url ?>,
				method:"GET",
				dataType: "JSON",
				success: function(result){
					const prepopulateData = result.data;
					if(prepopulateData) {
						// deal_status
						const dealConfirmed = ["Deal Won", "Closed INV"].includes(prepopulateData.deal_status) ? "YES" : "NO"
						jQuery(dealConfirmedInput).val(dealConfirmed);
						if(formId === <?php echo FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 ?>){
							const freeTravel = prepopulateData.free_travel === "1" ? "YES" : "NO"
							const f2f = prepopulateData.f2f ? "YES" : "NO"
							const funded = prepopulateData.funded_years.includes("2026") ? "YES" : "NO"
							jQuery("#input_<?php echo FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 ?>_<?php echo FIELD_EXIST_CONF_FREE_TRAVEL ?>").val(freeTravel);
							jQuery("#input_<?php echo FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 ?>_<?php echo FIELD_EXIST_CONF_FACE_TO_FACE ?>").val(f2f);
							jQuery("#input_<?php echo FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 ?>_<?php echo FIELD_EXIST_CONF_FUNDED_2026 ?>").val(funded);
						}
						jQuery(document).trigger('gform_post_render', [formId, 1]);
					}
				}
			});
		</script>
		<?php
    }

    return $form;
}

/*--------------------------------------------------------------------------
 * Confirmation Pricing & Extend Options
 *------------------------------------------------------------------------*/

add_filter('gform_pre_render_' . FORM_NEW_SCHOOLS_CONFIRMATION_2026, 'new_schools_confirmation_pricing_page');
add_filter('gform_pre_render_' . FORM_EXISTING_SCHOOLS_CONFIRMATION_2026, 'new_schools_confirmation_pricing_page');
add_filter('gform_pre_render_' . FORM_EARLY_YEARS_CONFIRMATION_2025, 'new_schools_confirmation_pricing_page');


function new_schools_confirmation_pricing_page($form)
{
    $form_id = $form['id'];
    $price = '$20';
    $students_label = 'students';
    $using_journals = true;
    $using_planners = false;
    if ($form_id == FORM_NEW_SCHOOLS_CONFIRMATION_2026) {
        $total_page = 5;
        $engage_field = FIELD_NEW_CONF_ENGAGE;
        $num_participating_students = rgpost('input_' . FIELD_NEW_CONF_NUM_STUDENTS);
    } elseif ($form_id == FORM_EXISTING_SCHOOLS_CONFIRMATION_2026) {
        $total_page = 6;
        $engage_field = FIELD_EXIST_CONF_ENGAGE;
        $school_type = rgpost('input_' . FIELD_EXIST_CONF_SCHOOL_TYPE);
        if ($school_type === 'Primary') {
            $num_participating_students = rgpost('input_' . FIELD_EXIST_CONF_NUM_STUDENTS);
        } elseif ($school_type === 'Secondary') {
            if (rgpost('input_' . FIELD_EXIST_CONF_LESSON_FORMAT) === 'Journals') {
                $using_journals = true;
                $num_participating_students = rgpost('input_' . FIELD_EXIST_CONF_NUM_STUDENTS);
            } else {
                $using_journals = false;
                $using_planners = true;
                $num_participating_planner_students = rgpost('input_' . FIELD_EXIST_CONF_NUM_STUDENTS);
            }

        } else {
            if (rgpost('input_' . FIELD_EXIST_CONF_LESSON_FORMAT) === 'Journals') {
                // whole school using journals
                $using_journals = true;
                $num_participating_students = rgpost('input_' . FIELD_EXIST_CONF_NUM_STUDENTS);
            } else {
                // primary using journals, secondary using planners;
                $using_planners = true;
                $num_participating_students = rgpost('input_' . FIELD_EXIST_CONF_PRIMARY_STUDENTS);
                $num_participating_planner_students = rgpost('input_' . FIELD_EXIST_CONF_SECONDARY_STUDENTS);
            }
        }
    } elseif ($form_id == FORM_EARLY_YEARS_CONFIRMATION_2025) {
        $total_page = 4;
        $engage_field = FIELD_EY_CONF_ENGAGE;
        $num_participating_students = rgpost('input_' . FIELD_EY_CONF_NUM_CHILDREN);
        $students_label = 'children';
    }

    if (GFFormDisplay::get_current_page($form_id) == $total_page) {
        $fields = $form['fields'];
        foreach ($form['fields'] as &$field) {
            if ($using_journals) {
                if ($field->id == $engage_field) {
                    $field->description = '$20 x '.$num_participating_students.' participating '.$students_label;
                }
            }
            if ($using_planners) {
                if ($field->id == FIELD_EXIST_CONF_PLANNER_PRICE) {
                    $field->description = '$14 x '.$num_participating_planner_students.' participating '.$students_label;
                }
            }
        }
    }
    return $form;
}

add_filter('gform_field_content_' . FORM_EXISTING_SCHOOLS_CONFIRMATION_2026, function ($field_content, $field) {
    if ($field->id == FIELD_EXIST_CONF_PRIMARY_STUDENTS) {
        return str_replace('primary school', "<span class='emph-engage'>primary school</span>", $field_content);
    }
    if ($field->id == FIELD_EXIST_CONF_SECONDARY_STUDENTS) {
        return str_replace('secondary school', "<span class='emph-engage'>secondary school</span>", $field_content);
    }

    return $field_content;
}, 10, 2);


add_filter('gform_pre_render_' . FORM_EXISTING_SCHOOLS_CONFIRMATION_2026, 'list_extend_options');

function list_extend_options($form)
{
    $form_id = $form['id'];
    if (GFFormDisplay::get_current_page($form_id) != 6) {
        return $form;
    }

    $extend_description = [];

    $extend_options = [
        'Teacher Wellbeing Program' => ['input_50_1'],
        'Teacher Wellbeing 1' => ['input_80_1', 'input_96_1', 'input_96_2', 'input_97_1', 'input_97_2'],
        'Teacher Wellbeing 2' => ['input_81_1', 'input_100_1', 'input_100_2', 'input_101_1', 'input_101_2'],
        'Teacher Wellbeing 3' => ['input_103_1', 'input_83_1', 'input_83_2', 'input_104_1', 'input_104_2'],
        'Digital Wellbeing for Families' => ['input_106_1', 'input_107_1', 'input_107_2', 'input_95_1', 'input_95_2'],
        'Building Resilience at Home' => ['input_110_1', 'input_109_1', 'input_109_2', 'input_94_1', 'input_94_2'],
        'Feeling ACE with Hugh' => ['input_90_1'],
        'Feeling ACE with Martin' => ['input_90_2'],
        'Connected Parenting with Lael Stone' => ['input_53_1'],
    ];


    foreach ($extend_options as $extend_name => $fields) {
        foreach ($fields as $field) {
            $value = rgpost($field);
            if ($value) {
                $price = substr($value, strpos($value, '$'));
                if ($price === '$0') {
                    $price .= ' - Included with Inspire';
                }
                $type = '';
                if (strpos($value, 'Webinar') !== false) {
                    $type = ' (Online Webinar)';
                }
                if (strpos($value, 'Workshop') !== false) {
                    $type = ' (In Person Workshop)';
                }
                array_push($extend_description, $extend_name . $type . ' '. $price);
            }
        }
    }

    $in_person = ['input_96_2', 'input_100_2', 'input_83_2', 'input_107_2', 'input_109_2'];
    $travel_costs = false;
    foreach ($in_person as $field) {
        $value = rgpost($field);
        if ($value) {
            $travel_costs = true;
            break;
        }
    }

    $fields = $form['fields'];
    foreach ($form['fields'] as &$field) {
        if ($field->id == FIELD_EXIST_CONF_EXTEND_SUMMARY) {
            $field->description = implode('<br/>', $extend_description);
        }
        if ($field->id == FIELD_EXIST_CONF_TOTAL_EXCL and $travel_costs) {
            $field->description = 'Excluding applicable travel costs for In Person Workshops and GST';
        }
    }


    return $form;
}
