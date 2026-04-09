<?php

require_once __DIR__ . '/form_ids.php';

/*==========================================================================
 * Table of Contents
 * -------------------------------------------------------------------------
 * 1. Theme Setup ................. enqueue styles, dashboard, admin
 * 2. Date & Event Forms .......... forms 70, 72
 * 3. Confirmation Forms .......... forms 76, 80, 29 (deal prepopulation, pricing)
 * 4. LTRP & Culture Assessment ... form 86 (progress steps, save/continue, org data)
 * 5. Curriculum Ordering ......... form 89 (multi-page, shipping, validation)
 * 6. Confirmation Pricing ........ forms 76, 80, 29 (pricing page, extend options)
 * 7. Cross-Form Validation ....... school name input validation (12 forms)
 * 8. Shared Helpers .............. progress bar, default qty, readonly, sessions
 * 9. Footer Assets ............... GF Address Enhanced plugin
 *========================================================================*/

/*--------------------------------------------------------------------------
 * 1. Theme Setup
 *------------------------------------------------------------------------*/

/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts()
{
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        [
            'hello-elementor-theme-style',
        ],
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20);

/* Disable Gutenburg */
add_filter('use_block_editor_for_post', '__return_false');

/* Support */
function remove_footer_admin()
{

    echo 'Website by <a href="https://macedondigital.com.au/" target="_blank">Macedon Digital</a> | Contact us for support <a href="mailto:info@macedondigital.com.au" target="_blank">info@macedondigital.com.au</a></p>';

}

add_filter('admin_footer_text', 'remove_footer_admin');

/**
 * DM Website Support Dashboard Widget
 */

add_action('wp_dashboard_setup', 'md_custom_dashboard_widgets');

function md_custom_dashboard_widgets()
{
    global $wp_meta_boxes;

    wp_add_dashboard_widget('custom_help_widget', 'Theme Support', 'custom_dashboard_help');
}

function custom_dashboard_help()
{
    echo '<p>Welcome to the TRP Forms website! Need help? Contact Macedon Digital - <a href="mailto:info@macedondigital.com.au">info@macedondigital.com.au</a></p>';
}


/**
* Remove Annoying WordPress Dashboard Widgets
*/

add_action('wp_dashboard_setup', 'md_remove_dashboard_widgets');

function md_remove_dashboard_widgets()
{

    remove_meta_box('dashboard_primary', 'dashboard', 'side'); // WordPress.com Blog
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal'); // Plugins
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); // Right Now
    remove_action('welcome_panel', 'wp_welcome_panel'); // Welcome Panel
    remove_action('try_gutenberg_panel', 'wp_try_gutenberg_panel'); // Try Gutenberg
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side'); // Quick Press widget
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side'); // Recent Drafts
    remove_meta_box('dashboard_secondary', 'dashboard', 'side'); // Other WordPress News
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal'); //Incoming Links
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // Recent Comments
    remove_meta_box('dashboard_activity', 'dashboard', 'normal'); // Activity
    remove_meta_box('e-dashboard-overview', 'dashboard', 'normal'); //Remove Elementor
}

/**
* Remove Rank Math footer message
*/

add_action('rank_math/whitelabel', '__return_true');


add_action('wp_dashboard_setup', 'md_remove_dashboard_widget');
/**
 *  Remove Site Health Dashboard Widget
 *
 */
function md_remove_dashboard_widget()
{
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
}

/**
 *  Remove Site Title from all pages
 *
 */

function ele_disable_page_title($return)
{
    return false;
}
add_filter('hello_elementor_page_title', 'ele_disable_page_title');

/*--------------------------------------------------------------------------
 * 2. Date & Event Forms (70, 72)
 *------------------------------------------------------------------------*/

// Date acceptance form
add_filter('gform_pre_render_' . FORM_DATE_ACCEPTANCE_2025, 'populate_date_acceptance');
function populate_date_acceptance($form)
{
    if (GFFormDisplay::get_current_page($form['id']) == 2) {
        require_once('populate_dates.php');
        $dates = new TrpDates($form);
        $dates->populate_form();
    }

    return $form;
}

// Event confirmation form
add_filter('gform_pre_render_' . FORM_EVENT_CONFIRMATION_2025, 'populate_event_acceptance');
function populate_event_acceptance($form)
{
    if (GFFormDisplay::get_current_page($form['id']) == 2) {
        require_once('populate_event_date.php');
        $dates = new TrpSingleDates($form);
        $dates->populate_form();
    }

    return $form;
}

/*--------------------------------------------------------------------------
 * 3. Confirmation Forms — Deal Prepopulation (76, 80, 29)
 *------------------------------------------------------------------------*/

// Populate school confirmation form fields
add_filter('gform_pre_render_' . FORM_NEW_SCHOOLS_CONFIRMATION_2026, 'populate_deal_confirmed');
add_filter('gform_pre_render_' . FORM_EXISTING_SCHOOLS_CONFIRMATION_2026, 'populate_deal_confirmed');
add_filter('gform_pre_render_' . FORM_EARLY_YEARS_CONFIRMATION_2025, 'populate_deal_confirmed');
function populate_deal_confirmed($form)
{
    $form_id = $form['id'];
    if ($form_id == FORM_NEW_SCHOOLS_CONFIRMATION_2026) {
        $school_account_no_field = 'input_226';
        $school_name_field = 'input_229';
        $deal_confirmed_input = '"#input_' . FORM_NEW_SCHOOLS_CONFIRMATION_2026 . '_183"';
    } elseif ($form_id == FORM_EXISTING_SCHOOLS_CONFIRMATION_2026) {
        $school_account_no_field = 'input_5';
        $school_name_field = 'input_8';
        $deal_confirmed_input = '"#input_' . FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 . '_49"';
    } elseif ($form_id == FORM_EARLY_YEARS_CONFIRMATION_2025) {
        $deal_confirmed_input = '"#input_' . FORM_EARLY_YEARS_CONFIRMATION_2025 . '_183"';
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
            $school_account_no = rgpost('input_233');
            $school_name = rgpost('input_5');
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
							jQuery("#input_<?php echo FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 ?>_61").val(freeTravel);
							jQuery("#input_<?php echo FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 ?>_62").val(f2f);
							jQuery("#input_<?php echo FORM_EXISTING_SCHOOLS_CONFIRMATION_2026 ?>_118").val(funded);
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
 * 4. LTRP & Culture Assessment (86)
 *------------------------------------------------------------------------*/

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

        $org_id = rgpost('input_13');

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
            $_POST['input_86'] = 'YES';
            return $form;
        }



        $_POST['input_14'] = $json_response['data']['state'];
        $_POST['input_3'] = $json_response['data']['name'];
        $_POST['input_67'] = $json_response['data']['id'];
        $_POST['input_10'] = $json_response['data']['ltrp'] ? 'YES' : 'NO';
        $_POST['input_85'] = $json_response['data']['ca'] ? 'YES' : 'NO';
        $_POST['input_89'] = $json_response['data']['participants'];
        $_POST['input_86'] = 'NO'; // error field

        foreach ($form['fields'] as &$field) {
            if ($field['id'] == 18) {
                $field['content'] = 'Welcome, ' . $json_response['data']['name'];
            }
        }

    }


    return $form;
}

add_filter('gform_field_content_' . FORM_LTRP_AND_CA_2026, function ($field_content, $field) {
    if ($field->id == 28) {
        return str_replace('fortnight', "<span class='emph-wording'>fortnight</span>", $field_content);
    }

    if ($field->id == 32) {
        return str_replace('week', "<span class='emph-wording'>week</span>", $field_content);
    }

    if ($field->id == 37) {
        return str_replace('weekly', "<span class='emph-wording'>weekly</span>", $field_content);
    }

    if ($field->id == 41) {
        return str_replace('daily', "<span class='emph-wording'>daily</span>", $field_content);
    }

    if ($field->id == 39) {
        return str_replace('some', "<span class='emph-wording'>some</span>", $field_content);
    }

    if ($field->id == 43) {
        return str_replace('most', "<span class='emph-wording'>most</span>", $field_content);
    }

    if ($field->id == 47) {
        return str_replace('semesterly', "<span class='emph-wording'>semesterly</span>", $field_content);
    }

    if ($field->id == 49) {
        return str_replace('termly', "<span class='emph-wording'>termly</span>", $field_content);
    }

    if ($field->id == 48) {
        return str_replace('Some', "<span class='emph-wording'>Some</span>", $field_content);
    }

    if ($field->id == 51) {
        return str_replace('All', "<span class='emph-wording'>All</span>", $field_content);
    }

    if ($field->id == 53) {
        return str_replace('year', "<span class='emph-wording'>year</span>", $field_content);
    }
    if ($field->id == 55) {
        return str_replace('semester', "<span class='emph-wording'>semester</span>", $field_content);
    }

    if ($field->id == 58) {
        return str_replace('termly', "<span class='emph-wording'>termly</span>", $field_content);
    }
    if ($field->id == 60) {
        return str_replace('fortnightly', "<span class='emph-wording'>fortnightly</span>", $field_content);
    }
    if ($field->id == 61) {
        return str_replace('each semester', "<span class='emph-wording'>each semester</span>", $field_content);
    }

    return $field_content;
}, 10, 2);

/*--------------------------------------------------------------------------
 * 5. Curriculum Ordering (89)
 *------------------------------------------------------------------------*/

add_filter('gform_pre_render_' . FORM_CURRICULUM_RESOURCE_ORDERING_2026, 'populate_curric_form_data_2026');
function populate_curric_form_data_2026($form)
{
    $form_id = $form['id'];
    $school_account_no_field = 'input_174';
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
						jQuery("#input_89_152").val(engageCombo);
						jQuery("#input_89_154").val(freeShipping);
						jQuery("#input_89_158").val(fundedSchool);
						jQuery("#input_89_169").val(newSchool);
						jQuery(document).trigger('gform_post_render', [89, 2]);
					}
				}
			});
		</script>
		<?php
    }


    if (GFFormDisplay::get_current_page($form_id) == 5) {
        $free_shipping = rgpost('input_154');
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
        $_POST['input_161'] = $shipping_price;

        $fields = $form['fields'];
        $description = '';

        $description .= '<h4>School Details</h4>';
        $description .= "<table class='school-details'>";
        $description .= '<tr><td>School Name</td><td>'.GFAPI::get_field($form_id, 174)->choices[0]['text'].'</td></tr>';
        $description .= '<tr><td>Your Name</td><td>'.rgpost('input_170_3'). ' '. rgpost('input_170_6').'</td></tr>';

        $description .= '<tr><td>Shipping Address</td><td>';
        $description .= rgpost('input_99_1') .'<br/>';
        if (rgpost('input_99_2')) {
            $description .= rgpost('input_99_2') .'<br/>';
        }
        $description .= rgpost('input_99_3') .'<br/>';
        $description .= rgpost('input_100') .' ';
        $description .= rgpost('input_99_5');
        $description .= '</td></tr>';

        $description .= '<tr><td>Billing Address</td><td>';
        if (rgpost('input_101_1')) {
            $description .= 'Same as Shipping Address';
        } else {
            $description .= rgpost('input_102_1') .'<br/>';
            if (rgpost('input_102_2')) {
                $description .= rgpost('input_102_2') .'<br/>';
            }
            $description .= rgpost('input_102_3') .'<br/>';
            $description .= rgpost('input_103') .' ';
            $description .= rgpost('input_102_5');
            $description .= '</td></tr>';
        }

        $description .= '<tr><td>PO Number</td><td>'.rgpost('input_109').'</td></tr>';

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
        $teacher_planner_num = rgpost('input_143_3');
        if ($teacher_planner_num > 0) {
            $teacher_planner_details = explode(' - ', rgpost('input_144'));
            $teacher_planner_type = $teacher_planner_details[0];
            $extra_table .= '<tr><td>Teacher Planner ('.$teacher_planner_type.')</td><td>'.$teacher_planner_num.'</td></tr>';
        }

        $senior_planner_num = rgpost('input_175_3');
        if ($senior_planner_num > 0) {
            $senior_planner_details = explode(' - ', rgpost('input_176'));
            $senior_planner_type = $senior_planner_details[0];
            $extra_table .= '<tr><td>Senior Planner '.$senior_planner_type.'</td><td>'.$senior_planner_num.'</td></tr>';
        }

        $teacher_seminar_num = rgpost('input_179_3');
        if ($teacher_seminar_num > 0) {
            $teacher_seminar_details = explode('|', rgpost('input_181'));
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
        if (rgpost('input_158') === 'YES') {
            // free shipping for funded schools
            $description .= '<tr><td>Shipping</td><td>$0.00</td></tr>';
        } else {
            $description .= '<tr><td>Shipping</td><td>'. $displayed_shipping_price .'</td></tr>';
        }
        $description .= '</table>';

        foreach ($form['fields'] as &$field) {
            if ($field['id'] == 160) {
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
    if (!empty(rgpost('input_106'))) {
        $is_valid = DateTime::createFromFormat('d/m/Y', rgpost('input_106')) <= DateTime::createFromFormat('d/m/Y', '31/12/2025');
        if (!$is_valid) {
            // set the form validation to false
            $validation_result['is_valid'] = false;

            //finding Field with ID of 1 and marking it as failed validation
            foreach ($form['fields'] as &$field) {

                //NOTE: replace 1 with the field you would like to validate
                if ($field->id == '106') {
                    $field->failed_validation = true;
                    $field->validation_message = 'Please enter a date before 31/12/2025';
                    break;
                }
            }
        }


    }

    // first date in 2026
    if (!empty(rgpost('input_107'))) {
        $is_valid = DateTime::createFromFormat('d/m/Y', rgpost('input_107')) >= DateTime::createFromFormat('d/m/Y', '01/01/2026');
        if (!$is_valid) {
            // set the form validation to false
            $validation_result['is_valid'] = false;

            //finding Field with ID of 1 and marking it as failed validation
            foreach ($form['fields'] as &$field) {

                //NOTE: replace 1 with the field you would like to validate
                if ($field->id == '107') {
                    $field->failed_validation = true;
                    $field->validation_message = 'Please enter a date after 01/01/2026';
                    break;
                }
            }
        }


    }



    // primary reading log min qty = 10
    if (rgpost('input_28_3') < 10 and !empty(rgpost('input_28_3'))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach ($form['fields'] as &$field) {

            //NOTE: replace 1 with the field you would like to validate
            if ($field->id == '28') {
                $field->failed_validation = true;
                $field->validation_message = 'Minumum 10 items are required';
                break;
            }
        }

    }

    // primary student planner min qty = 10
    if (rgpost('input_36_3') < 10 and !empty(rgpost('input_36_3'))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach ($form['fields'] as &$field) {

            //NOTE: replace 1 with the field you would like to validate
            if ($field->id == '36') {
                $field->failed_validation = true;
                $field->validation_message = 'Minumum 10 items are required';
                break;
            }
        }

    }

    // senior student planner min qty = 10
    if (rgpost('input_175_3') < 10 and !empty(rgpost('input_175_3'))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach ($form['fields'] as &$field) {

            //NOTE: replace 1 with the field you would like to validate
            if ($field->id == '175') {
                $field->failed_validation = true;
                $field->validation_message = 'Minumum 10 items are required';
                break;
            }
        }

    }

    // teacher sem max qty = 10
    if (rgpost('input_179_3') > 30 and !empty(rgpost('input_179_3'))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach ($form['fields'] as &$field) {

            //NOTE: replace 1 with the field you would like to validate
            if ($field->id == '179') {
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


/*--------------------------------------------------------------------------
 * 6. Confirmation Pricing & Extend Options (76, 80, 29)
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
        $engage_field = 221;
        $num_participating_students = rgpost('input_219');
    } elseif ($form_id == FORM_EXISTING_SCHOOLS_CONFIRMATION_2026) {
        $total_page = 6;
        $engage_field = 54;
        $school_type = rgpost('input_117');
        if ($school_type === 'Primary') {
            $num_participating_students = rgpost('input_27');
        } elseif ($school_type === 'Secondary') {
            if (rgpost('input_29') === 'Journals') {
                $using_journals = true;
                $num_participating_students = rgpost('input_27');
            } else {
                $using_journals = false;
                $using_planners = true;
                $num_participating_planner_students = rgpost('input_27');
            }

        } else {
            if (rgpost('input_29') === 'Journals') {
                // whole school using journals
                $using_journals = true;
                $num_participating_students = rgpost('input_27');
            } else {
                // primary using journals, secondary using planners;
                $using_planners = true;
                $num_participating_students = rgpost('input_127');
                $num_participating_planner_students = rgpost('input_128');
            }
        }
    } elseif ($form_id == FORM_EARLY_YEARS_CONFIRMATION_2025) {
        $total_page = 4;
        $engage_field = 44;
        $num_participating_students = rgpost('input_210');
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
                if ($field->id == 121) {
                    $field->description = '$14 x '.$num_participating_planner_students.' participating '.$students_label;
                }
            }
        }
    }
    return $form;
}

add_filter('gform_field_content_' . FORM_EXISTING_SCHOOLS_CONFIRMATION_2026, function ($field_content, $field) {
    if ($field->id == 127) {
        return str_replace('primary school', "<span class='emph-engage'>primary school</span>", $field_content);
    }
    if ($field->id == 128) {
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
        if ($field->id == 111) {
            $field->description = implode('<br/>', $extend_description);
        }
        if ($field->id == 15 and $travel_costs) {
            $field->description = 'Excluding applicable travel costs for In Person Workshops and GST';
        }
    }


    return $form;
}

/*--------------------------------------------------------------------------
 * 7. Cross-Form Validation — School Name Input
 *------------------------------------------------------------------------*/

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
            FORM_SCHOOL_PRIZE_PACK                  => ['dropdown' => '5',   'checkbox' => 'input_7_1'],
            FORM_NEW_SCHOOLS_CONFIRMATION_2026      => ['dropdown' => '226', 'checkbox' => 'input_227_1', 'page' => 1],
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


/*--------------------------------------------------------------------------
 * 8. Shared Helpers (progress bar, default qty, readonly, sessions)
 *------------------------------------------------------------------------*/

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

// School Info Sessions
foreach (['gform_pre_render_', 'gform_pre_validation_', 'gform_pre_submission_filter_', 'gform_admin_pre_render_'] as $hook) {
    add_filter($hook . FORM_SCHOOL_INFO_SESSION_2026, function ($form) {
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

/*--------------------------------------------------------------------------
 * 9. Footer Assets
 *------------------------------------------------------------------------*/

// Show progress bar after form render (hidden initially by hide_progress_bar_wrap)
add_action('wp_footer', function () {
    ?>
    <script>
    jQuery(document).bind('gform_post_render', function (event, formId, current_page) {
        jQuery("div.wrap_progress_bar").css({'visibility':'visible', 'display':''});
    });
    </script>
    <?php
});

// GF Address Enhanced plugin assets
add_action('wp_footer', 'enqueue_gf_address_enhanced_assets');
function enqueue_gf_address_enhanced_assets()
{
    // Load the assets from a separate file to keep functions.php clean
    $asset_file = __DIR__ . '/gf-address-enhanced-assets.php';
    if (file_exists($asset_file)) {
        include $asset_file;
    }
}
