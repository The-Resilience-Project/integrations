<?php


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

// Date acceptance form
add_filter('gform_pre_render_70', 'populate_date_acceptance');
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
add_filter('gform_pre_render_72', 'populate_event_acceptance');
function populate_event_acceptance($form)
{
    if (GFFormDisplay::get_current_page($form['id']) == 2) {
        require_once('populate_event_date.php');
        $dates = new TrpSingleDates($form);
        $dates->populate_form();
    }

    return $form;
}

// Populate 2025 school confirmation form fields
add_filter('gform_pre_render_76', 'populate_deal_confirmed');
add_filter('gform_pre_render_80', 'populate_deal_confirmed');
add_filter('gform_pre_render_29', 'populate_deal_confirmed');
function populate_deal_confirmed($form)
{
    $form_id = $form['id'];
    if ($form_id == 76) {
        $school_account_no_field = 'input_226';
        $school_name_field = 'input_229';
        $deal_confirmed_input = '"#input_76_183"';
    } elseif ($form_id == 80) {
        $school_account_no_field = 'input_5';
        $school_name_field = 'input_8';
        $deal_confirmed_input = '"#input_80_49"';
    } elseif ($form_id == 29) {
        $deal_confirmed_input = '"#input_29_183"';
    }
    if (GFFormDisplay::get_current_page($form_id) == 2) {
        if ($form_id == 76 or $form_id == 80) {
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
            ?>console.log(<?php echo $school_account_no . ' '. $school_name ?>)<?php
            if (!empty($school_account_no)) {
                $url = '"https://theresilienceproject.com.au/resilience/api/ey_confirmation_form_details.php/?school_account_no='.$school_account_no.'"';
            } else {
                $url = '"https://theresilienceproject.com.au/resilience/api/ey_confirmation_form_details.php/?school_name='.$school_name.'"';
            }

            //$url = '"https://theresilienceproject.com.au/resilience/api/ey_confirmation_form_details.php/?service_name='.$school_name.'"';
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
					    console.log(prepopulateData)
						// deal_status
						const dealConfirmed = ["Deal Won", "Closed INV"].includes(prepopulateData.deal_status) ? "YES" : "NO"
						jQuery(dealConfirmedInput).val(dealConfirmed);
						if(formId === 80){
							const freeTravel = prepopulateData.free_travel === "1" ? "YES" : "NO"
							const f2f = prepopulateData.f2f ? "YES" : "NO"
							const funded = prepopulateData.funded_years.includes("2026") ? "YES" : "NO"
							jQuery("#input_80_61").val(freeTravel);
							jQuery("#input_80_62").val(f2f);
							jQuery("#input_80_118").val(funded);
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

add_filter('gform_progress_steps_86', 'add_one_more_step', 10, 3);
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
add_filter('gform_savecontinue_link_86', function ($save_button, $form) {
    $form_id            = $form['id'];
    // $button             = rgars( $form, 'save/button' );
    // $button['type']     = 'image';
    // $button['imageUrl'] = 'the/url/here';
    $page_number = GFFormDisplay::get_current_page($form_id);

    if ($page_number == 1 || $page_number == 2) {
        return null;
    }
    return '<div class="footer-spacer"></div>'.$save_button;

}, 10, 2);

add_filter('gform_pre_render_86', 'populate_org_name_state_ltrp');
function populate_org_name_state_ltrp($form)
{
    $form_id = $form['id'];
    if (GFFormDisplay::get_current_page($form_id) == 2) {

        $org_id = rgpost('input_13');

        $request_header = [];
        // $request_header[] = "token: DdtiDMSsq9ETjSe2FMEZBICu";
        // $request_header[] = "Content-Type: application/json";

        $request_method = 'GET';

        $request_handle = curl_init('https://theresilienceproject.com.au/resilience/api/school_ltrp_details.php/?org_id='.$org_id);
        curl_setopt_array($request_handle, [
            CURLOPT_CUSTOMREQUEST => $request_method,
            // CURLOPT_POSTFIELDS => json_encode(array("organisationId" => $org_id)),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request_header,
        ]);

        $response = curl_exec($request_handle);
        $json_response = json_decode($response, true);

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

add_filter('gform_field_content_86', function ($field_content, $field) {
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

add_filter('gform_pre_render_63', 'populate_curric_form_data');
function populate_curric_form_data($form)
{
    $form_id = $form['id'];
    $school_account_no_field = 'input_174';
    if (GFFormDisplay::get_current_page($form_id) == 2) {
        error_log('here');
        $school_account_no = rgpost($school_account_no_field);
        $url = '"https://theresilienceproject.com.au/resilience/api/school_curric_ordering_details.php/?school_account_no='.$school_account_no.'"';
        ?>
		<script>
			jQuery.ajax({
				url:<?php echo $url ?>,
				method:"GET",
				dataType: "JSON",
				success: function(result){
				    // console.log(result)
					const prepopulateData = result.data;
					if(prepopulateData) {
					    console.log(prepopulateData)
						const plannerOnlySchool = prepopulateData.engage === "Planners" ? "YES" : "NO";
						const freeShipping = prepopulateData.free_shipping ? "YES" : "NO";
						const fundedSchool = prepopulateData.funded_years.includes("2025") ? "YES" : "NO"
						const newSchool = prepopulateData.deal_type === "School - New" ? "YES" : "NO"
						jQuery("#input_63_152").val(plannerOnlySchool);
						jQuery("#input_63_154").val(freeShipping);
						jQuery("#input_63_158").val(fundedSchool);
						jQuery("#input_63_169").val(newSchool);
						jQuery(document).trigger('gform_post_render', [63, 2]);
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
        $planner_only = rgpost('input_152');

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

        $description .= '<tr><td>Last date for deliveries in 2024</td><td>'.rgpost('input_106').'</td></tr>';
        $description .= '<tr><td>First date for deliveries in 2025</td><td>'.rgpost('input_107').'</td></tr>';
        $description .= '<tr><td>PO Number</td><td>'.rgpost('input_109').'</td></tr>';

        $description .= '</table><br/>';

        $student_table = '';

        if ($planner_only === 'NO') {
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
                ['Year 8', 'input_18_3'],
                ['Year 9', 'input_19_3'],
                ['Year 10', 'input_20_3'],
                ['Year 11', 'input_21_3'],
                ['Year 12', 'input_22_3'],


            ];
            foreach ($year_levels as [$year_level, $input_name]) {
                $num = rgpost($input_name);
                if ($num > 0) {
                    $student_table .= '<tr><td>'.$year_level.'</td><td>'.$num.'</td></tr>';
                }
            }
            if ($student_table !== '') {
                $description .= '<h4>Student Curriculum</h4>';
                $description .= '<table><tr><th>Description</th><th>Quantity</th></tr>';
                $description .= $student_table;
                $description .= '<tr><td>Total</td><td>'.rgpost('input_166').'</td></tr>';

                $description .= '</table><br/>';
            }
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
        foreach ($year_levels as [$year_level, $input_name]) {
            $num = rgpost($input_name);
            if ($num > 0) {
                $teacher_table .= '<tr><td>'.$year_level.'</td><td>'.$num.'</td></tr>';
            }
        }
        if ($teacher_table !== '') {
            $description .= '<h4>Hard Copy Teacher Resources</h4>';
            $description .= '<table><tr><th>Description</th><th>Quantity</th></tr>';
            $description .= $teacher_table;
            $description .= '<tr><td>Total</td><td>'.rgpost('input_167').'</td></tr>';
            $description .= '</table><br/>';
        }

        // extra
        $extra_table = '';
        $shop_items = [
            ['Primary Reading Log', 'input_28_2', 'input_28_3'],
            ['Primary Student Planner', 'input_36_2', 'input_36_3'],
            ['GEM Conversation Cards', 'input_29_2', 'input_29_3'],
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
            $planner_details = explode(' - ', rgpost('input_144'));
            $planner_type = $planner_details[0];
            $planner_price = explode('|', $planner_details[1])[0];
            $extra_table .= '<tr><td>Teacher Planner ('.$planner_type.')</td><td>'.$teacher_planner_num.'</td></tr>';
        }
        if ($extra_table !== '') {
            $description .= '<h4>Extra Resources</h4>';
            $description .= '<table><tr><th>Description</th><th>Quantity</th></tr>';
            $description .= $extra_table;
            $description .= '</table><br/>';
        }
        $description .= '<h4>Shipping</h4>';
        if ($shipping_price == 0) {
            $description .= '<p><i>Great news! Since you placed your first order before 8th November, this additional order qualifies for free shipping!</i></p>';
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

add_filter('gform_pre_render_89', 'populate_curric_form_data_2026');
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
				    // console.log(result)
					const prepopulateData = result.data;
					if(prepopulateData) {
					    console.log(prepopulateData)
					    let engageCombo = "Journal Only"
					    if(prepopulateData.engage.includes('Journals') && prepopulateData.engage.includes('Planners')){
					        engageCombo = "Journals and Planners"
					    } else if (!prepopulateData.engage.includes('Journals') && prepopulateData.engage.includes('Planners')){
					        engageCombo = "Planner Only"
					    } 
					    console.log(engageCombo)
				// 	    const journalSchool = prepopulateData.engage.includes('Journals') ? "YES" : prepopulateData.engage === "" ? "YES" : "NO";
				// 		const plannerSchool = prepopulateData.engage.includes('Planners') ? "YES" : "NO" 
				        // const journalSchool = "YES"
				        // const plannerSchool = "YES"
						const freeShipping = prepopulateData.free_shipping ? "YES" : "NO";
						const fundedSchool = prepopulateData.funded_years.includes("2026") ? "YES" : "NO"
						const newSchool = prepopulateData.deal_type === "School - New" ? "YES" : "NO"
				// 		jQuery("#input_89_184").val(journalSchool);
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
        // 		$engage_combo = rgpost("input_152");

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

        $description .= '<tr><td>Last date for deliveries in 2025</td><td>'.rgpost('input_106').'</td></tr>';
        $description .= '<tr><td>First date for deliveries in 2026</td><td>'.rgpost('input_107').'</td></tr>';
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
            $description .= '<p><i>Great news! Since you placed this order early, an additional order will qualify for free shipping!</i></p>';
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

add_filter('gform_pre_render_69', 'calculate_shipping_for_spms');
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

add_filter('gform_validation_63', 'curric_ordering_validation');
add_filter('gform_validation_89', 'curric_ordering_validation');
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

    if ($form['id'] === 63) {
        $validation_result['form'] = $form;
        return $validation_result;
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


// update new schools price page
add_filter('gform_pre_render_76', 'new_schools_confirmation_pricing_page');
add_filter('gform_pre_render_80', 'new_schools_confirmation_pricing_page');
add_filter('gform_pre_render_29', 'new_schools_confirmation_pricing_page');


function new_schools_confirmation_pricing_page($form)
{
    $form_id = $form['id'];
    $price = '$20';
    $students_label = 'students';
    $using_journals = true;
    $using_planners = false;
    if ($form_id == 76) {
        $total_page = 5;
        $engage_field = 221;
        $num_participating_students = rgpost('input_219');
    } elseif ($form_id == 80) {
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
    } elseif ($form_id == 29) {
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

add_filter('gform_field_content_80', function ($field_content, $field) {
    if ($field->id == 127) {
        return str_replace('primary school', "<span class='emph-engage'>primary school</span>", $field_content);
    }
    if ($field->id == 128) {
        return str_replace('secondary school', "<span class='emph-engage'>secondary school</span>", $field_content);
    }

    return $field_content;
}, 10, 2);


add_filter('gform_pre_render_80', 'list_extend_options');

function list_extend_options($form)
{
    $form_id = $form['id'];
    if (GFFormDisplay::get_current_page($form_id) != 6) {
        return $form;
    }

    $extend_description = [];

    // 	$selected_comp_extend = rgpost("input_38");
    // 	if ($selected_comp_extend){
    // 	    $mapping = array(
    //     	    "Hugh Staff AC Webinar\$0" => "Feeling ACE with Hugh (Online Webinar) \$0 - Included with Inspire",
    //             "Martin Staff AC Webinar\$0" => "Authentic Connection for Staff with Martin (Online Webinar) \$0 - Included with Inspire",
    //     	    "Connected Parenting Webinar\$0" => "Connected Parenting with Lael Stone (Online Webinar) \$0 - Included with Inspire",
    //     	    "Building Resilience at Home Webinar\$0" => "Building Resilience at Home (Online Webinar) \$0 - Included with Inspire",
    //     	    "Hugh Parent Webinar\$0" => "Authentic Connection for Parents/Carers with Hugh (Online Webinar) \$0 - Included with Inspire",
    //             "Martin Parent Webinar\$0" => "Authentic Connection for Parents/Carers with Martin (Online Webinar) \$0 - Included with Inspire",
    //         );
    //         $show_comp_extend = $mapping[$selected_comp_extend];
    //         array_push($extend_description, $show_comp_extend);

    // 	}

    $extend_options = [
        'Teacher Wellbeing Program' => ['input_50_1'],
        'Teacher Wellbeing 1' => ['input_80_1', 'input_96_1', 'input_96_2', 'input_97_1', 'input_97_2'],
        'Teacher Wellbeing 2' => ['input_81_1', 'input_100_1', 'input_100_2', 'input_101_1', 'input_101_2'],
        'Teacher Wellbeing 3' => ['input_103_1', 'input_83_1', 'input_83_2', 'input_104_1', 'input_104_2'],
       // "Authentic Connection for Teachers with Hugh" => array("input_85_1"),
       // "Autentic Connection for Teachers with Martin" => array("input_85_2"),
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

// format comp extend on existing schools page
// add_filter( 'gform_field_choice_markup_pre_render_80_38', 'describe_comp_extend', 10, 4 );
function describe_comp_extend($choice_markup, $choice, $field, $value)
{
    $open_div = '<div style="display: grid;grid-template-columns: 2fr 1fr;gap: 30px;">';
    $connected_parenting = '
<div class="inspire-description curriculum-description" style="padding-bottom: 0; border-bottom: 0">
    <h5 class="blue-text">
            Connected Parenting with Lael Stone
    </h5>
    <details>
        <summary>
            Click for more information
        </summary>
        <b>60 minutes | Parents and Carers</b><br/>
        This Connected Parenting webinar with educator and parenting expert, Lael Stone, provides parents and carers with:
        <ul>
            <li> Practical strategies to build cooperation and stronger connections with children.</li>
            <li>Ideas to support children when they are facing adversity.</li>
            <li> Ways to assist children in building emotional resilience.</li>
            <li>Tips to deal with our own triggers as parents or carers.</li>
        </ul>
    </details>
</div>';

    $trp_in_action = '
<div class="inspire-description curriculum-description" style="padding-bottom: 0; border-bottom: 0">
    <h5 class="blue-text">
            Building Resilience at Home <i>(formerly known as TRP in Action)</i>
    </h5>
    <details>
        <summary>
            Click for more information
        </summary>
        <b>60 minutes | Parents and Carers </b><br/>
        As we know, a whole school approach is key to supporting student wellbeing. This session is designed to provide a way to connect the
classroom to home, providing parents/carers with:
        <ul>
            <li> Knowledge of the TRP program and how it is run in your school.</li>
            <li>Tips and strategies to support their child’s wellbeing at home through the GEM principles.</li>
            <li> Ideas on how to support their own personal wellbeing.</li>
        </ul>
    </details>
</div>
';

    $authentic_connection_parents = '
<div class="inspire-description curriculum-description" style="padding-bottom: 0; border-bottom: 0">
    <h5 class="blue-text">
            Authentic Connection Webinar for Parents/Carers with Hugh or Martin
    </h5>
    <details>
        <summary>
            Click for more information
        </summary>
        <b>60 minutes | Parents and Carers</b><br/>
        Hugh and Martin are excited to deliver a new presentation, Authentic Connection. Through emotionally engaging stories and practical strategies, this session will
        <ul>
            <li> Help parents/carers understand the benefits of letting go of shame, perfection, ego and control.</li>
            <li>Combine powerful research with candid storytelling.</li>
            <li> Provide inspiration to help support what is arguably the toughest job in the world... being a parent.</li>
        </ul>
    </details>
</div>
';

    $authentic_connection_staff = '
<div class="inspire-description curriculum-description" style="padding-bottom: 0; border-bottom: 0">
    <h5 class="blue-text">
            Authentic Connection Webinar for Staff with Hugh or Martin
    </h5>
    <details>
        <summary>
            Click for more information
        </summary>
        <b>60 minutes | All Staff</b><br/>
        Hugh and Martin are excited to deliver a new presentation, Authentic Connection. Through emotionally engaging stories and practical strategies, this session will
        <ul>
            <li> Demonstrate the power of embracing vulnerability, imperfection and passion to build connection.</li>
            <li>Demonstrate the positive impact connection has on us both personally and professionally.</li>
        </ul>
        Please note that this webinar focuses on providing personal inspiration, tools and strategies around enhancing your own wellbeing.
While school examples are provided, it is not intended as a session on how to implement The Resilience Program in your school.
    </details>
</div>
';

    if ($choice['value'] === 'Hugh Staff AC Webinar$0') {
        return $open_div. $authentic_connection_staff . '<div>' . $choice_markup;
    };
    if ($choice['value'] === 'Martin Staff AC Webinar$0') {
        return $choice_markup . '</div></div>';
    };

    if ($choice['value'] === 'Connected Parenting Webinar$0') {
        return $open_div. $connected_parenting . $choice_markup.'</div>';
    };
    if ($choice['value'] === 'Building Resilience at Home Webinar$0') {
        return $open_div. $trp_in_action . $choice_markup.'</div>';
    };
    if ($choice['value'] === 'Hugh Parent Webinar$0') {
        return $open_div. $authentic_connection_parents . '<div>' . $choice_markup;
    };
    if ($choice['value'] === 'Martin Parent Webinar$0') {
        return $choice_markup . '</div></div>';
    };

    return $choice_markup;
}

// Add custom validation for school name inputs
// At least one of the dropdown or free text must be populated

add_filter('gform_validation', 'validate_school_name_input');

function validate_school_name_input($validation_result)
{
    $form = $validation_result['form'];
    $form_id = $form['id'];
    $service_type = 'a school';
    $test_page = rgpost('gform_source_page_number_' . $_POST['gform_submit']) ? rgpost('gform_target_page_number_' . $_POST['gform_submit']) : 1;

    if ($form_id == 51 and rgpost('gform_source_page_number_' . $_POST['gform_submit']) == 2) {

        ?> <script>console.log("--------(", "<?php echo rgpost('input_14') ?>")</script><?php
        // general enquiries form
        $enq_type = rgpost('input_14');
        ?> <script>console.log("<?php echo $enq_type?>")</script><?php
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
    } elseif ($form_id == 53) {
        // school enquiries page
        $dropdown_name = '22';
        $checkbox_name = 'input_23_1';
    } elseif ($form_id == 52) {
        // info session page
        $dropdown_name = '10';
        $checkbox_name = 'input_11_1';
    } elseif ($form_id == 55) {
        // prize pack
        $dropdown_name = '5';
        $checkbox_name = 'input_7_1';
    } elseif ($form_id == 51 and GFFormDisplay::get_current_page($form_id) == 2 and rgpost('input_14') == 'School') {
        // All enquiries page
        $dropdown_name = '21';
        $checkbox_name = 'input_22_1';
    } elseif ($form_id == 76 and GFFormDisplay::get_current_page($form_id) == 1) {
        // new school confirmation page
        $dropdown_name = '226';
        $checkbox_name = 'input_227_1';
    } elseif ($form_id == 80 and GFFormDisplay::get_current_page($form_id) == 1) {
        // existing school confirmation page
        $dropdown_name = '5';
        $checkbox_name = 'input_7_1';
    } elseif ($form_id == 61 and GFFormDisplay::get_current_page($form_id) == 1) {
        // leading trp page
        $dropdown_name = '10';
        $checkbox_name = 'input_11_1';
    } elseif ($form_id == 60) {
        // workplace qualifier page
        $dropdown_name = '12';
        $checkbox_name = 'input_13_1';
        $service_type = 'an organisation';
    } elseif ($form_id == 29) {
        // ey conf page
        $dropdown_name = '233';
        $checkbox_name = 'input_235_1';
        $service_type = 'a service';
    } elseif ($form_id == 67) {
        // ey info session reg
        $dropdown_name = '10';
        $checkbox_name = 'input_11_1';
        $service_type = 'a service';
    } elseif ($form_id == 75) {
        // school info session recording
        $dropdown_name = '10';
        $checkbox_name = 'input_11_1';
    } elseif ($form_id == 77) {
        // workplace webinar recording
        $dropdown_name = '20';
        $checkbox_name = 'input_21_1';
        $service_type = 'an organisation';
    } elseif ($form_id == 81) {
        // ey prize pack
        $dropdown_name = '5';
        $checkbox_name = 'input_7_1';
        $service_type = 'a service';
    } elseif ($form_id == 82) {
        // ey conference enquiry
        $dropdown_name = '22';
        $checkbox_name = 'input_23_1';
        $service_type = 'a service';
    } else {
        return $validation_result;
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

// Populate School Info Session Times
add_filter('gform_pre_render_52', 'populate_info_sessions');
add_filter('gform_pre_validation_52', 'populate_info_sessions');
add_filter('gform_pre_submission_filter_52', 'populate_info_sessions');
add_filter('gform_admin_pre_render_52', 'populate_info_sessions');
function populate_info_sessions($form)
{
    foreach ($form['fields'] as &$field) {

        if ($field->id != 5) {
            continue;
        }
        ?>
		<script>
			jQuery.ajax({
				url:"https://theresilienceproject.com.au/resilience/Potentials/getEventPlanned.php",
				method:"GET",
				dataType: "JSON",
				success: function(result){
					if(result.optionContent) {
						jQuery('#input_52_5').append(result.optionContent);
					}
				}
			});
		</script>
		<?php

    }

    return $form;
}

// Populate Leading TRP
add_filter('gform_pre_render_61', 'populate_leading_trp_sessions');
add_filter('gform_pre_validation_61', 'populate_leading_trp_sessions');
add_filter('gform_pre_submission_filter_61', 'populate_leading_trp_sessions');
add_filter('gform_admin_pre_render_61', 'populate_leading_trp_sessions');
function populate_leading_trp_sessions($form)
{
    foreach ($form['fields'] as &$field) {

        if ($field->id != 5) {
            continue;
        }
        $curl_handle = curl_init('https://theresilienceproject.com.au/resilience/Potentials/getEventPlanned.php?type=leading-trp');
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);  // don't return headers
        $response = curl_exec($curl_handle);
        $response = json_decode($response, true);
        $options = $response['optionTextValueMapping'];
        $field->choices = $options;
        $field->placeholder = '-- Select a session -- ';
    }

    return $form;
}

// Populate EY info sessions
add_filter('gform_pre_render_67', 'populate_ey_info_sessions');
add_filter('gform_pre_validation_67', 'populate_ey_info_sessions');
add_filter('gform_pre_submission_filter_67', 'populate_ey_info_sessions');
add_filter('gform_admin_pre_render_67', 'populate_ey_info_sessions');
function populate_ey_info_sessions($form)
{
    foreach ($form['fields'] as &$field) {

        if ($field->id != 5) {
            continue;
        }
        $curl_handle = curl_init('https://theresilienceproject.com.au/resilience/Potentials/getEventPlanned.php?type=early-year');
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);  // don't return headers
        $response = curl_exec($curl_handle);
        $response = json_decode($response, true);
        $options = $response['optionTextValueMapping'];
        $field->choices = $options;
        $field->placeholder = '-- Select a session -- ';
    }

    return $form;
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
add_action('wp_footer', 'add_custom_script_footer');
function add_custom_script_footer()
{ ?>
<script type="text/javascript">
	function getUrlParameter(sParam) {
		var sPageURL = window.location.search.substring(1),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;
		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam) {
				return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
			}
		}
		return false;
	}
    var pres = 0;
    var w_1 = 0;
    var w_2 = 0;
    var conn_1 = 0;
    var conn_2 = 0;
    var d = 0;
    var m = 0;
    var f = 0;
    var trp = 0;
    var b_1 = 0;
    var b_2 = 0;
    jQuery(document).ready(function() {
        jQuery('.elementor-706 p:empty').remove();
        jQuery('.elementor-706 br').remove();
        jQuery('#elementor-tab-content-1711').show();
		var org_name = getUrlParameter('org_name');
		if(org_name != false) {
			jQuery('#input_15_118').val(org_name);
		}
		var first_name = getUrlParameter('contact_first_name');
		if(first_name != false) {
			jQuery('#input_15_123').val(first_name);
		}
		var last_name = getUrlParameter('contact_last_name');
		if(last_name != false) {
			jQuery('#input_15_124').val(last_name);
		}
		var c_email = getUrlParameter('contact_email');
		if(c_email != false) {
			jQuery('#input_15_125').val(c_email);
		}
		var org_address = getUrlParameter('address');
		if(org_address != false) {
			jQuery('#input_15_142_1').val(org_address);
		}
		var org_city = getUrlParameter('city');
		if(org_city != false) {
			jQuery('#input_15_142_3').val(org_city);
		}
		var org_state = getUrlParameter('state');
		if(org_state != false) {
			jQuery('#input_15_142_4').val(org_state);
		}
		var org_postcode = getUrlParameter('zip_code');
		if(org_postcode != false) {
			jQuery('#input_15_142_5').val(org_postcode);
		}
        jQuery(document).on('keyup', '#input_15_123', function() {
            var first_name = jQuery(this).val();
            var last_name = jQuery('#input_15_124').val();
            if (last_name != '' && first_name != '') {
                jQuery('#contact_id').val(first_name + ' ' + last_name);
            }
            else if (last_name != '' && first_name == '') {
                jQuery('#contact_id').val(last_name);
            }
            else if (first_name != '' && last_name == '') {
                jQuery('#contact_id').val(first_name);
            }
            else {
                jQuery('#contact_id').val(first_name);
            }
        });
        jQuery(document).on('keyup', '#input_15_124', function() {
            var last_name = jQuery(this).val();
            var first_name = jQuery('#input_15_123').val();
            if (first_name != '' && last_name != '') {
                jQuery('#contact_id').val(first_name + ' ' + last_name);
            }
            else if (first_name != '' && last_name == '') {
                jQuery('#contact_id').val(first_name);
            }
            else if (first_name == '' && last_name != '') {
                jQuery('#contact_id').val(last_name);
            }
            else {
                jQuery('#contact_id').val(last_name);
            }
        });
        jQuery(document).on('change', 'input[name="input_99"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#field_15_23').show();
                jQuery('#field_15_22').show();
                jQuery('#cf_potentials_discoveringresilienceprogram').val(1);
            }
            else {
                jQuery('#field_15_23').hide();
                jQuery('#field_15_22').hide();
                jQuery('#field_15_96').hide();
                jQuery('#field_15_100').hide();
                jQuery('#input_15_96_1').val(0);
				jQuery('#cf_potentials_discoveringresilienceprogram').val(0);
                jQuery('.image-choices-choice').removeClass('image-choices-choice-selected');
                pres = 0;
                var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
                jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
                removeTableItem('pres');
				jQuery('#cf_2260').val(0);
				jQuery('#cf_potentials_pricediscoveringresilienceprogram').val(parseFloat(pres).toFixed(2));
				jQuery('#cf_potentials_pricediscoveringresilienceprogram_currency_value').val(parseFloat(pres).toFixed(2));
				jQuery('#cf_potentials_yourpreferredpresenter').val('');
            }
        });
        jQuery(document).on('click', '#label_15_22_0', function() {
            if (jQuery(this).parent().hasClass('image-choices-choice-selected')) {
                return false;
            }
            else {
                jQuery('.image-choices-choice').removeClass('image-choices-choice-selected');
                jQuery(this).parent().addClass('image-choices-choice-selected');
                jQuery('#field_15_96').show();
                jQuery('#field_15_100').hide();
                jQuery('#input_15_96_1').val(1);
                jQuery('#input_15_96_1').trigger('keyup');
            }
        });
        jQuery(document).on('click', '#label_15_22_1', function() {
            if (jQuery(this).parent().hasClass('image-choices-choice-selected')) {
                return false;
            }
            else {
                jQuery('.image-choices-choice').removeClass('image-choices-choice-selected');
                jQuery(this).parent().addClass('image-choices-choice-selected');
                jQuery('#field_15_96').hide();
                jQuery('#field_15_100').show();
                jQuery('#input_15_100_1').val(1);
                jQuery('#input_15_100_1').trigger('keyup');
            }
        });
        jQuery(document).on('keyup', '#input_15_96_1', function() {
            var qty = jQuery(this).val();
            var price = 5500 + (5500 * 0.1);
            var unit_price = 5500;
            pres = qty * price;
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (pres > 0) {
                addTableItem('Discovering Resilience Program - '+jQuery('.image-choices-choice-selected span.image-choices-choice-text').text(), qty, unit_price, 'pres');
            }
            else {
                removeTableItem('pres');
            }
			jQuery('#cf_2260').val(qty);
			jQuery('#cf_potentials_pricediscoveringresilienceprogram').val(parseFloat(pres).toFixed(2));
			jQuery('#cf_potentials_pricediscoveringresilienceprogram_currency_value').val(parseFloat(pres).toFixed(2));
			jQuery('#cf_potentials_yourpreferredpresenter').val(jQuery('.image-choices-choice-selected span.image-choices-choice-text').text());
        });
        jQuery(document).on('keyup', '#input_15_100_1', function() {
            var qty = jQuery(this).val();
            var price = 5500 + (5500 * 0.1);
            var unit_price = 5500;
            pres = qty * price;
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (pres > 0) {
                addTableItem('Discovering Resilience Program - '+jQuery('.image-choices-choice-selected span.image-choices-choice-text').text(), qty, unit_price, 'pres');
            }
            else {
                removeTableItem('pres');
            }
			jQuery('#cf_2260').val(qty);
			jQuery('#cf_potentials_pricediscoveringresilienceprogram').val(parseFloat(pres).toFixed(2));
			jQuery('#cf_potentials_pricediscoveringresilienceprogram_currency_value').val(parseFloat(pres).toFixed(2));
			jQuery('#cf_potentials_yourpreferredpresenter').val(jQuery('.image-choices-choice-selected span.image-choices-choice-text').text());
        });
        jQuery(document).on('change', 'input[name="input_103"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#field_15_147').show();
                jQuery('#cf_potentials_wellbeingworkshopafterourselves').val(1);
            }
            else {
                jQuery('#field_15_147').hide();
                jQuery('#field_15_97').hide();
                jQuery('#field_15_98').hide();
				jQuery('#cf_potentials_wellbeingworkshopafterourselves').val(0);
				jQuery('#cf_potentials_wellbeingworkshopformat').val('');
                jQuery('input[name="input_147"]').prop('checked', false);
                w_1 = 0;
                var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
                jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
				removeTableItem('w_1');
				jQuery('#cf_potentials_quantitywellbeingworkshop').val(0);
				jQuery('#cf_2307').val(parseFloat(w_1).toFixed(2));
				jQuery('#cf_2307_currency_value').val(parseFloat(w_1).toFixed(2));
            }
        });
        jQuery(document).on('change', 'input[name="input_147"]', function() {
            console.log('PRES', pres);
            var val = jQuery(this).val();
            if (val == 'Live webinar') {
                jQuery('#input_15_97_1').val(1);
                jQuery('#field_15_97').show();
                jQuery('#field_15_98').hide();
                jQuery('#cf_potentials_wellbeingworkshopformat').val(val);
                jQuery('#input_15_97_1').trigger('keyup');
            }
            else if (val == 'In-person*') {
                jQuery('#input_15_97_1').val(1);
                jQuery('#field_15_97').hide();
                jQuery('#field_15_98').show();
				jQuery('#cf_potentials_wellbeingworkshopformat').val(val);
                jQuery('#input_15_97_1').trigger('keyup');
            }
        });
        jQuery(document).on('keyup', '#input_15_97_1', function() {
            var qty = jQuery(this).val();
            var input_147 = jQuery('input[name="input_147"]:checked').val();
            if (input_147 == 'Live webinar') {
                w_1 = qty * (1900 + 190);
                var unit_price = 1900;
            }
            else if (input_147 == 'In-person*') {
                w_1 = qty * (2900 + 290);
                var unit_price = 2900;
            }
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (w_1 > 0) {
                addTableItem('Wellbeing Workshop: Looking after ourselves - '+input_147, qty, unit_price, 'w_1');
            }
            else {
                removeTableItem('w_1');
            }
			jQuery('#cf_potentials_quantitywellbeingworkshop').val(qty);
			jQuery('#cf_2307').val(parseFloat(w_1).toFixed(2));
			jQuery('#cf_2307_currency_value').val(parseFloat(w_1).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_114"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#field_15_148').show();
                jQuery('#cf_potentials_wellbeingworkshopaftereachother').val(1);
            }
            else {
                jQuery('#field_15_148').hide();
                jQuery('#field_15_106').hide();
                jQuery('#field_15_107').hide();
				jQuery('#cf_potentials_wellbeingworkshopaftereachother').val(0);
				jQuery('#cf_potentials_wellbeingworkshopformatafterother').val('');
				jQuery('input[name="input_148"]').prop('checked', false);
                w_2 = 0;
                var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
                jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
				removeTableItem('w_2');
				jQuery('#cf_potentials_quantitywellbeingworkshopother').val(0);
				jQuery('#cf_potentials_pricewellbeingworkshopother').val(parseFloat(w_2).toFixed(2));
				jQuery('#cf_potentials_pricewellbeingworkshopother_currency_value').val(parseFloat(w_2).toFixed(2));
            }
        });
        jQuery(document).on('change', 'input[name="input_148"]', function() {
            var val = jQuery(this).val();
            if (val == 'Live webinar') {
                jQuery('#input_15_106_1').val(1);
                jQuery('#field_15_106').show();
                jQuery('#field_15_107').hide();
                jQuery('#cf_potentials_wellbeingworkshopformatafterother').val(val);
                jQuery('#input_15_106_1').trigger('keyup');
            }
            else if (val == 'In-person*') {
                jQuery('#input_15_106_1').val(1);
                jQuery('#field_15_106').hide();
                jQuery('#field_15_107').show();
				jQuery('#cf_potentials_wellbeingworkshopformatafterother').val(val);
                jQuery('#input_15_106_1').trigger('keyup');
            }
        });
        jQuery(document).on('keyup', '#input_15_106_1', function() {
            var qty = jQuery(this).val();
            var input_147 = jQuery('input[name="input_148"]:checked').val();
            if (input_147 == 'Live webinar') {
                w_2 = qty * (1900 + 190);
                var unit_price = 1900;
            }
            else if (input_147 == 'In-person*') {
                w_2 = qty * (2900 + 290);
                var unit_price = 2900;
            }
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (w_2 > 0) {
                addTableItem('Wellbeing Workshop: Looking after each other - '+input_147, qty, unit_price, 'w_2');
            }
            else {
                removeTableItem('w_2');
            }
			jQuery('#cf_potentials_quantitywellbeingworkshopother').val(qty);
			jQuery('#cf_potentials_pricewellbeingworkshopother').val(parseFloat(w_2).toFixed(2));
			jQuery('#cf_potentials_pricewellbeingworkshopother_currency_value').val(parseFloat(w_2).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_105"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#field_15_149').show();
                jQuery('#cf_potentials_connectedparentingpresentation').val(1);
            }
            else {
                jQuery('#field_15_149').hide();
                jQuery('#field_15_109').hide();
                jQuery('#field_15_110').hide();
				jQuery('#cf_potentials_connectedparentingpresentation').val(0);
				jQuery('input[name="input_149"]').prop('checked', false);
				jQuery('#cf_2272').val('');
                conn_1 = 0;
                var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
                jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
				removeTableItem('conn_1');
				jQuery('#cf_2274').val(0);
				jQuery('#cf_potentials_priceconnectedparentingpresentation').val(parseFloat(conn_1).toFixed(2));
				jQuery('#cf_potentials_priceconnectedparentingpresentation_currency_value').val(parseFloat(conn_1).toFixed(2));
            }
        });
        jQuery(document).on('change', 'input[name="input_149"]', function() {
            var val = jQuery(this).val();
            if (val == 'Live webinar (including recording for 7 days)') {
                jQuery('#input_15_109_1').val(1);
                jQuery('#field_15_109').show();
                jQuery('#field_15_110').hide();
                jQuery('#cf_2272').val(val);
                jQuery('#input_15_109_1').trigger('keyup');
            }
            else if (val == 'In-person*') {
                jQuery('#input_15_109_1').val(1);
                jQuery('#field_15_109').hide();
                jQuery('#field_15_110').show();
				jQuery('#cf_2272').val(val);
                jQuery('#input_15_109_1').trigger('keyup');
            }
        });
        jQuery(document).on('keyup', '#input_15_109_1', function() {
            var qty = jQuery(this).val();
            var input_147 = jQuery('input[name="input_149"]:checked').val();
            if (input_147 == 'Live webinar (including recording for 7 days)') {
                conn_1 = qty * (2500 + 250);
                var unit_price = 2500;
            }
            else if (input_147 == 'In-person*') {
                conn_1 = qty * (3500 + 350);
                var unit_price = 3500;
            }
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (conn_1 > 0) {
                if (input_147 == 'Live webinar (including recording for 7 days)') {
                    addTableItem('Connected Parenting Presentation - Live webinar', qty, unit_price, 'conn_1');
                }
                else if (input_147 == 'In-person*') {
                    addTableItem('Connected Parenting Presentation - In-person', qty, unit_price, 'conn_1');
                }
            }
            else {
                removeTableItem('conn_1');
            }
			jQuery('#cf_2274').val(qty);
			jQuery('#cf_potentials_priceconnectedparentingpresentation').val(parseFloat(conn_1).toFixed(2));
			jQuery('#cf_potentials_priceconnectedparentingpresentation_currency_value').val(parseFloat(conn_1).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_112"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#input_15_113_1').val(1);
                jQuery('#field_15_113').show();
                jQuery('#cf_potentials_connectedparentingdigitalseries').val(1);
                jQuery('#input_15_113_1').trigger('keyup');
            }
            else {
                jQuery('#input_15_113_1').val(0);
                jQuery('#field_15_113').hide();
				jQuery('#cf_potentials_connectedparentingdigitalseries').val(0);
                jQuery('#input_15_113_1').trigger('keyup');
            }
        });
        jQuery(document).on('keyup', '#input_15_113_1', function() {
            var qty = jQuery(this).val();
            conn_2 = qty * (1200 + 120);
            var unit_price = 1200;
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (conn_2 > 0) {
                addTableItem('Connected Parenting Digital Series', qty, unit_price, 'conn_2');
            }
            else {
                removeTableItem('conn_2');
            }
			jQuery('#cf_2278').val(qty);
			jQuery('#cf_2316').val(parseFloat(conn_2).toFixed(2));
			jQuery('#cf_2316_currency_value').val(parseFloat(conn_2).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_29"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#input_15_5_1').val(1);
                jQuery('#field_15_5').show();
                jQuery('#cf_potentials_daywellbeingjournal').val(1);
            }
            else {
                jQuery('#input_15_5_1').val(0);
                jQuery('#field_15_5').hide();
				jQuery('#cf_potentials_daywellbeingjournal').val(0);
            }
            jQuery('#input_15_5_1').trigger('keyup');
        });
        jQuery(document).on('keyup', '#input_15_5_1', function() {
            var qty = jQuery(this).val();
            if (qty > 0 && qty <= 99) {
                d = qty * (15 + 1.5);
                var unit_price = 15.00;
                jQuery('#input_15_5').text('$ 15.00');
            }
            else if (qty > 99 && qty <= 499) {
                d = qty * (13.5 + 1.35);
                var unit_price = 13.50;
                jQuery('#input_15_5').text('$ 13.50');
            }
            else if (qty > 499 && qty <= 999) {
                d = qty * (12 + 1.2);
                var unit_price = 12.00;
                jQuery('#input_15_5').text('$ 12.00');
            }
            else if (qty > 999 && qty <= 1999) {
                d = qty * (10.5 + 1.05);
                var unit_price = 10.50;
                jQuery('#input_15_5').text('$ 10.50');
            }
            else if (qty > 1999 && qty <= 4999) {
                d = qty * (9 + 0.9);
                var unit_price = 9.00;
                jQuery('#input_15_5').text('$ 9.00');
            }
            else if (qty > 4999) {
                d = qty * (7.5 + 0.75);
                var unit_price = 7.50;
                jQuery('#input_15_5').text('$ 7.50');
            }
            else {
                d = 0;
				var unit_price = 0;
                jQuery('#input_15_5').text('$ 15.00');
            }
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (d > 0) {
                addTableItem(jQuery('#field_15_3 a').text(), qty, unit_price, 'd');
            }
            else {
                removeTableItem('d');
            }
			jQuery('#cf_potentials_quantitydayjournals').val(qty);
			jQuery('#cf_potentials_pricedaywellbeingjournal').val(parseFloat(d).toFixed(2));
			jQuery('#cf_potentials_pricedaywellbeingjournal_currency_value').val(parseFloat(d).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_35"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#input_15_36_1').val(1);
                jQuery('#field_15_36').show();
                jQuery('#cf_potentials_monthwellbeingjournal').val(1);
            }
            else {
                jQuery('#input_15_36_1').val(0);
                jQuery('#field_15_36').hide();
				jQuery('#cf_potentials_monthwellbeingjournal').val(0);
            }
            jQuery('#input_15_36_1').trigger('keyup');
        });
        jQuery(document).on('keyup', '#input_15_36_1', function() {
            var qty = jQuery(this).val();
            if (qty > 0 && qty <= 99) {
                m = qty * (27.30 + 2.73);
                var unit_price = 27.30;
                jQuery('#input_15_36').text('$ 27.30');
            }
            else if (qty > 99 && qty <= 249) {
                m = qty * (24.6 + 2.46);
                var unit_price = 24.60;
                jQuery('#input_15_36').text('$ 24.60');
            }
            else if (qty > 249 && qty <= 499) {
                m = qty * (23.2 + 2.32);
                var unit_price = 23.20;
                jQuery('#input_15_36').text('$ 23.20');
            }
            else if (qty > 499 && qty <= 999) {
                m = qty * (21.8 + 2.18);
                var unit_price = 21.80;
                jQuery('#input_15_36').text('$ 21.80');
            }
            else if (qty > 999 && qty <= 1999) {
                m = qty * (19.10 + 1.91);
                var unit_price = 19.10;
                jQuery('#input_15_36').text('$ 19.10');
            }
            else if (qty > 1999 && qty <= 4999) {
                m = qty * (16.4 + 1.64);
                var unit_price = 16.40;
                jQuery('#input_15_36').text('$ 16.40');
            }
            else if (qty > 4999) {
                m = qty * (13.7 + 1.37);
                var unit_price = 13.70;
                jQuery('#input_15_36').text('$ 13.70');
            }
            else {
                m = 0;
				var unit_price = 0;
                jQuery('#input_15_36').text('$ 27.30');
            }
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (m > 0) {
                addTableItem(jQuery('#field_15_32 a').text(), qty, unit_price, 'm');
            }
            else {
                removeTableItem('m');
            }
			jQuery('#cf_potentials_quantitymonthjournals').val(qty);
			jQuery('#cf_potentials_pricemonthwellbeingjournal').val(parseFloat(m).toFixed(2));
			jQuery('#cf_potentials_pricemonthwellbeingjournal_currency_value').val(parseFloat(m).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_43"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#input_15_44_1').val(1);
                jQuery('#field_15_44').show();
                jQuery('#cf_potentials_familywellbeingjournal').val(1);
            }
            else {
                jQuery('#input_15_44_1').val(0);
                jQuery('#field_15_44').hide();
				jQuery('#cf_potentials_familywellbeingjournal').val(0);
            }
            jQuery('#input_15_44_1').trigger('keyup');
        });
        jQuery(document).on('keyup', '#input_15_44_1', function() {
            var qty = jQuery(this).val();
            if (qty > 0 && qty <= 99) {
                f = qty * (22.70 + 2.27);
                var unit_price = 22.70;
                jQuery('#input_15_44').text('$ 22.70');
            }
            else if (qty > 99 && qty <= 249) {
                f = qty * (20.4 + 2.04);
                var unit_price = 20.40;
                jQuery('#input_15_44').text('$ 20.40');
            }
            else if (qty > 249 && qty <= 499) {
                f = qty * (19.3 + 1.93);
                var unit_price = 19.30;
                jQuery('#input_15_44').text('$ 19.30');
            }
            else if (qty > 499 && qty <= 999) {
                f = qty * (18.2 + 1.82);
                var unit_price = 18.20;
                jQuery('#input_15_44').text('$ 18.20');
            }
            else if (qty > 999 && qty <= 1999) {
                f = qty * (15.90 + 1.59);
                var unit_price = 15.90;
                jQuery('#input_15_44').text('$ 15.90');
            }
            else if (qty > 1999 && qty <= 4999) {
                f = qty * (13.6 + 1.36);
                var unit_price = 13.60;
                jQuery('#input_15_44').text('$ 13.60');
            }
            else if (qty > 4999) {
                f = qty * (11.3 + 1.13);
                var unit_price = 11.30;
                jQuery('#input_15_44').text('$ 11.30');
            }
            else {
                f = 0;
				var unit_price = 0;
                jQuery('#input_15_44').text('$ 22.70');
            }
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (f > 0) {
                addTableItem(jQuery('#field_15_40 a').text(), qty, unit_price, 'f');
            }
            else {
                removeTableItem('f');
            }
			jQuery('#cf_potentials_quantityfamilyjournals').val(qty);
			jQuery('#cf_potentials_pricefamilywellbeingjournal').val(parseFloat(f).toFixed(2));
			jQuery('#cf_potentials_pricefamilywellbeingjournal_currency_value').val(parseFloat(f).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_69"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#input_15_67_1').val(1);
                jQuery('#field_15_67').show();
                jQuery('#cf_potentials_trpapp').val(1);
            }
            else {
                jQuery('#input_15_67_1').val(0);
                jQuery('#field_15_67').hide();
				jQuery('#cf_potentials_trpapp').val(0);
            }
            jQuery('#input_15_67_1').trigger('keyup');
        });
        jQuery(document).on('keyup', '#input_15_67_1', function() {
            var qty = jQuery(this).val();
            trp = qty * (4.49 + 0.449);
            var unit_price = 4.49;
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (trp > 0) {
                addTableItem('TRP App', qty, unit_price, 'trp');
            }
            else {
                removeTableItem('trp');
            }
			jQuery('#cf_potentials_quantitytrpapp').val(qty);
			jQuery('#cf_potentials_pricetrpapp').val(parseFloat(trp).toFixed(2));
			jQuery('#cf_potentials_pricetrpapp_currency_value').val(parseFloat(trp).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_73"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#input_15_74_1').val(1);
                jQuery('#field_15_74').show();
                jQuery('#cf_potentials_booktheresilienceproject').val(1);
            }
            else {
                jQuery('#input_15_74_1').val(0);
                jQuery('#field_15_74').hide();
				jQuery('#cf_potentials_booktheresilienceproject').val(0);
            }
            jQuery('#input_15_74_1').trigger('keyup');
        });
        jQuery(document).on('keyup', '#input_15_74_1', function() {
            var qty = jQuery(this).val();
            if (qty > 0 && qty <= 99) {
                b_1 = qty * 31.80 + (qty * 31.80 * 0.1);
                var unit_price = 31.80;
                jQuery('#input_15_74').text('$ 31.80');

            }
            else if (qty >= 100 && qty <= 199) {
                b_1 = qty * 28.50 + (qty * 28.50 * 0.1);
                var unit_price = 28.50;
                jQuery('#input_15_74').text('$ 28.50');
            }
            else if (qty >= 200) {
                b_1 = qty * 25.40 + (qty * 25.40 * 0.1);
                var unit_price = 25.4;
                jQuery('#input_15_74').text('$ 25.40');
            }
            else {
                b_1 = 0;
				var unit_price = 0;
                jQuery('#input_15_74').text('$ 31.80');
            }
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (b_1 > 0) {
                addTableItem(jQuery('#field_15_70 a').text(), qty, unit_price, 'b1');
            }
            else {
                removeTableItem('b1');
            }
			jQuery('#cf_potentials_quantitybooktheresilienceproject').val(qty);
			jQuery('#cf_potentials_pricebooktheresilienceproject').val(parseFloat(b_1).toFixed(2));
			jQuery('#cf_potentials_pricebooktheresilienceproject_currency_value').val(parseFloat(b_1).toFixed(2));
        });
        jQuery(document).on('change', 'input[name="input_76"]', function() {
            var val = jQuery(this).val();
            if (val == 'Yes') {
                jQuery('#input_15_75_1').val(1);
                jQuery('#field_15_75').show();
                jQuery('#cf_potentials_bookletgo').val(1);
            }
            else {
                jQuery('#input_15_75_1').val(0);
                jQuery('#field_15_75').hide();
				jQuery('#cf_potentials_bookletgo').val(0);
            }
            jQuery('#input_15_75_1').trigger('keyup');
        });
        jQuery(document).on('keyup', '#input_15_75_1', function() {
            var qty = jQuery(this).val();
            if (qty > 0 && qty <= 99) {
                b_2 = qty * 31.80 + (qty * 31.80 * 0.1);
                var unit_price = 31.80;
                jQuery('#input_15_75').text('$ 31.80');
            }
            else if (qty >= 100 && qty <= 199) {
                b_2 = qty * 28.50 + (qty * 28.50 * 0.1);
                var unit_price = 28.50;
                jQuery('#input_15_75').text('$ 28.50');
            }
            else if (qty >= 200) {
                b_2 = qty * 25.40 + (qty * 25.40 * 0.1);
                var unit_price = 25.4;
                jQuery('#input_15_75').text('$ 25.40');
            }
            else {
                b_2 = 0;
				var unit_price = 0;
                jQuery('#input_15_75').text('$ 31.80');
            }
            var sub_Total = parseFloat(pres) + parseFloat(w_1) + parseFloat(w_2) + parseFloat(conn_1) + parseFloat(conn_2) + parseFloat(d) + parseFloat(m) + parseFloat(f) + parseFloat(trp) + parseFloat(b_1) + parseFloat(b_2);
            jQuery('#input_15_8').val(sub_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			if (b_2 > 0) {
                addTableItem(jQuery('#field_15_79 a').text(), qty, unit_price, 'b2');
            }
            else {
                removeTableItem('b2');
            }
			jQuery('#cf_potentials_quantitybookletgo').val(qty);
			jQuery('#cf_potentials_pricebookletgo').val(parseFloat(b_2).toFixed(2));
			jQuery('#cf_potentials_pricebookletgo_currency_value').val(parseFloat(b_2).toFixed(2));
        });
    });
    function addTableItem(productName, qty, unit_price, el) {
        if (jQuery('#field_15_81 table.gpecf-order-summary tbody tr#'+el).length <= 0) {
            var html = '<tr id="' + el + '"><td style="border-right: 1px solid #dfdfdf; vertical-align: top; padding: 8px 10px; width: 50%;"><div style="font-weight: bold; color: #bf461e; font-size: 13px; margin-bottom: 5px; ">' + productName + '</div></td><td style="border-right: 1px solid #dfdfdf; vertical-align: top; padding: 8px 10px; text-align: center; ">' + qty + '</td><td style="border-right: 1px solid #dfdfdf; vertical-align: top; padding: 8px 10px; text-align: right; ">$ ' + (parseFloat(unit_price).toFixed(2)).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '</td><td style="border-right: 1px solid #dfdfdf; vertical-align: top; padding: 8px 10px; text-align: right; ">$ ' + (parseFloat(unit_price * qty).toFixed(2)).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '</td></tr>';jQuery('#field_15_81 table.gpecf-order-summary tbody').append(html);
        }
        else {
            var html = '<td style="border-right: 1px solid #dfdfdf; vertical-align: top; padding: 8px 10px; width: 50%;"><div style="font-weight: bold; color: #bf461e; font-size: 13px; margin-bottom: 5px; ">' + productName + '</div></td><td style="border-right: 1px solid #dfdfdf; vertical-align: top; padding: 8px 10px; text-align: center; ">' + qty + '</td><td style="border-right: 1px solid #dfdfdf; vertical-align: top; padding: 8px 10px; text-align: right; ">$ ' + (parseFloat(unit_price).toFixed(2)).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '</td><td style="border-right: 1px solid #dfdfdf; vertical-align: top; padding: 8px 10px; text-align: right; ">$ ' + (parseFloat(unit_price * qty).toFixed(2)).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '</td>';jQuery('#field_15_81 table.gpecf-order-summary tbody tr#'+el).html(html);
        }
        calcTotal();
    }
    function removeTableItem(el) {
        jQuery('#field_15_81 table.gpecf-order-summary tbody tr#'+el).remove();
        calcTotal();
    }
    function calcTotal() {
        var s_sTotal = 0;
        var s_gst = 0;
        var s_Total = 0;
        jQuery('#field_15_81 table.gpecf-order-summary tbody tr').each(function() {
            var s_price = jQuery(this).find('td:eq(3)').text();
            s_price = s_price.replace('$', '');
            s_price = s_price.replace(',', '');
            s_sTotal += parseFloat(s_price);
        });
        s_gst = parseFloat(s_sTotal * 0.1);
        s_Total = s_sTotal + s_gst;
        jQuery('tfoot tr:eq(0) td:eq(2)').text('$ '+s_sTotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
        jQuery('tfoot tr:eq(1) td:eq(1)').text('$ '+s_gst.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
        jQuery('tfoot tr:eq(2) td:eq(1)').text('$ '+s_Total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));

		jQuery('#amount').val(s_Total.toFixed(2));
		jQuery('#amount_currency_value').val(s_Total.toFixed(2));
		jQuery('#cf_potentials_programcost').val(s_Total.toFixed(2));
		jQuery('#cf_potentials_programcost_currency_value').val(s_Total.toFixed(2));
    }
	jQuery('input[name=cf_potentials_wouldyouliketheinvoice]').on('click',function(){
		if(jQuery(this).val() == '1') {
			jQuery('div.invoice_contact').show();
		} else {
			jQuery('div.invoice_contact').hide();
		}
		console.log('value',jQuery(this).val());
	});
	jQuery(document).on('submit','#__vtigerWebForm_6',function(e){
		e.preventDefault();
		e.stopPropagation();
		var pass = true;
		jQuery('.field_required').each(function() {
			var field_val = jQuery(this).val().trim();
			if (field_val == '') {
				jQuery(this).focus();
				jQuery(this).css('border-color', '#ff0000');
				pass = false;
				return false;
			}
			else {
				jQuery(this).css('border-color', '#ddd');
			}
		});
		if (!pass) {
			return false;
		}
		else {
			var formData = new FormData(document.getElementById("__vtigerWebForm_6"));
			jQuery.ajax({
				url:"https://devl06.borugroup.com/resilience/Potentials/createDealFromWorkplaceForm.php",
				method:"POST",
				data: formData,
				dataType: "json",
				contentType:false,
				cache:false,
				processData:false,
				beforeSend: function() {
					jQuery('#loading_page').show();
				},
				success: function(result){
					jQuery('#loading_page').hide();
					console.log('result',result);
					if(result.success) {
						window.location.href = "https://forms.theresilienceproject.com.au/thank-you-new-interstate-and-vic/";
					} else {
						var content = "<div class='dialog-ovelay'>" +
							"<div class='dialog' style='width: 350px;margin-right: 270px;'><header>" +
							" <h3>"+result.message+" </h3> " +
							"</header>" +
							"<footer>" +
							"<div class='controls'>" +
							" <button class='button button-danger cancelAction'>OK</button> " +
							"</div>" +
							"</footer>" +
							"</div>" +
							"</div>";
						jQuery('body').prepend(content);
						jQuery('.cancelAction').click(function () {
							jQuery(this).parents('.dialog-ovelay').fadeOut(500, function () {
								jQuery(this).remove();
							});
						});
					}
				}
			});
		}
	});
</script>
<?php }?>
<?php
add_action('wp_footer', 'add_custom_early_script_footer');
function add_custom_early_script_footer()
{
    ?>
<script src='https://gf-address-enhanced.webaware.net.au/wp-includes/js/underscore.min.js?ver=1.13.4' id='underscore-js'></script>
<script type="text/html" id="tmpl-gf-address-enhanced-state-any"><input type="text" name="{{data.field_name}}" id="{{data.field_id}}" value="{{data.state}}" placeholder="{{data.placeholder}}"
		<# if (data.autocomplete) { #> autocomplete="{{data.autocomplete}}" <# } #>
		<# if (data.required) { #> aria-required="{{data.required}}" <# } #>
		<# if (data.describedby) { #> aria-describedby="{{data.describedby}}" <# } #>
		<# if (data.tabindex) { #> tabindex="{{data.tabindex}}" <# } #> /></script>
<script type="text/html" id="tmpl-gf-address-enhanced-state-list"><select name="{{data.field_name}}" id="{{data.field_id}}"
		<# if (data.autocomplete) { #> autocomplete="{{data.autocomplete}}" <# } #>
		<# if (data.required) { #> aria-required="{{data.required}}" <# } #>
		<# if (data.describedby) { #> aria-describedby="{{data.describedby}}" <# } #>
		<# if (data.tabindex) { #> tabindex="{{data.tabindex}}" <# } #> >

		<option value="">{{data.placeholder}}</option>
		<# _.each(data.states, function(s) { #>
		<option value="{{s[0]}}"<# if (data.state === s[0] || data.state === s[1]) { #> selected <# } #>>{{s[1]}}</option>
		<# }) #>
	</select></script>
<script>
	jQuery(document).ready(function() {
		jQuery('.elementor-552 p:empty').remove();
        jQuery('.elementor-552 br').remove();
	jQuery(document).on('keyup', '#input_10_3', function() {
		var number_child = jQuery(this).val();
		var price_child = parseFloat(number_child * 19);
		price_child = price_child.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
		jQuery('#price-children').text(price_child);
		jQuery('#number-children').text(number_child);
		var total_cost = parseFloat((number_child * 19) + 990);
		total_cost = total_cost.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
		jQuery('#total-cost span').text(total_cost);
		var amount = total_cost.replace(',', '');
		amount = parseFloat(amount);
		jQuery('#amount_currency_value').val(amount);
		jQuery('#amount_value').val(amount);
		jQuery('#cf_potentials_programcost_currency_value').val(amount);
		jQuery('#cf_potentials_programcost').val(amount);
		jQuery('#cf_potentials_numberofstudentsvalue').val(number_child);
	});
	jQuery(document).on('change', 'input[name="input_32"]', function() {
		var val = jQuery(this).val();
		if (val == 'Yes') {
			jQuery('#field_10_6').show();
			jQuery('#field_10_7').show();
		}
		else {
			jQuery('#field_10_6').hide();
			jQuery('#field_10_7').hide();
		}
	});
	jQuery(document).on('keyup', '#input_10_8_3', function() {
		var first_name = jQuery(this).val();
		var last_name = jQuery('#input_10_8_6').val();
		if (last_name != '') {
			jQuery('#contact_id').val(first_name + ' ' + last_name);
		}
		else {
			jQuery('#contact_id').val(first_name);
		}
	});
	jQuery(document).on('keyup', '#input_10_8_6', function() {
		var last_name = jQuery(this).val();
		var first_name = jQuery('#input_10_8_3').val();
		if (first_name != '') {
			jQuery('#contact_id').val(first_name + ' ' + last_name);
		}
		else {
			jQuery('#contact_id').val(last_name);
		}
	});
	var org_name = getUrlParameter('org_name');
	if(org_name != false) {
		jQuery('#input_10_1').val(org_name);
		jQuery('#potentialname').val("Deal: "+org_name);
	}
	var number_of_student = getUrlParameter('number_of_student');
	if(number_of_student != false) {
		jQuery('#input_10_3').val(number_of_student);
		jQuery('#cf_potentials_numberofstudentsvalue').val(number_of_student);
	}
	var contact_first_name = getUrlParameter('contact_first_name');
	if(contact_first_name != false) {
		jQuery('#input_10_8_3').val(contact_first_name);
	}
	var contact_last_name = getUrlParameter('contact_last_name');
	if(contact_last_name != false) {
		jQuery('#input_10_8_6').val(contact_last_name);
	}
	var contact_email = getUrlParameter('contact_email');
	if(contact_email != false) {
		jQuery('#input_10_9').val(contact_email);
	}
	var address = getUrlParameter('address');
	if(address != false) {
		jQuery('#input_10_10_1').val(address);
	}
	var city = getUrlParameter('city');
	if(city != false) {
		jQuery('#input_10_10_3').val(city);
	}
	var state = getUrlParameter('state');
	if(state != false) {
		jQuery('#input_10_10_4').val(state);
	}
	var zip_code = getUrlParameter('zip_code');
	if(zip_code != false) {
		jQuery('#input_10_10_5').val(zip_code);
	}
	function getUrlParameter(sParam) {
		var sPageURL = window.location.search.substring(1),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;
		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam) {
				return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
			}
		}
		return false;
	}
	jQuery(document).on('submit','#__vtigerWebForm_3',function(e){
		e.preventDefault();
		e.stopPropagation();
		var formData = new FormData(document.getElementById("__vtigerWebForm_3"));
		jQuery.ajax({
            url:"https://devl06.borugroup.com/resilience/Potentials/createDealEarlyYearsConfirmationForm.php",
            method:"POST",
            data: formData,
            dataType: "json",
            contentType:false,
            cache:false,
            processData:false,
			beforeSend: function() {
                jQuery('#loading_page').show();
            },
            success: function(result){
				jQuery('#loading_page').hide();
				console.log('result',result);
				if(result.success) {
					window.location.href = "https://forms.theresilienceproject.com.au/thank-you-new-interstate-and-vic/";
				} else {
					var content = "<div class='dialog-ovelay'>" +
						"<div class='dialog' style='width: 350px;margin-right: 270px;'><header>" +
						" <h3>"+result.message+" </h3> " +
						"</header>" +
						"<footer>" +
						"<div class='controls'>" +
						" <button class='button button-danger cancelAction'>OK</button> " +
						"</div>" +
						"</footer>" +
						"</div>" +
						"</div>";
					jQuery('body').prepend(content);
					jQuery('.cancelAction').click(function () {
						jQuery(this).parents('.dialog-ovelay').fadeOut(500, function () {
							jQuery(this).remove();
						});
					});
				}
            }
        });
	});
});
</script>
<?php } ?>
<?php
add_action('wp_footer', 'add_custom_existing_confirmation_script_footer');
function add_custom_existing_confirmation_script_footer()
{
    ?>
<script>
	jQuery(document).ready(function() {
		jQuery('.elementor-127 p:empty').remove();
        jQuery('.elementor-127 br').remove();
		jQuery('.div_program_addition').hide();
        jQuery('.div_teach_well_program').hide();
        jQuery('.div_teach_well_web_your').hide();
        jQuery('.div_teach_well_web_other').hide();
        jQuery('.div_teach_well_series').hide();
        jQuery('.div_trp_in_act').hide();
        jQuery('.div_connect_parenting_web').hide();
        jQuery('.div_progr').hide();
        jQuery('.teach_well_program').on('change', function() {
            var select_program = jQuery('input:radio[name=input_24]:checked').val();
            var span_add_program = jQuery('.span_add_program').text();
            var span_student_fee = jQuery('.span_student_fee').text();
            var add_more = jQuery(this).val();
            if (!jQuery(this).is(':checked')) {
                jQuery('.div_teach_well_program').hide();
                var new_span_add_program = parseInt(span_add_program) - parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
                jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            } else {
                jQuery('.div_program_addition').show();
                jQuery('.div_teach_well_program').show();
                var new_span_add_program = parseInt(span_add_program) + parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            }
        });
        jQuery('.teach_well_web_your').on('change', function() {
            var select_program = jQuery('input:radio[name=input_24]:checked').val();
            var span_add_program = jQuery('.span_add_program').text();
            var span_student_fee = jQuery('.span_student_fee').text();
            var add_more = jQuery(this).val();
            if (!jQuery(this).is(':checked')) {
                jQuery('.div_teach_well_web_your').hide();
                var new_span_add_program = parseInt(span_add_program) - parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            } else {
                jQuery('.div_program_addition').show();
                jQuery('.div_teach_well_web_your').show();
                var new_span_add_program = parseInt(span_add_program) + parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            }
        });
        jQuery('.teach_well_web_other').on('change', function() {
            var select_program = jQuery('input:radio[name=input_24]:checked').val();
            var span_add_program = jQuery('.span_add_program').text();
            var span_student_fee = jQuery('.span_student_fee').text();
            var add_more = jQuery(this).val();
            if (!jQuery(this).is(':checked')) {
                jQuery('.div_teach_well_web_other').hide();
                var new_span_add_program = parseInt(span_add_program) - parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            } else {
                jQuery('.div_program_addition').show();
                jQuery('.div_teach_well_web_other').show();
                var new_span_add_program = parseInt(span_add_program) + parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            }
        });
        jQuery('.teach_well_series').on('change', function() {
            var select_program = jQuery('input:radio[name=input_24]:checked').val();
            var span_add_program = jQuery('.span_add_program').text();
            var span_student_fee = jQuery('.span_student_fee').text();
            var add_more = jQuery(this).val();
            if (!jQuery(this).is(':checked')) {
                jQuery('.div_teach_well_series').hide();
                var new_span_add_program = parseInt(span_add_program) - parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            } else {
                jQuery('.div_program_addition').show();
                jQuery('.div_teach_well_series').show();
                var new_span_add_program = parseInt(span_add_program) + parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            }
        });
        jQuery('.trp_in_act').on('change', function() {
            var select_program = jQuery('input:radio[name=input_24]:checked').val();
            var span_add_program = jQuery('.span_add_program').text();
            var span_student_fee = jQuery('.span_student_fee').text();
            var add_more = jQuery(this).val();
            if (!jQuery(this).is(':checked')) {
                jQuery('.div_trp_in_act').hide();
                var new_span_add_program = parseInt(span_add_program) - parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            } else {
                jQuery('.div_program_addition').show();
                jQuery('.div_trp_in_act').show();
                var new_span_add_program = parseInt(span_add_program) + parseInt(add_more);
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            }
        });
        jQuery('.connect_parenting_web').on('change', function() {
            var select_program = jQuery('input:radio[name=input_24]:checked').val();
            var span_add_program = jQuery('.span_add_program').text();
            var span_student_fee = jQuery('.span_student_fee').text();
            if(select_program !== 'undefined') select_program = 0;
            if(span_add_program == '') span_add_program = 0;
            var add_more = jQuery(this).val();
            if (!jQuery(this).is(':checked')) {
                jQuery('.div_connect_parenting_web').hide();
                if(parseInt(span_add_program) >0) {
                    var new_span_add_program = parseInt(span_add_program) - parseInt(add_more);
                } else {
                    var new_span_add_program = parseInt(add_more);
                }
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            } else {
                jQuery('.div_program_addition').show();
                jQuery('.div_connect_parenting_web').show();
                if(parseInt(span_add_program)>0) {
                    var new_span_add_program = parseInt(span_add_program) + parseInt(add_more);
                } else {
                    var new_span_add_program = parseInt(add_more);
                }
                var span_total_cost = parseInt(select_program) + parseInt(new_span_add_program) + parseInt(span_student_fee);
                jQuery('.span_add_program').text(new_span_add_program);
                jQuery('.span_total_cost').text(span_total_cost);
                jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
				jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            }
        });
        jQuery('input[name=cf_potentials_numberofstudents]').on('change', function() {
            var numberofstudents = parseInt(jQuery(this).val());
            var total_fee = numberofstudents*19;
            jQuery('.student_fee_val').text(numberofstudents);
            jQuery('.span_student_fee').text(total_fee);
            jQuery('#cf_potentials_studentfees_currency_value').val(total_fee);
            jQuery('#cf_potentials_numberofstudentsvalue').val(numberofstudents);
        });
        jQuery("input[name='input_24']").click(function(){
            var select_program = 0;
            if(jQuery('input:radio[name=input_24]:checked').val()) select_program = jQuery('input:radio[name=input_24]:checked').val();
            var span_add_program = jQuery('.span_add_program').text();
            var span_student_fee = jQuery('.span_student_fee').text();
            var span_total_cost = parseInt(select_program) + parseInt(span_add_program) + parseInt(span_student_fee);
            jQuery('.span_select_program').text(select_program);
            jQuery('.span_total_cost').text(span_total_cost);
            jQuery('#cf_potentials_programcost_currency_value').val(span_total_cost);
			jQuery('#cf_potentials_studentfees_currency_value').val(span_student_fee);
            if(select_program == 3500) {
                jQuery('.div_progr').show();
                jQuery('.li_program').text('Program 1');
            } else if(select_program == 500) {
                jQuery('.div_progr').show();
                jQuery('.li_program').text('Program 2');
            } else {
                jQuery('.div_progr').show();
                jQuery('.li_program').text('Program 3');
            }
        });
        jQuery(document).on('keyup', '#input_9_54_3', function() {
            var first_name = jQuery(this).val();
            var last_name = jQuery('#input_9_54_6').val();
            if (last_name != '') {
                jQuery('#contact_id').val(first_name + ' ' + last_name);
            }
            else {
                jQuery('#contact_id').val(first_name);
            }
        });
        jQuery(document).on('keyup', '#input_9_54_6', function() {
            var last_name = jQuery(this).val();
            var first_name = jQuery('#input_9_54_3').val();
            if (first_name != '') {
                jQuery('#contact_id').val(first_name + ' ' + last_name);
            }
            else {
                jQuery('#contact_id').val(last_name);
            }
        });
		function getUrlParameter(sParam) {
			var sPageURL = window.location.search.substring(1),
				sURLVariables = sPageURL.split('&'),
				sParameterName,
				i;
			for (i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split('=');

				if (sParameterName[0] === sParam) {
					return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
				}
			}
			return false;
		}
		jQuery(document).on('submit','#__vtigerWebForm_4',function(e){
			e.preventDefault();
			e.stopPropagation();
			var formData = new FormData(document.getElementById("__vtigerWebForm_4"));
			jQuery.ajax({
				url:"https://devl06.borugroup.com/resilience/Potentials/createExistingSchoolConfirmationForm.php",
				method:"POST",
				data: formData,
				dataType: "json",
				contentType:false,
				cache:false,
				processData:false,
				beforeSend: function() {
					jQuery('#loading_page').show();
				},
				success: function(result){
					jQuery('#loading_page').hide();
					console.log('result',result);
					if(result.success) {
						window.location.href = "https://forms.theresilienceproject.com.au/thank-you-new-interstate-and-vic/";
					} else {
						var content = "<div class='dialog-ovelay'>" +
							"<div class='dialog' style='width: 350px;margin-right: 270px;'><header>" +
							" <h3>"+result.message+" </h3> " +
							"</header>" +
							"<footer>" +
							"<div class='controls'>" +
							" <button class='button button-danger cancelActionExistingConfirmation'>OK</button> " +
							"</div>" +
							"</footer>" +
							"</div>" +
							"</div>";
						jQuery('body').prepend(content);
						jQuery('.cancelActionExistingConfirmation').click(function () {
							jQuery(this).parents('.dialog-ovelay').fadeOut(500, function () {
								jQuery(this).remove();
							});
						});
					}
				}
			});
		});

		 var pathname = window.location.pathname;
            if(pathname.indexOf("confirm-event-dates") !== -1 || pathname.indexOf("confirm-single-event-dates") !== -1){
                var siteURL = "https://devl06.borugroup.com/resilience/";
                var recordId = getUrlParameter('record');
				var eventId = getUrlParameter('eventid');
				if(recordId != '' && recordId != false){
					jQuery.ajax({
						url:siteURL+"webform/54333_contact_events.php?record="+recordId+'&eventid='+eventId,
						method:"GET",
						contentType:false,
						cache:false,
						processData:false,
						beforeSend: function() {
							jQuery('#messageBar').removeClass('hide');
						},
						success: function(result){
							jQuery('#messageBar').addClass('hide');
							jQuery('#confirm_event_html').html(result);
						}
					});
					jQuery(document).on('click', '.confirm_event', function () {
						jQuery(".vanilla-preloader").css("display", "block");
						var confirmButton = jQuery(this);
						confirmButton.attr('disabled', 'disabled');
						jQuery('#messageBar').removeClass('hide');

						var eventHtml = jQuery('#event_html').html();
						var contactid = '4x'+recordId;

						jQuery.ajax({
							url: siteURL + "Events/54333_inviteeDateAccepted.php",
							method: "POST",
							data: {'contactid': contactid, 'eventHtml': eventHtml, 'eventid': eventId},
							dataType: "json",
							success: function (result) {
								jQuery('#messageBar').addClass('hide');
								confirmButton.removeAttr('disabled');
								console.log('result', result)

								if (result.success) {
									if(pathname.indexOf("confirm-single-event-dates") !== -1){
									   window.location.href = "https://forms.theresilienceproject.com.au/thank-you-single-event-dates/"
									}else{
									   window.location.href = "https://forms.theresilienceproject.com.au/thank-you-new-school-confirmation/";
									}
								} else {
									var error_message = "Error while saving record.";
									if (result.error != '') {
										error_message = result.error;
									}
									console.log('error_message', error_message)
								}
								jQuery(".vanilla-preloader").css("display", "none");
							}
						});
					});
				}
            }

});
	//task_id=53385
		jQuery(document).on('gform_post_render', function(event, form_id, current_page){
			console.log(form_id );
			// code to trigger on form or form page render
 			if (form_id == '18' || form_id == '40'  ){
				console.log(form_id );


				jQuery('input[id="choice_18_172_1"]').on('change', function() {

					if (jQuery(this).prop("checked")) {
						 jQuery('input[id="choice_18_172_2"]').prop('checked', false).trigger('change');
					}
				});
														 jQuery('input[id="choice_18_172_2"]').on('change', function() {
					if (jQuery(this).prop("checked")) {
						 jQuery('input[id="choice_18_172_1"]').prop('checked', false).trigger('change');
					}

				});


				jQuery('input[name="input_160"]').on('change', function() {
            	console.log('student change' );
					//
					var numberofstudent = parseFloat( jQuery(this).val() );
					 console.log(numberofstudent);
					if (form_id == '18'  ) {
						var totalcost =  (numberofstudent*19)+3500;
						jQuery('#totalstudentcost').text((numberofstudent*19).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					}
					if (  form_id == '40'  ) {
						var totalcost =  (numberofstudent*20)+3900;
						jQuery('#totalstudentcost').text((numberofstudent*20).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					}

					jQuery('#totalcost').text(totalcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));

				});

				setTimeout(function() {
      jQuery('input[name="input_5"]').trigger('change');
    }, 2000);

				}// end form
			if (form_id == '21' || form_id == '27' ){ // form 21 || form 27
				console.log(form_id );

				function get_extendcost(){
					var extendcost = 0;

					if (jQuery('input[name="input_212.1"]').prop("checked")) {
						extendcost = extendcost+ 3500;
						}
					if (jQuery('input[name="input_212.2"]').prop("checked")) {
						if (form_id == '27'){
							extendcost = extendcost+ 500;
						}
						else extendcost = extendcost+ 1900;
						}
					if (jQuery('input[name="input_212.3"]').prop("checked")) {
						extendcost = extendcost+ 500;
						}
					if (jQuery('input[name="input_212.4"]').prop("checked")) {
						if (form_id == '27'){
							extendcost = extendcost+ 500;
						}
						else extendcost = extendcost+ 1900;
						}
					if (jQuery('input[name="input_212.5"]').prop("checked")) {
						extendcost = extendcost+ 500;
						}
					if (jQuery('input[name="input_212.6"]').prop("checked")) {
						if (form_id == '27'){
							extendcost = extendcost+ 500;
						}
						else extendcost = extendcost+ 1900;
						}
					if (jQuery('input[name="input_212.7"]').prop("checked")) {
						extendcost = extendcost+ 500;
						}
					if (jQuery('input[name="input_212.8"]').prop("checked")) {
						extendcost = extendcost+ 500;
						}
					if (jQuery('input[name="input_212.9"]').prop("checked")) {
						extendcost = extendcost+ 500;
						}
						return extendcost;
				}

				function get_engage(){
					var radioValue = jQuery('input[name="input_237"]:checked').val();
		            if(radioValue == 'Planners'){
		                return 13;
		            }
					return 19;
				}
				function get_inspire(){
					var radioValue = jQuery('input[name="input_238"]:checked').val();
		            if(radioValue == 'Yes'){
		                return 3500;
		            }
					return 0;
				}
				function showTotal(){
					var numberofstudent = parseFloat( jQuery('input[name="input_160"]').val() );
					 console.log(numberofstudent);

					var perstudent = get_engage();
					var totalstudentcost = (numberofstudent*perstudent);
					var extendcost = get_extendcost();
					var inspirecost = get_inspire();

					console.log(perstudent );
					console.log(totalstudentcost );
					console.log(extendcost );
					console.log(inspirecost );

					var totalcost = totalstudentcost +inspirecost + extendcost ;
					jQuery('#totalstudentcost').text(totalstudentcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					jQuery('#extendcost').text(extendcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					jQuery('#inspirecost').text(inspirecost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					jQuery('#totalcost').text(totalcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
				}

				jQuery('input[type="checkbox"]').on('change', function() {

					showTotal();

				});

				jQuery('input[name="input_5"]').on('change', function() {
            		console.log('orgname change. but not auto fill student count' );					//
					/*var org_name = jQuery(this).val();
					 console.log(org_name);
					jQuery.ajax({
					url:"https://devl06.borugroup.com/resilience/Potentials/checkNumberOfStudents.php",
					method:"POST",
					data: { org_name:org_name },
					dataType: "json",
					success: function(result){
						 console.log(result);
						if(result.success && result.numberofstudent) {
							  console.log('update student' );
							jQuery('input[name="input_160"]').val(result.numberofstudent);
							setTimeout(function() {
							  jQuery('input[name="input_160"]').trigger('change');
							}, 500);
						}
					}
					});*/

				});

				jQuery('input[name="input_160"]').on('change', function() {
            		console.log('student change' );
					showTotal();

				});
				jQuery('input[name="input_237"],input[name="input_238"]').on('change', function() {
            		console.log('radio change' );
					showTotal();

				});
				if (form_id == '21'){
					jQuery('input[name="input_238"]').on('change', function() {
						console.log('input_238 change' );
						var radioValue = jQuery('input[name="input_238"]:checked').val();
						if(radioValue == 'Yes'){
							jQuery('.gchoice_21_212_9').addClass('hide');
							jQuery('input[name="input_212.9"]').prop('checked', false);
						}
						else {
							jQuery('.gchoice_21_212_9').removeClass('hide');
							jQuery('input[name="input_212.9"]').prop('checked', false);
						}

					});
				} else if(form_id == '27' ){
					jQuery('input[name="input_238"]').on('change', function() {
						console.log('input_238 change' );
						var radioValue = jQuery('input[name="input_238"]:checked').val();
						if(radioValue == 'Yes'){
							jQuery('.gchoice_21_212_9').addClass('hide');
							jQuery('.gchoice_27_212_6').addClass('hide');
							jQuery('input[name="input_212.9"]').prop('checked', false);
						}
						else {
							jQuery('.gchoice_21_212_9').removeClass('hide');
							jQuery('.gchoice_27_212_6').removeClass('hide');
							jQuery('input[name="input_212.9"]').prop('checked', false);
						}

					});
				}
				setTimeout(function() {
					var gpps_page_progression_21 = jQuery('input[name="gpps_page_progression_21"]').val();
					if(gpps_page_progression_21 == 1){
						console.log('after validation' );
						showTotal();
					}
					else {
						jQuery('input[name="input_5"]').trigger('change');
					}

			    }, 2000);

			}// end form

			if (form_id == '34'){ // form 34
				var totalCostF = 0;
				jQuery('#gform_submit_button_34').val('Submit Request');
				jQuery(document).on('click','#choice_34_294_1,#choice_34_296_1,#choice_34_299_1,#choice_34_302_1,#choice_34_304_1,#choice_34_306_1,#choice_34_236_1', function() {
					var el = this;
					var valueF = 0;
					var qty_name = '';
					var isChecked = jQuery(el).is(':checked');
					var totalCostVal34 = jQuery('#input_34_180').val();
					if(jQuery(el).val() == 'wellbeing_ourselves_3500') {
						valueF = 3500;
						qty_name = '#input_34_335_1';
					} else if(jQuery(el).val() == 'wellbeing_ourselves_2500') {
						valueF = 2500;
						qty_name = '#input_34_336_1';
					} else if(jQuery(el).val() == 'wellbeing_other_3500') {
						valueF = 3500;
						qty_name = '#input_34_337_1';
					} else if(jQuery(el).val() == 'wellbeing_other_2500') {
						valueF = 2500;
						qty_name = '#input_34_338_1';
					} else if(jQuery(el).val() == 'connect_3500') {
						valueF = 3500;
						qty_name = '#input_34_339_1';
					} else if(jQuery(el).val() == 'connect_2500') {
						valueF = 2500;
						qty_name = '#input_34_340_1';
					} else if(jQuery(el).val() == 'connect_digital') {
						valueF = 1200;
						qty_name = '#input_34_341_1';
					}
					if(isChecked) {
						jQuery(document).on('change',qty_name, function() {
							if(jQuery(el).is(':checked')) {
								var old_qty = jQuery(this).attr('oldqty');
								var qty = jQuery(this).val();
								if(qty>0) {
									jQuery(this).attr('oldqty',qty);
									totalCostF += parseFloat(valueF)*parseInt(qty);
									if(old_qty) {
										totalCostF -= parseFloat(valueF)*parseInt(old_qty);
									}
									jQuery('#totalCostValue34').text('$ '+totalCostF.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
									jQuery('#input_34_180').val(totalCostF);
								} else {
									if(old_qty) {
										totalCostF -= parseFloat(valueF)*parseInt(old_qty);
										if(parseFloat(totalCostVal34)>0) totalCostF = totalCostF + parseFloat(totalCostVal34);
										jQuery('#totalCostValue34').text('$ '+totalCostF.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
										jQuery('#input_34_180').val(totalCostF);
									}
									jQuery(this).attr('oldqty','');
								}
							}
						});
					} else {
						var qtyTmp = jQuery(qty_name).val();
						if(parseFloat(totalCostF)>0 && parseInt(qtyTmp)>0) totalCostF = parseFloat(totalCostF) -  (parseFloat(valueF)*parseInt(qtyTmp));
						jQuery('#totalCostValue34').text('$ '+totalCostF.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
						jQuery('#input_34_180').val(totalCostF);
					}
				});
				var gpps_page_progression_34 = jQuery('input[name="gpps_page_progression_34"]').val();
				var totalCostVal = jQuery('#input_34_180').val();
				if(gpps_page_progression_34 == 1 && totalCostVal){
					totalCostVal = parseFloat(totalCostVal);
					jQuery('#totalCostValue34').text('$ '+totalCostVal.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
				}
			} // end form 34

			if (form_id == '37'){ // form 37
				var totalCost = 0;
				jQuery('#choice_37_352_1,#choice_37_361_1,#choice_37_367_1,#choice_37_373_1,#choice_37_379_1,#choice_37_385_1,#choice_37_391_1').on('click', function() {
					var valueC = 0;
					var number_of_participants = jQuery('#input_37_358').val();
					var totalCost = parseFloat(jQuery('#input_37_180').val());
					if(!totalCost) totalCost = 0;
					if(jQuery(this).val() == 'Package 1') {
						if(parseInt(number_of_participants)>0) {
							valueC = 5500+(12*parseInt(number_of_participants));
						} else {
							valueC = 5500+12;
						}
					} else if(jQuery(this).val() == 'Package 2') {
						valueC = 5500;
					} else if(jQuery(this).val() == 'Digital Discovering Resilience Presentation' || jQuery(this).val() == 'Digital Authentic Connection Presentation') {
						valueC = 2900;
					} else if(jQuery(this).val() == 'Digital Wellbeing Series' || jQuery(this).val() == 'Digital Wellbeing Series') {
						valueC = 3000;
					} else if(jQuery(this).val() == 'Authen Package 1') {
						valueC = 5500;
					}
					if (jQuery(this).is(':checked')) {
						totalCost += parseFloat(valueC);
					} else {
						if(totalCost>0) totalCost = parseFloat(totalCost) -  parseFloat(valueC);
					}
					jQuery('#totalCostValue').text('$ '+totalCost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					jQuery('#input_37_180').val(totalCost);
				});
				jQuery('#input_37_358').on('focusin', function(){
					jQuery(this).data('val', jQuery(this).val());
				});
				jQuery('#input_37_358').on('change', function() {
					if(jQuery(this).data('val')) {
						var prev_number_of_participants = jQuery(this).data('val');
					} else {
						var prev_number_of_participants = 1;
					}
					var number_of_participants = jQuery(this).val();
					var totalCost = jQuery('#input_37_180').val();
					if(parseInt(number_of_participants)>0) {
						totalCost = totalCost -(12*parseInt(prev_number_of_participants)) + (12*parseInt(number_of_participants));
						jQuery('#totalCostValue').text('$ '+totalCost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
						jQuery('#input_37_180').val(totalCost);
					}

				});
				var gpps_page_progression_37 = jQuery('input[name="gpps_page_progression_37"]').val();
				var totalCostVal37 = jQuery('#input_37_180').val();
				if(gpps_page_progression_37 == 1 && totalCostVal37){
					totalCostVal37 = parseFloat(totalCostVal37);
					jQuery('#totalCostValue').text('$ '+totalCostVal37.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
				}
			} // end form 37
if (form_id == '44'  ){ // form 44
				console.log(form_id );
	
	gform.addFilter( 'gform_datepicker_options_pre_init', function( optionsObj, formId, fieldId ) {
    	// load default 
		if (jQuery('input[name="input_1.1"]').prop("checked")) {
			jQuery('.teacher').removeClass('hide');
		}
		var tot=0;
    var value = null;
    for(var i=10;i<=21;i++){    
    	value = jQuery('input[name="input_'+i+'"]').val();    
        if(parseFloat(value)>0){
        	jQuery('input[name="qty_input_'+i+'"]').val(value);
        	tot += parseFloat(value);
        }            
    }
    value = jQuery('input[name="input_63"]').val();    
        if(parseFloat(value)>0){
        	jQuery('input[name="qty_input_63"]').val(value);
        	tot += parseFloat(value);
        }    
    document.getElementById('qty_total').innerHTML = tot;
    
    tot=0;
    for(var i=30;i<=41;i++){    
    	value = jQuery('input[name="input_'+i+'"]').val();    
        if(parseFloat(value)>0){
        	jQuery('input[name="teacher_qty_input_'+i+'"]').val(value);
        	tot += parseFloat(value);
        }
            
    }
    value = jQuery('input[name="input_64"]').val();    
        if(parseFloat(value)>0){
        	jQuery('input[name="teacher_qty_input_64"]').val(value);
        	tot += parseFloat(value);
        }    
    document.getElementById('teacher_qty_total').innerHTML = tot;
		
		
		if ( formId == 44 && fieldId == 53 ) {
		console.log('init date picker' );
        optionsObj.yearRange= '+1:+20';
        optionsObj.defaultDate = new Date(2024, 0, 1); 
    }
		if ( formId == 44 && fieldId == 54 ) { 
        optionsObj.defaultDate = new Date(2023, 11, 1); 
    }
    return optionsObj;
});
	jQuery('input[id="gform_next_button_44_60"]').val('Preview Order');
	jQuery('input[id="gform_previous_button_44"]').val('Make Changes');
	jQuery('input[id="gform_submit_button_44"]').val('Submit Order');
	
	var org_data = null; 
	var org_name = getUrlParameter('org_name');
	var id = getUrlParameter('id');
	var org_id = getUrlParameter('org_id');
		if( typeof org_name !== 'undefined' && org_name !==false  ) {
			var org_data = { org_name:org_name };
		}
	else if (typeof org_id !== 'undefined' && org_id !==false  ){
			 var org_data = { org_id:org_id };
			 }
	else if (typeof id !== 'undefined'  && id !==false ){
			 var org_data = { id:id };
			 }
	// quote info
	 var quote_data = null;
	 var quote_no = getUrlParameter('quote_no');
	var quote_id = getUrlParameter('quote_id'); 
		if( typeof quote_no !== 'undefined' && quote_no !== false ) {
			  quote_data = { quote_no:quote_no };
		}
	else if (typeof quote_id !== 'undefined' && quote_id !== false ){
			   quote_data = { quote_id:quote_id };
			 }
	
	console.log(quote_data );
// 	if (quote_data != null){
// 		jQuery.ajax({url:"https://theresilienceproject.com.au/resilience/Invoices/getQuoteRecordData.php",
// 					method:"POST",
// 					data: quote_data,
// 					dataType: "json",
// 					success: function(result){
// 						 console.log(result);
// 						if(result.success && result.record) {
// 							 jQuery('input[name="input_59"]').val(result.record['id'] ); 
// 							  console.log('prefill address' );
// 							var account_id = result.record['account_id'];
// 							org_data = { id:account_id };
// 							if (org_data != null){
// 		jQuery.ajax({url:"https://theresilienceproject.com.au/resilience/Invoices/getOrgRecordData.php",
// 					method:"POST",
// 					data: org_data,
// 					dataType: "json",
// 					success: function(result){
// 						 console.log(result);
// 						if(result.success && result.record) { 
// 							  console.log('show org name' ); 							
// 							//jQuery('#orgname').removeClass('hide').find('h2').html('Resource order for ' +result.record['accountname'] );
// 						}
// 					}
// 					});
// 	}
// 							/*
// 							 jQuery('input[name="input_42.1"]').val( result.record['bill_street'] );
//  jQuery('input[name="input_42.3"]').val(result.record['bill_city'] ); 
//  jQuery('[name="input_42.4"]').val(result.record['bill_state'] ).trigger('change'); 
//  jQuery('input[name="input_42.5"]').val(result.record['bill_code'] ); 
// 							if (result.record['bill_country'].length > 0){
// 								 jQuery('[name="input_42.6"]').val(result.record['bill_country'] ).trigger('change'); 
// 							}
//  */
							
// 							setTimeout(function() {
							  
// 							}, 500);
// 						}
// 					}
// 					});
// 	}
	if (org_data != null){
		jQuery.ajax({url:"https://trpstaging.dev/resilience/Invoices/getOrgRecordData.php",
					method:"POST",
					data: org_data,
					dataType: "json",
					success: function(result){
						 console.log(result);
						if(result.success && result.record) { 
							  console.log('show org name' ); 							
							//jQuery('#orgname').removeClass('hide').find('h2').html('Resource order for ' +result.record['accountname'] );
						}
					}
					});
	}
	
	
	// sum the total 
	   jQuery('input[type="number"]').change(function() {
 var name = jQuery(this).attr('name');
 console.log(name);
 if (name.indexOf('teacher') >=0 ){
 	name = name.replace('teacher_qty_',''); 
	 var arr = document.getElementsByClassName('teacher');
    var tot=0;
    for(var i=0;i<arr.length;i++){
        if(parseFloat(arr[i].value))
            tot += parseFloat(arr[i].value);
    }
    document.getElementById('teacher_qty_total').innerHTML = tot;
	 jQuery('input[name="input_66"]').val(tot);
 }
 else {
	 name = name.replace('qty_','');   
	  var arr = document.getElementsByClassName('student');
    var tot=0;
    for(var i=0;i<arr.length;i++){
        if(parseFloat(arr[i].value))
            tot += parseFloat(arr[i].value);
    }
    document.getElementById('qty_total').innerHTML = tot;
	 jQuery('input[name="input_65"]').val(tot);
 } 
    jQuery('input[name="'+name+'"]').val(jQuery(this).val());
});
	
		jQuery('input[name="input_1.1"]').on('click', function() {
		 console.log('input_1');
		 if (jQuery('input[name="input_1.1"]').prop("checked")) {
			jQuery('.teacher').removeClass('hide');
		}
		else {
		jQuery('.teacher').addClass('hide');
		}

		});
	
	 
	jQuery('input[id="gform_next_button_44_60"]').removeAttr('onclick').removeAttr('onkeypress')  ; 
	jQuery('input[id="gform_next_button_44_60"]').on('click', function(e) {
		console.log('submit form' ); 
		//jQuery(this).attr("disabled", true); 
	e.preventDefault();
		e.stopPropagation();
		
		jQuery.ajax({url:"https://trpstaging.dev/resilience/Invoices/getProductData.php",
					method:"POST",
					data: {product:true},
					dataType: "json",
					success: function(result){
						 console.log(result);
						if(result.success && result.data) {
						var weight_db = result.weight_db;
						var location_db = result.location_db;
						var states = result.states; 
						var total_weight = 0;
						var shipping_cost = 0;
							jQuery.each( result.data, function( key, record ) {
							  console.log( key + ": " + record.cf_products_weight );
							  qty = jQuery('input[name="qty_input_'+key+'"]').val() || jQuery('input[name="teacher_qty_input_'+key+'"]').val(); 
							  if (qty > 0) {
								total_weight = total_weight + ( qty * parseFloat(record.cf_products_weight) ); 
							    }
							  
							  
							});
						console.log('total_weight=', total_weight );
						
						// calculate the cost 
						// get shipping location 
						var location = ''; 
						if (jQuery('input[name="input_50.1"]').prop("checked")) {
							location = jQuery('[name="input_42.4"]').val();  // billing state 
						    }
						    else {
						    	location = jQuery('[name="input_43.4"]').val(); // shipping 
						    }
						console.log('location=', location );	
						    if (location.length>0){
							  jQuery.each( states, function( code, name ) {
							  //console.log( code + ": " + name );
							  if ( location.toLowerCase() == code.toLowerCase() || location.toLowerCase() == name.toLowerCase()){
							  	// matching location  
							  	var location_code = code; 
							  	
							  	var location_index = null; 
							  	location_db.forEach(function (item, index) {
							  		if (item.indexOf(location_code) >=0  ){
							  		location_index = index;
							  		}
								    
								    
								})
								console.log('location_index=',location_index);
								if (location_index >= 0){
									 
									jQuery.each( weight_db, function( key, weight_data ) {
									  console.log( key , weight_data );
									  
									  if (weight_data.condition =='<' && total_weight < weight_data.value){
									  	shipping_cost = weight_data.data[location_index]; 
									  	return false;	
									  }
									  else if (weight_data.condition =='>' && total_weight > weight_data.value) {
									  	shipping_cost = weight_data.data[location_index];  
									  	return false;
									  } 
									});
									
								}
							  	
							  }
							  
							});
							}
							console.log('shipping_cost=', shipping_cost);
							if (shipping_cost > 0){
								jQuery('input[name="input_62"]').val(shipping_cost);
							}
						
							 
							  
							 
						}
						setTimeout(function() {
							  jQuery('#gform_44').trigger('submit',[true]);
							}, 100);
						
					}
					});
		
	}); 
	
	
	} // end form 44
			if (form_id == '42' || form_id == '43'){ // form 42 and form 43
				console.log(form_id );

				function get_extendcost(){
					console.log('calc extend');
					var extendcost = 0;
					if(form_id == '42') {
						if (jQuery('input[name="input_273.1"]').prop("checked")) {
							extendcost = extendcost+ 3500;
						}
						if (jQuery('input[name="input_274.1"]').prop("checked")) {
							extendcost = extendcost+ 1900;
						}
						if (jQuery('input[name="input_275.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_276.1"]').prop("checked")) {
							extendcost = extendcost+ 1900;
						}
						if (jQuery('input[name="input_277.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_278.1"]').prop("checked")) {
							extendcost = extendcost+ 1900;
						}
						if (jQuery('input[name="input_279.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_280.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_281.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_282.1"]').prop("checked")) {
							extendcost = extendcost+ 1900;
						}
						if (jQuery('input[name="input_283.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_284.1"]').prop("checked")) {
							extendcost = extendcost+ 1900;
						}
						if (jQuery('input[name="input_285.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_286.1"]').prop("checked")) {
							extendcost = extendcost+ 1900;
						}
						if (jQuery('input[name="input_287.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
					} else {
						if (jQuery('input[name="input_274.1"]').prop("checked")) {
							extendcost = extendcost+ 3500;
						}
						if (jQuery('input[name="input_275.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_277.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_279.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_280.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_281.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_285.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_289.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
						if (jQuery('input[name="input_291.1"]').prop("checked")) {
							extendcost = extendcost+ 500;
						}
					}
					console.log('extendcost',extendcost);
					return extendcost;
				}

				function get_inspire(){
					var radioValue = jQuery('input[name="input_238"]:checked').val();
					if(radioValue == 'Yes'){
						console.log('presenter',jQuery('input[name="input_25"]:checked').val())
		                return 3500;
		            }
					return 0;
					/*
					var totalInspire = jQuery('#inspirecost').text();
					if(!totalInspire) totalInspire = 0;
		            if(radioValue == 'Yes'){
						if(totalInspire <=0 && (jQuery('input[name="input_272"]').val() == 'Teacher Wellbeing Webinar 1 - Looking after ourselves (online)'
						|| jQuery('input[name="input_272"]').val() == 'Teacher Wellbeing Webinar 2 - Looking after each other (online)'
						|| jQuery('input[name="input_272"]').val() == 'Teacher Wellbeing Webinar 3 - Sharing success (online)'
						|| jQuery('input[name="input_272"]').val() == 'Connected Parenting Webinar with Lael Stone (online)'
						|| jQuery('input[name="input_272"]').val() == 'TRP In Action Webinar for Parents/Carers (online)'
						|| jQuery('input[name="input_272"]').val() == 'Authentic Connection Staff Webinar with Hugh or Martin (online)'
						|| jQuery('input[name="input_272"]').val() == 'Authentic Connection Parent/Carer Webinar with Hugh or Martin (online)'))
						|| jQuery('input[name="input_272"]').val() == 'Digital Wellbeing for Families Webinar (online)')) {
							totalInspire += 500;
						} else {
							totalInspire = 500;
						}
		            }
					return parseFloat(totalInspire);
					*/
				}
				function showTotal(){
					var numberofstudent = parseInt( jQuery('input[name="input_160"]').val() );
					var totalstudentcost = parseFloat(jQuery('#totalstudentcost').text().replace(/,/g, ""));
					if(totalstudentcost <=0) {
						if(jQuery('#input_42_208').val() == 'Secondary' || jQuery('input[name="input_237"]:checked').val() == 'K-12') {
							totalstudentcost = parseFloat(numberofstudent*20);
						} else if(jQuery('#input_42_208').val() == 'Secondary') {
							if(jQuery('input[name="input_237"]').val() == 'Planners') {
								totalstudentcost = parseFloat(numberofstudent*14);
							} else if(jQuery('input[name="input_237"]').val() == 'Journals') {
								totalstudentcost = parseFloat(numberofstudent*20);
							}
						}
					}
					var extendcost = get_extendcost();
					var inspirecost = get_inspire();
					if(!totalstudentcost) totalstudentcost = 0;
					if(!extendcost) extendcost = 0;
					if(!inspirecost) inspirecost = 0;

					console.log('totalstudentcost',totalstudentcost);
					console.log('extendcost',extendcost);
					console.log('inspirecost',inspirecost);

					var totalcost = totalstudentcost +inspirecost + extendcost ;
					jQuery('#totalstudentcost').text(totalstudentcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					jQuery('#extendcost').text(extendcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					jQuery('#inspirecost').text(inspirecost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					jQuery('#totalcost').text(totalcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
				}

				jQuery('input[type="checkbox"],#input_42_160').on('change', function() {
					//change number of student
					showTotal();

				});

				jQuery('input[name="input_5"]').on('change', function() {
            		console.log('orgname change. but not auto fill student count' );					//
					/*var org_name = jQuery(this).val();
					 console.log(org_name);
					jQuery.ajax({
					url:"https://devl06.borugroup.com/resilience/Potentials/checkNumberOfStudents.php",
					method:"POST",
					data: { org_name:org_name },
					dataType: "json",
					success: function(result){
						 console.log(result);
						if(result.success && result.numberofstudent) {
							  console.log('update student' );
							jQuery('input[name="input_160"]').val(result.numberofstudent);
							setTimeout(function() {
							  jQuery('input[name="input_160"]').trigger('change');
							}, 500);
						}
					}
					});*/

				});

				jQuery('input[name="input_160"]').on('change', function() {
            		console.log('student change' );
					showTotal();

				});
				jQuery('input[name="input_272"]').on('change', function() {
            		console.log('radio change' );
					showTotal();

				});
				jQuery('input[name="input_25"]').on('change', function() {
            		console.log('radio change' );
					showTotal();

				});
				jQuery('select[name="input_208"]').on('change', function() {
					var numberofstudent = parseInt( jQuery('input[name="input_160"]').val());
					var totalstudentcost = '';
					if(jQuery(this).val() == "Primary" || jQuery(this).val() == "K-12") {
						totalstudentcost = parseFloat(numberofstudent*20);
						var extendcost = get_extendcost();
						var inspirecost = get_inspire();
						if(!extendcost) extendcost = 0;
						if(!inspirecost) inspirecost = 0;

						console.log('totalstudentcost',totalstudentcost);
						console.log('extendcost',extendcost);
						console.log('inspirecost',inspirecost);

						var totalcost = totalstudentcost +inspirecost + extendcost ;
						jQuery('#totalstudentcost').text(totalstudentcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
						jQuery('#extendcost').text(extendcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
						jQuery('#inspirecost').text(inspirecost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
						jQuery('#totalcost').text(totalcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
					} else {
						jQuery('input[name="input_237"]').on('click', function() {
							console.log('1111',jQuery(this).val() );
							if(jQuery(this).val() == "Planners") {
								totalstudentcost = parseFloat(numberofstudent*14);
							} else {
								totalstudentcost = parseFloat(numberofstudent*20);
							}
							var extendcost = get_extendcost();
							var inspirecost = get_inspire();
							if(!extendcost) extendcost = 0;
							if(!inspirecost) inspirecost = 0;

							console.log('totalstudentcost',totalstudentcost);
							console.log('extendcost',extendcost);
							console.log('inspirecost',inspirecost);

							var totalcost = totalstudentcost +inspirecost + extendcost ;
							jQuery('#totalstudentcost').text(totalstudentcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
							jQuery('#extendcost').text(extendcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
							jQuery('#inspirecost').text(inspirecost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
							jQuery('#totalcost').text(totalcost.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));

						});
					}

				});


			}// end form 42

    	});
	
jQuery(document).bind('gform_post_render', function (event, formId, current_page) {
        jQuery("div.wrap_progress_bar").css({'visibility':'visible', 'display':''});
});
	

var gf_address_enhanced_smart_states = {"states":{"Albania":[["01","Berat"],["09","Dib\u00ebr"],["02","Durr\u00ebs"],["03","Elbasan"],["04","Fier"],["05","Gjirokast\u00ebr"],["06","Kor\u00e7\u00eb"],["07","Kuk\u00ebs"],["08","Lezh\u00eb"],["10","Shkod\u00ebr"],["11","Tirana"],["12","Vlor\u00eb"]],"Algeria":[["01","Adrar"],["44","A\u00efn Defla"],["46","A\u00efn T\u00e9mouchent"],["16","Algiers"],["23","Annaba"],["05","Batna"],["08","B\u00e9char"],["06","B\u00e9ja\u00efa"],["07","Biskra"],["09","Blida"],["34","Bordj Bou Arr\u00e9ridj"],["10","Bouira"],["35","Boumerd\u00e8s"],["02","Chlef"],["25","Constantine"],["17","Djelfa"],["32","El Bayadh"],["36","El Tarf"],["39","El Oued"],["47","Gharda\u00efa"],["24","Guelma"],["33","Illizi"],["18","Jijel"],["40","Khenchela"],["03","Laghouat"],["29","Mascara"],["26","M\u00e9d\u00e9a"],["43","Mila"],["27","Mostaganem"],["28","M'Sila"],["45","Naama"],["31","Oran"],["30","Ouargla"],["04","Oum El Bouaghi"],["48","Relizane"],["20","Sa\u00efda"],["19","S\u00e9tif"],["22","Sidi Bel Abb\u00e8s"],["21","Skikda"],["41","Souk Ahras"],["11","Tamanghasset"],["12","T\u00e9bessa"],["14","Tiaret"],["37","Tindouf"],["42","Tipasa"],["38","Tissemsilt"],["15","Tizi Ouzou"],["13","Tlemcen"]],"Angola":[["BGO","Bengo"],["BGU","Benguela"],["BIE","Bi\u00e9"],["CAB","Cabinda"],["CNN","Cunene"],["HUA","Huambo"],["HUI","Hu\u00edla"],["CCU","Kuando Kubango"],["CNO","Kwanza Norte"],["CUS","Kwanza Sul"],["LUA","Luanda"],["LNO","Lunda Norte"],["LSU","Lunda Sul"],["MAL","Malanje"],["MOX","Moxico"],["NAM","Namibe"],["UIG","U\u00edge"],["ZAI","Zaire"]],"Argentina":[["C","Ciudad Aut\u00f3noma de Buenos Aires",1],["B","Buenos Aires"],["K","Catamarca"],["H","Chaco"],["U","Chubut"],["X","C\u00f3rdoba"],["W","Corrientes"],["E","Entre R\u00edos"],["P","Formosa"],["Y","Jujuy"],["L","La Pampa"],["F","La Rioja"],["M","Mendoza"],["N","Misiones"],["Q","Neuqu\u00e9n"],["R","R\u00edo Negro"],["A","Salta"],["J","San Juan"],["D","San Luis"],["Z","Santa Cruz"],["S","Santa Fe"],["G","Santiago del Estero"],["V","Tierra del Fuego"],["T","Tucum\u00e1n"]],"Armenia":[["ER","Yerevan",1],["AG","Aragatsotn"],["AR","Ararat"],["AV","Armavir"],["GR","Gegharkunik"],["KT","Kotayk"],["LO","Lori"],["SH","Shirak"],["SU","Syunik"],["TV","Tavush"],["VD","Vayots Dzor"]],"Australia":[["ACT","Australian Capital Territory"],["NSW","New South Wales"],["NT","Northern Territory"],["QLD","Queensland"],["VIC","Victoria"],["SA","South Australia"],["WA","Western Australia"],["TAS","Tasmania"]],"Austria":[["1","Burgenland"],["2","Carinthia"],["3","Lower Austria"],["4","Upper Austria"],["5","Salzburg"],["6","Styria"],["7","Tyrol"],["8","Vorarlberg"],["9","Vienna"]],"Azerbaijan":[["BA","Baku",2],["GA","Ganja",1],["LA","Lankaran municipality",1],["MI","Mingachevir",1],["NA","Naftalan",1],["NV","Nakhchivan",1],["SA","Shaki municipality",1],["SR","Shirvan",1],["SM","Sumgait",1],["XA","Stepanakert",1],["YE","Yevlakh municipality",1],["ABS","Absheron"],["AGC","Aghjabadi"],["AGM","Agdam"],["AGS","Agdash"],["AGA","Agstafa"],["AGU","Agsu"],["AST","Astara"],["BAB","Babek"],["BAL","Balakan"],["BAR","Barda"],["BEY","Beylagan"],["BIL","Bilasuvar"],["CAB","Jabrayil"],["CAL","Jalilabad"],["CUL","Julfa"],["DAS","Dashkasan"],["FUZ","Fuzuli"],["GAD","Gadabay"],["GOR","Goranboy"],["GOY","Goychay"],["GYG","Goygol"],["HAC","Hajigabul"],["IMI","Imishli"],["ISM","Ismayilli"],["KAL","Kalbajar"],["KAN","Kangarli"],["KUR","Kurdamir"],["LAC","Lachin"],["LAN","Lankaran"],["LER","Lerik"],["MAS","Masally"],["NEF","Neftchala"],["OGU","Oghuz"],["ORD","Ordubad"],["QAB","Qabala"],["QAX","Qakh"],["QAZ","Qazakh"],["QOB","Gobustan"],["QBA","Quba"],["QBI","Qubadli"],["QUS","Qusar"],["SAT","Saatly"],["SAB","Sabirabad"],["SBN","Shabran"],["SAD","Sadarak"],["SAH","Shahbuz"],["SAK","Shaki"],["SAL","Salyan"],["SMI","Shamakhi"],["SKR","Shamkir"],["SMX","Samukh"],["SAR","Sharur"],["SIY","Siyazan"],["SUS","Shusha"],["TAR","Tartar"],["TOV","Tovuz"],["UCA","Ujar"],["XAC","Khachmaz"],["XIZ","Khizi"],["XCI","Khojaly"],["XVD","Khojavend"],["YAR","Yardimli"],["YEV","Yevlakh"],["ZAN","Zangilan"],["ZAQ","Zagatala"],["ZAR","Zardab"]],"Bangladesh":[["05","Bagerhat"],["01","Bandarban"],["02","Barguna"],["06","Barishal"],["07","Bhola"],["03","Bogura"],["04","Brahmanbaria"],["09","Chandpur"],["10","Chattogram"],["12","Chuadanga"],["11","Cox's Bazar"],["08","Cumilla"],["13","Dhaka"],["14","Dinajpur"],["15","Faridpur"],["16","Feni"],["19","Gaibandha"],["18","Gazipur"],["17","Gopalganj"],["20","Habiganj"],["21","Jamalpur"],["22","Jashore"],["25","Jhalakathi"],["23","Jhenaidah"],["24","Joypurhat"],["29","Khagrachhari"],["27","Khulna"],["26","Kishoreganj"],["28","Kurigram"],["30","Kushtia"],["31","Lakshmipur"],["32","Lalmonirhat"],["36","Madaripur"],["37","Magura"],["33","Manikganj"],["39","Meherpur"],["38","Moulvibazar"],["35","Munshiganj"],["34","Mymensingh"],["48","Naogaon"],["43","Narail"],["40","Narayanganj"],["42","Narsingdi"],["44","Natore"],["45","Chapai Nawabganj"],["41","Netrakona"],["46","Nilphamari"],["47","Noakhali"],["49","Pabna"],["52","Panchagarh"],["51","Patuakhali"],["50","Pirojpur"],["53","Rajbari"],["54","Rajshahi"],["56","Rangamati"],["55","Rangpur"],["58","Satkhira"],["62","Shariatpur"],["57","Sherpur"],["59","Sirajganj"],["61","Sunamganj"],["60","Sylhet"],["63","Tangail"],["64","Thakurgaon"]],"Belarus":[["HM","City of Minsk",1],["BR","Brest Region"],["HO","Gomel Region"],["HR","Grodno Region"],["MA","Mogilev Region"],["MI","Minsk Region"],["VI","Vitebsk Region"]],"Belgium":[["BRU","Burgenland",10],["VAN","Antwerp"],["WBR","Walloon Brabant"],["WHT","Hainaut"],["WLG","Li\u00e8ge"],["VLI","Limburg"],["WLX","Luxembourg"],["WNA","Namur"],["VOV","East Flanders"],["VBR","Flemish Brabant"],["VWV","West Flanders"]],"Benin":[["AL","Alibori"],["AK","Atakora"],["AQ","Atlantique"],["BO","Borgou"],["CO","Collines"],["KO","Kouffo"],["DO","Donga"],["LI","Littoral"],["MO","Mono"],["OU","Ou\u00e9m\u00e9"],["PL","Plateau"],["ZO","Zou"]],"Bolivia":[["H","Chuquisaca"],["C","Cochabamba"],["B","El Beni"],["L","La Paz"],["O","Oruro"],["N","Pando"],["P","Potos\u00ed"],["S","Santa Cruz"],["T","Tarija"]],"Bosnia and Herzegovina":[["BIH","Federation of Bosnia and Herzegovina",1],["SRP","Republika Srpska",1],["BRC","Br\u010dko District",1]],"Brazil":[["AC","Acre"],["AL","Alagoas"],["AP","Amap\u00e1"],["AM","Amazonas"],["BA","Bahia"],["CE","Cear\u00e1"],["DF","Distrito Federal"],["ES","Esp\u00edrito Santo"],["GO","Goi\u00e1s"],["MA","Maranh\u00e3o"],["MT","Mato Grosso"],["MS","Mato Grosso do Sul"],["MG","Minas Gerais"],["PA","Par\u00e1"],["PB","Para\u00edba"],["PR","Paran\u00e1"],["PE","Pernambuco"],["PI","Piau\u00ed"],["RJ","Rio de Janeiro"],["RN","Rio Grande do Norte"],["RS","Rio Grande do Sul"],["RO","Rond\u00f4nia"],["RR","Roraima"],["SC","Santa Catarina"],["SP","S\u00e3o Paulo"],["SE","Sergipe"],["TO","Tocantins"]],"Bulgaria":[["01","Blagoevgrad"],["02","Burgas"],["08","Dobrich"],["07","Gabrovo"],["26","Haskovo"],["09","Kardzhali"],["10","Kyustendil"],["11","Lovech"],["12","Montana"],["13","Pazardzhik"],["14","Pernik"],["15","Pleven"],["16","Plovdiv"],["17","Razgrad"],["18","Ruse"],["27","Shumen"],["19","Silistra"],["20","Sliven"],["21","Smolyan"],["23","Sofia"],["22","Sofia-Grad"],["24","Stara Zagora"],["25","Targovishte"],["03","Varna"],["04","Veliko Tarnovo"],["05","Vidin"],["06","Vratsa"],["28","Yambol"]],"Canada":[["AB","Alberta"],["BC","British Columbia"],["MB","Manitoba"],["NB","New Brunswick"],["NL","Newfoundland and Labrador"],["NT","Northwest Territories"],["NS","Nova Scotia"],["NU","Nunavut"],["ON","Ontario"],["PE","Prince Edward Island"],["QC","Quebec"],["SK","Saskatchewan"],["YT","Yukon Territory"]],"Chile":[["AI","Ais\u00e9n del General Carlos Iba\u00f1ez del Campo"],["AN","Antofagasta"],["AP","Arica y Parinacota"],["AR","La Araucan\u00eda"],["AT","Atacama"],["BI","Biob\u00edo"],["CO","Coquimbo"],["LI","Libertador General Bernardo O'Higgins"],["LL","Los Lagos"],["LR","Los R\u00edos"],["MA","Magallanes"],["ML","Maule"],["NB","\u00d1uble"],["RM","Regi\u00f3n Metropolitana de Santiago"],["TA","Tarapac\u00e1"],["VS","Valpara\u00edso"]],"China":[["AH","Anhui \/ \u5b89\u5fbd\u7701"],["BJ","Beijing \/ \u5317\u4eac\u5e02"],["CQ","Chongqing \/ \u91cd\u5e86\u5e02"],["FJ","Fujian \/ \u798f\u5efa\u7701"],["GS","Gansu \/ \u7518\u8083\u7701"],["GD","Guangdong \/ \u5e7f\u4e1c\u7701"],["GX","Guangxi Zhuangzu \/ \u5e7f\u897f\u58ee\u65cf\u81ea\u6cbb\u533a"],["GZ","Guizhou \/ \u8d35\u5dde\u7701"],["HI","Hainan \/ \u6d77\u5357\u7701"],["HE","Hebei \/ \u6cb3\u5317\u7701"],["HL","Heilongjiang \/ \u9ed1\u9f99\u6c5f\u7701"],["HA","Henan \/ \u6cb3\u5357\u7701"],["HB","Hubei \/ \u6e56\u5317\u7701"],["HN","Hunan \/ \u6e56\u5357\u7701"],["NM","Inner Mongolia \/ \u5185\u8499\u53e4\u81ea\u6cbb\u533a"],["JS","Jiangsu \/ \u6c5f\u82cf\u7701"],["JX","Jiangxi \/ \u6c5f\u897f\u7701"],["JL","Jilin \/ \u5409\u6797\u7701"],["LN","Liaoning \/ \u8fbd\u5b81\u7701"],["MO","Macao \/ \u6fb3\u95e8\u7279\u522b\u884c\u653f\u533a"],["NX","Ningxia Huizi \/ \u5b81\u590f\u56de\u65cf\u81ea\u6cbb\u533a"],["QH","Qinghai \/ \u9752\u6d77\u7701"],["SN","Shaanxi \/ \u9655\u897f\u7701"],["SD","Shandong \/ \u5c71\u4e1c\u7701"],["SH","Shanghai \/ \u4e0a\u6d77\u5e02"],["SX","Shanxi \/ \u5c71\u897f\u7701"],["SC","Sichuan \/ \u56db\u5ddd\u7701"],["TJ","Tianjin \/ \u5929\u6d25\u5e02"],["XZ","Tibet \/ \u897f\u85cf\u81ea\u6cbb\u533a"],["XJ","Xinjiang \/ \u65b0\u7586\u7ef4\u543e\u5c14\u81ea\u6cbb\u533a"],["YN","Yunnan \/ \u4e91\u5357\u7701"],["ZJ","Zhejiang \/ \u6d59\u6c5f\u7701"]],"Colombia":[["DC","Capital District",1],["AMA","Amazonas"],["ANT","Antioquia"],["ARA","Arauca"],["ATL","Atl\u00e1ntico"],["BOL","Bol\u00edvar"],["BOY","Boyac\u00e1"],["CAL","Caldas"],["CAQ","Caquet\u00e1"],["CAS","Casanare"],["CAU","Cauca"],["CES","Cesar"],["CHO","Choc\u00f3"],["COR","C\u00f3rdoba"],["CUN","Cundinamarca"],["GUA","Guain\u00eda"],["GUV","Guaviare"],["HUI","Huila"],["LAG","La Guajira"],["MAG","Magdalena"],["MET","Meta"],["NAR","Nari\u00f1o"],["NSA","Norte de Santander"],["PUT","Putumayo"],["QUI","Quind\u00edo"],["RIS","Risaralda"],["SAN","Santander"],["SAP","San Andr\u00e9s & Providencia"],["SUC","Sucre"],["TOL","Tolima"],["VAC","Valle del Cauca"],["VAU","Vaup\u00e9s"],["VID","Vichada"]],"Costa Rica":[["A","Alajuela"],["C","Cartago"],["G","Guanacaste"],["H","Heredia"],["L","Lim\u00f3n"],["P","Puntarenas"],["SJ","San Jos\u00e9"]],"Croatia":[["21","Zagreb City",10],["07","Bjelovar-Bilogora"],["12","Brod-Posavina"],["19","Dubrovnik-Neretva"],["18","Istria"],["04","Karlovac"],["06","Koprivnica-Kri\u017eevci"],["02","Krapina-Zagorje"],["09","Lika-Senj"],["20","Me\u0111imurje"],["14","Osijek-Baranja"],["11","Po\u017eega-Slavonia"],["08","Primorje-Gorski Kotar"],["03","Sisak-Moslavina"],["17","Split-Dalmatia"],["15","\u0160ibenik-Knin"],["05","Vara\u017edin"],["10","Virovitica-Podravina"],["16","Vukovar-Srijem"],["13","Zadar"],["01","Zagreb County"]],"Cyprus":[["04","Famagusta"],["06","Kyrenia"],["03","Larnaca"],["02","Limassol"],["01","Nicosia"],["05","Paphos"]],"Czechia":[["10","Prague",1],["201","Bene\u0161ov"],["202","Beroun"],["641","Blansko"],["642","Brno-City"],["643","Brno-Country"],["801","Brunt\u00e1l"],["644","B\u0159eclav"],["511","\u010cesk\u00e1 L\u00edpa"],["311","\u010cesk\u00e9 Bud\u011bjovice"],["312","\u010cesk\u00fd Krumlov"],["421","D\u011b\u010d\u00edn"],["321","Doma\u017elice"],["802","Fr\u00fddek-M\u00edstek"],["631","Havl\u00ed\u010dk\u016fv Brod"],["645","Hodon\u00edn"],["521","Hradec Kr\u00e1lov\u00e9"],["411","Cheb"],["422","Chomutov"],["531","Chrudim"],["512","Jablonec nad Nisou"],["711","Jesen\u00edk"],["522","Ji\u010d\u00edn"],["632","Jihlava"],["313","Jind\u0159ich\u016fv Hradec"],["412","Karlovy Vary"],["803","Karvin\u00e1"],["203","Kladno"],["322","Klatovy"],["204","Kol\u00edn"],["721","Krom\u011b\u0159\u00ed\u017e"],["205","Kutn\u00e1 Hora"],["513","Liberec"],["423","Litom\u011b\u0159ice"],["424","Louny"],["206","M\u011bln\u00edk"],["207","Mlad\u00e1 Boleslav"],["425","Most"],["523","N\u00e1chod"],["804","Nov\u00fd Ji\u010d\u00edn"],["208","Nymburk"],["712","Olomouc"],["805","Opava"],["806","Ostrava-City"],["532","Pardubice"],["633","Pelh\u0159imov"],["314","P\u00edsek"],["324","Plze\u0148-South"],["323","Plze\u0148-City"],["325","Plze\u0148-North"],["209","Prague-East"],["20A","Prague-West"],["315","Prachatice"],["713","Prost\u011bjov"],["714","P\u0159erov"],["20B","P\u0159\u00edbram"],["20C","Rakovn\u00edk"],["326","Rokycany"],["524","Rychnov nad Kn\u011b\u017enou"],["514","Semily"],["413","Sokolov"],["316","Strakonice"],["533","Svitavy"],["715","\u0160umperk"],["317","T\u00e1bor"],["327","Tachov"],["426","Teplice"],["525","Trutnov"],["634","T\u0159eb\u00ed\u010d"],["722","Uhersk\u00e9 Hradi\u0161t\u011b"],["427","\u00dast\u00ed nad Labem"],["534","\u00dast\u00ed nad Orlic\u00ed"],["723","Vset\u00edn"],["646","Vy\u0161kov"],["724","Zl\u00edn"],["647","Znojmo"],["635","\u017d\u010f\u00e1r nad S\u00e1zavou"]],"Denmark":[["84","Capital Region of Denmark",1],["82","Central Denmark"],["81","North Jutland"],["83","Southern Denmark"],["85","Zealand"]],"Dominican Republic":[["01","Distrito Nacional",1],["02","Azua"],["03","Baoruco"],["04","Barahona"],["05","Dajab\u00f3n"],["06","Duarte"],["07","El\u00edas Pi\u00f1a"],["08","El Seibo"],["09","Espaillat"],["10","Independencia"],["11","La Altagracia"],["12","La Romana"],["13","La Vega"],["14","Mar\u00eda Trinidad S\u00e1nchez"],["15","Monte Cristi"],["16","Pedernales"],["17","Peravia"],["18","Puerto Plata"],["19","Hermanas Mirabal"],["20","Saman\u00e1"],["21","San Crist\u00f3bal"],["22","San Juan"],["23","San Pedro de Macor\u00eds"],["24","S\u00e1nchez Ram\u00edrez"],["25","Santiago"],["26","Santiago Rodr\u00edguez"],["27","Valverde"],["28","Monse\u00f1or Nouel"],["29","Monte Plata"],["30","Hato Mayor"],["31","San Jos\u00e9 de Ocoa"],["32","Santo Domingo"],["33","Cibao Nordeste",-1],["34","Cibao Noroeste",-1],["35","Cibao Norte",-1],["36","Cibao Sur",-1],["37","El Valle",-1],["38","Enriquillo",-1],["39","Hig\u00fcamo",-1],["40","Ozama",-1],["41","Valdesia",-1],["42","Yuma",-1]],"Ecuador":[["A","Azuay"],["B","Bol\u00edvar"],["F","Ca\u00f1ar"],["C","Carchi"],["H","Chimborazo"],["X","Cotopaxi"],["O","El Oro"],["E","Esmeraldas"],["W","Gal\u00e1pagos"],["G","Guayas"],["I","Imbabura"],["L","Loja"],["R","Los R\u00edos"],["M","Manab\u00ed"],["S","Morona Santiago"],["N","Napo"],["D","Orellana"],["Y","Pastaza"],["P","Pichincha"],["SE","Santa Elena"],["SD","Santo Domingo de los Ts\u00e1chilas"],["U","Sucumb\u00edos"],["T","Tungurahua"],["Z","Zamora Chinchipe"]],"Egypt":[["ALX","Alexandria"],["ASN","Aswan"],["AST","Asyut"],["BA","Red Sea"],["BH","Beheira"],["BNS","Beni Suef"],["C","Cairo"],["DK","Dakahlia"],["DT","Damietta"],["FYM","Faiyum"],["GH","Gharbia"],["GZ","Giza"],["IS","Ismailia"],["JS","South Sinai"],["KB","Qalyubia"],["KFS","Kafr el-Sheikh"],["KN","Qena"],["LX","Luxor"],["MN","Minya"],["MNF","Monufia"],["MT","Matrouh"],["PTS","Port Said"],["SHG","Sohag"],["SHR","Al Sharqia"],["SIN","North Sinai"],["SUZ","Suez"],["WAD","New Valley"]],"El Salvador":[["AH","Ahuachap\u00e1n"],["CA","Caba\u00f1as"],["CH","Chalatenango"],["CU","Cuscatl\u00e1n"],["LI","La Libertad"],["PA","La Paz"],["UN","La Uni\u00f3n"],["MO","Moraz\u00e1n"],["SM","San Miguel"],["SS","San Salvador"],["SV","San Vicente"],["SA","Santa Ana"],["SO","Sonsonate"],["US","Usulut\u00e1n"]],"Estonia":[["37","Harju"],["39","Hiiu"],["45","Ida-Viru"],["50","J\u00f5geva"],["52","J\u00e4rva"],["56","L\u00e4\u00e4ne"],["60","L\u00e4\u00e4ne-Viru"],["64","P\u00f5lva"],["68","P\u00e4rnu"],["71","Rapla"],["74","Saare"],["79","Tartu"],["81","Valga"],["84","Viljandi"],["87","V\u00f5ru"]],"Finland":[["01","\u00c5land"],["02","South Karelia"],["03","Southern Ostrobothnia"],["04","Southern Savonia"],["05","Kainuu"],["06","Tavastia Proper"],["07","Central Ostrobothnia"],["08","Central Finland"],["09","Kymenlaakso"],["10","Lapland"],["11","Pirkanmaa"],["12","Ostrobothnia"],["13","North Karelia"],["14","Northern Ostrobothnia"],["15","Northern Savonia"],["16","P\u00e4ij\u00e4nne Tavastia"],["17","Satakunta"],["18","Uusimaa"],["19","Southwest Finland"]],"France":[["75C","Paris",2],["69M","Lyon Metropolis",1],["01","Ain"],["02","Aisne"],["03","Allier"],["04","Alpes-de-Haute-Provence"],["06","Alpes-Maritimes"],["07","Ard\u00e8che"],["08","Ardennes"],["09","Ari\u00e8ge"],["10","Aube"],["11","Aude"],["12","Aveyron"],["67","Bas-Rhin"],["13","Bouches-du-Rh\u00f4ne"],["14","Calvados"],["15","Cantal"],["16","Charente"],["17","Charente-Maritime"],["18","Cher"],["19","Corr\u00e8ze"],["2A","Corse-du-Sud"],["21","C\u00f4te-d'Or"],["22","C\u00f4tes-d'Armor"],["23","Creuse"],["79","Deux-S\u00e8vres"],["24","Dordogne"],["25","Doubs"],["26","Dr\u00f4me"],["91","Essonne"],["27","Eure"],["28","Eure-et-Loir"],["29","Finist\u00e8re"],["30","Gard"],["32","Gers"],["33","Gironde"],["2B","Haute-Corse"],["31","Haute-Garonne"],["43","Haute-Loire"],["52","Haute-Marne"],["05","Hautes-Alpes"],["70","Haute-Sa\u00f4ne"],["74","Haute-Savoie"],["65","Hautes-Pyr\u00e9n\u00e9es"],["87","Haute-Vienne"],["68","Haut-Rhin"],["92","Hauts-de-Seine"],["34","H\u00e9rault"],["35","Ille-et-Vilaine"],["36","Indre"],["37","Indre-et-Loire"],["38","Is\u00e8re"],["39","Jura"],["40","Landes"],["42","Loire"],["44","Loire-Atlantique"],["45","Loiret"],["41","Loir-et-Cher"],["46","Lot"],["47","Lot-et-Garonne"],["48","Loz\u00e8re"],["49","Maine-et-Loire"],["50","Manche"],["51","Marne"],["53","Mayenne"],["54","Meurthe-et-Moselle"],["55","Meuse"],["56","Morbihan"],["57","Moselle"],["58","Ni\u00e8vre"],["59","Nord"],["60","Oise"],["61","Orne"],["62","Pas-de-Calais"],["63","Puy-de-D\u00f4me"],["64","Pyr\u00e9n\u00e9es-Atlantiques"],["66","Pyr\u00e9n\u00e9es-Orientales"],["69","Rh\u00f4ne"],["71","Sa\u00f4ne-et-Loire"],["72","Sarthe"],["73","Savoie"],["77","Seine-et-Marne"],["76","Seine-Maritime"],["93","Seine-Saint-Denis"],["80","Somme"],["81","Tarn"],["82","Tarn-et-Garonne"],["90","Territoire de Belfort"],["94","Val-de-Marne"],["95","Val-d'Oise"],["83","Var"],["84","Vaucluse"],["85","Vend\u00e9e"],["86","Vienne"],["88","Vosges"],["89","Yonne"],["78","Yvelines"]],"Georgia":[["TB","Tbilisi",1],["GU","Guria"],["IM","Imereti"],["KA","Kakheti"],["KK","Kvemo Kartli"],["MM","Mtskheta-Mtianeti"],["RL","Racha-Lechkhumi and Kvemo Svaneti"],["SZ","Samegrelo-Zemo Svaneti"],["SJ","Samtskhe-Javakheti"],["SK","Shida Kartli"],["AB","Abkhazia",-1],["AJ","Adjara",-1]],"Germany":[["BW","Baden-W\u00fcrttemberg"],["BY","Bavaria"],["BE","Berlin"],["BB","Brandenburg"],["HB","Bremen"],["HH","Hamburg"],["HE","Hesse"],["MV","Mecklenburg-Western Pomerania"],["NI","Lower Saxony"],["NW","North Rhine-Westphalia"],["RP","Rhineland-Palatinate"],["SL","Saarland"],["SN","Saxony"],["ST","Saxony-Anhalt"],["SH","Schleswig-Holstein"],["TH","Thuringia"]],"Ghana":[["AF","Ahafo"],["AH","Ashanti"],["BA","Brong-Ahafo"],["BO","Bono"],["BE","Bono East"],["CP","Central"],["EP","Eastern"],["AA","Greater Accra"],["NE","North East"],["NP","Northern"],["OT","Oti"],["SV","Savannah"],["UE","Upper East"],["UW","Upper West"],["TV","Volta"],["WP","Western"],["WN","Western North"]],"Greece":[["A","Eastern Macedonia and Thrace"],["B","Central Macedonia"],["C","Western Macedonia"],["D","Epirus"],["E","Thessaly"],["F","Ionian Islands"],["G","Western Greece"],["H","Central Greece"],["I","Attica"],["J","Peloponnese"],["K","Northern Aegean"],["L","Southern Aegean"],["M","Crete"]],"Guatemala":[["AV","Alta Verapaz"],["BV","Baja Verapaz"],["CM","Chimaltenango"],["CQ","Chiquimula"],["PR","El Progreso"],["ES","Escuintla"],["GU","Guatemala"],["HU","Huehuetenango"],["IZ","Izabal"],["JA","Jalapa"],["JU","Jutiapa"],["PE","Pet\u00e9n"],["QZ","Quetzaltenango"],["QC","Quich\u00e9"],["RE","Retalhuleu"],["SA","Sacatep\u00e9quez"],["SM","San Marcos"],["SR","Santa Rosa"],["SO","Solol\u00e1"],["SU","Suchitep\u00e9quez"],["TO","Totonicap\u00e1n"],["ZA","Zacapa"]],"Honduras":[["AT","Atl\u00e1ntida"],["IB","Bay Islands"],["CH","Choluteca"],["CL","Col\u00f3n"],["CM","Comayagua"],["CP","Cop\u00e1n"],["CR","Cort\u00e9s"],["EP","El Para\u00edso"],["FM","Francisco Moraz\u00e1n"],["GD","Gracias a Dios"],["IN","Intibuc\u00e1"],["LE","Lempira"],["LP","La Paz"],["OC","Ocotepeque"],["OL","Olancho"],["SB","Santa B\u00e1rbara"],["VA","Valle"],["YO","Yoro"]],"Hong Kong":[["HONG KONG","Hong Kong Island"],["KOWLOON","Kowloon"],["NEW TERRITORIES","New Territories"]],"Hungary":[["BK","B\u00e1cs-Kiskun"],["BA","Baranya"],["BE","B\u00e9k\u00e9s"],["BZ","Borsod-Aba\u00faj-Zempl\u00e9n"],["BU","Budapest"],["CS","Csongr\u00e1d-Csan\u00e1d"],["FE","Fej\u00e9r"],["GS","Gy\u0151r-Moson-Sopron"],["HB","Hajd\u00fa-Bihar"],["HE","Heves"],["JN","J\u00e1sz-Nagykun-Szolnok"],["KE","Kom\u00e1rom-Esztergom"],["NO","N\u00f3gr\u00e1d"],["PE","Pest"],["SO","Somogy"],["SZ","Szabolcs-Szatm\u00e1r-Bereg"],["TO","Tolna"],["VA","Vas"],["VE","Veszpr\u00e9m"],["ZA","Zala"]],"Iceland":[["1","Capital Region",1],["2","Southern Peninsula"],["3","Western Region"],["4","Westfjords"],["5","Northwestern Region"],["6","Northeastern Region"],["7","Eastern Region"],["8","Southern Region"]],"India":[["DL","Delhi",1],["AP","Andhra Pradesh"],["AR","Arunachal Pradesh"],["AS","Assam"],["BR","Bihar"],["CT","Chhattisgarh"],["GA","Goa"],["GJ","Gujarat"],["HR","Haryana"],["HP","Himachal Pradesh"],["JK","Jammu and Kashmir"],["JH","Jharkhand"],["KA","Karnataka"],["KL","Kerala"],["MP","Madhya Pradesh"],["MH","Maharashtra"],["MN","Manipur"],["ML","Meghalaya"],["MZ","Mizoram"],["NL","Nagaland"],["OR","Odisha"],["PB","Punjab"],["RJ","Rajasthan"],["SK","Sikkim"],["TN","Tamil Nadu"],["TG","Telangana"],["TR","Tripura"],["UT","Uttarakhand"],["UP","Uttar Pradesh"],["WB","West Bengal"],["AN","Andaman and Nicobar Islands",-1],["CH","Chandigarh",-1],["DN","Dadra and Nagar Haveli",-1],["DD","Daman and Diu",-1],["LA","Ladakh",-1],["LD","Lakshadweep",-1],["PY","Pondicherry (Puducherry)",-1]],"Indonesia":[["AC","Aceh"],["BA","Bali"],["BB","Bangka-Belitung Islands"],["BE","Bengkulu"],["BT","Banten"],["GO","Gorontalo"],["JK","Jakarta"],["JA","Jambi"],["JB","West Java"],["JT","Central Java"],["JI","East Java"],["KB","West Kalimantan"],["KS","South Kalimantan"],["KT","Central Kalimantan"],["KI","East Kalimantan"],["KU","North Kalimantan"],["LA","Lampung"],["MA","Maluku"],["MU","North Maluku"],["NB","West Nusa Tenggara"],["NT","East Nusa Tenggara"],["PA","Papua"],["PB","West Papua"],["RI","Riau"],["KR","Riau Islands"],["SR","West Sulawesi"],["SN","South Sulawesi"],["ST","Central Sulawesi"],["SG","Southeast Sulawesi"],["SA","North Sulawesi"],["SB","West Sumatra"],["SS","South Sumatra"],["SU","North Sumatra"],["YO","Yogyakarta"]],"Iran":[["32","Alborz"],["03","Ardabil"],["06","Bushehr"],["08","Chaharmahal and Bakhtiari"],["01","East Azerbaijan"],["14","Fars"],["19","Gilan"],["27","Golestan"],["24","Hamadan"],["23","Hormozgan"],["05","Ilam"],["04","Isfahan"],["17","Kermanshah"],["15","Kerman"],["10","Khuzestan"],["18","Kohgiluyeh and Boyer-Ahmad"],["16","Kurdistan"],["20","Lorestan"],["22","Markazi"],["21","Mazandaran"],["31","North Khorasan"],["28","Qazvin"],["26","Qom"],["30","Razavi Khorasan"],["12","Semnan"],["13","Sistan and Baluchestan"],["29","South Khorasan"],["07","Tehran"],["02","West Azerbaijan"],["25","Yazd"],["11","Zanjan"]],"Ireland":[["CW","Carlow"],["CN","Cavan"],["CE","Clare"],["CO","Cork"],["DL","Donegal"],["D","Dublin"],["G","Galway"],["KY","Kerry"],["KE","Kildare"],["KK","Kilkenny"],["LS","Laois"],["LM","Leitrim"],["LK","Limerick"],["LD","Longford"],["LH","Louth"],["MO","Mayo"],["MH","Meath"],["MN","Monaghan"],["OY","Offaly"],["RN","Roscommon"],["SO","Sligo"],["TA","Tipperary"],["WD","Waterford"],["WH","Westmeath"],["WX","Wexford"],["WW","Wicklow"]],"Italy":[["AG","Agrigento"],["AL","Alessandria"],["AN","Ancona"],["AO","Aosta"],["AR","Arezzo"],["AP","Ascoli Piceno"],["AT","Asti"],["AV","Avellino"],["BA","Bari"],["BT","Barletta-Andria-Trani"],["BL","Belluno"],["BN","Benevento"],["BG","Bergamo"],["BI","Biella"],["BO","Bologna"],["BZ","Bolzano"],["BS","Brescia"],["BR","Brindisi"],["CA","Cagliari"],["CL","Caltanissetta"],["CB","Campobasso"],["CE","Caserta"],["CT","Catania"],["CZ","Catanzaro"],["CH","Chieti"],["CO","Como"],["CS","Cosenza"],["CR","Cremona"],["KR","Crotone"],["CN","Cuneo"],["EN","Enna"],["FM","Fermo"],["FE","Ferrara"],["FI","Firenze"],["FG","Foggia"],["FC","Forl\u00ec-Cesena"],["FR","Frosinone"],["GE","Genova"],["GO","Gorizia"],["GR","Grosseto"],["IM","Imperia"],["IS","Isernia"],["AQ","L'Aquila"],["SP","La Spezia"],["LT","Latina"],["LE","Lecce"],["LC","Lecco"],["LI","Livorno"],["LO","Lodi"],["LU","Lucca"],["MC","Macerata"],["MN","Mantova"],["MS","Massa-Carrara"],["MT","Matera"],["ME","Messina"],["MI","Milano"],["MO","Modena"],["MB","Monza and Brianza"],["NA","Napoli"],["NO","Novara"],["NU","Nuoro"],["OR","Oristano"],["PD","Padova"],["PA","Palermo"],["PR","Parma"],["PV","Pavia"],["PG","Perugia"],["PU","Pesaro and Urbino"],["PE","Pescara"],["PC","Piacenza"],["PI","Pisa"],["PT","Pistoia"],["PN","Pordenone"],["PZ","Potenza"],["PO","Prato"],["RG","Ragusa"],["RA","Ravenna"],["RC","Reggio Calabria"],["RE","Reggio Emilia"],["RI","Rieti"],["RN","Rimini"],["RM","Roma"],["RO","Rovigo"],["SA","Salerno"],["SS","Sassari"],["SV","Savona"],["SI","Siena"],["SR","Siracusa"],["SO","Sondrio"],["SU","Sud Sardegna"],["TA","Taranto"],["TE","Teramo"],["TR","Terni"],["TO","Torino"],["TP","Trapani"],["TN","Trento"],["TV","Treviso"],["TS","Trieste"],["UD","Udine"],["VA","Varese"],["VE","Venezia"],["VB","Verbano-Cusio-Ossola"],["VC","Vercelli"],["VR","Verona"],["VV","Vibo Valentia"],["VI","Vicenza"],["VT","Viterbo"]],"Jamaica":[["01","Kingston"],["02","Saint Andrew"],["03","Saint Thomas"],["04","Portland"],["05","Saint Mary"],["06","Saint Ann"],["07","Trelawny"],["08","Saint James"],["09","Hanover"],["10","Westmoreland"],["11","Saint Elizabeth"],["12","Manchester"],["13","Clarendon"],["14","Saint Catherine"]],"Japan":[["23","Aichi"],["05","Akita"],["02","Aomori"],["12","Chiba"],["38","Ehime"],["18","Fukui"],["40","Fukuoka"],["07","Fukushima"],["21","Gifu"],["10","Gunma"],["34","Hiroshima"],["01","Hokkaido"],["28","Hyogo"],["08","Ibaraki"],["17","Ishikawa"],["03","Iwate"],["37","Kagawa"],["46","Kagoshima"],["14","Kanagawa"],["39","Kochi"],["43","Kumamoto"],["26","Kyoto"],["24","Mie"],["04","Miyagi"],["45","Miyazaki"],["20","Nagano"],["42","Nagasaki"],["29","Nara"],["15","Niigata"],["44","Oita"],["33","Okayama"],["47","Okinawa"],["27","Osaka"],["41","Saga"],["11","Saitama"],["25","Shiga"],["32","Shimane"],["22","Shizuoka"],["09","Tochigi"],["36","Tokushima"],["13","Tokyo"],["31","Tottori"],["16","Toyama"],["30","Wakayama"],["06","Yamagata"],["35","Yamaguchi"],["19","Yamanashi"]],"Kenya":[["01","Baringo"],["02","Bomet"],["03","Bungoma"],["04","Busia"],["05","Elgeyo-Marakwet"],["06","Embu"],["07","Garissa"],["08","Homa Bay"],["09","Isiolo"],["10","Kajiado"],["11","Kakamega"],["12","Kericho"],["13","Kiambu"],["14","Kilifi"],["15","Kirinyaga"],["16","Kisii"],["17","Kisumu"],["18","Kitui"],["19","Kwale"],["20","Laikipia"],["21","Lamu"],["22","Machakos"],["23","Makueni"],["24","Mandera"],["25","Marsabit"],["26","Meru"],["27","Migori"],["28","Mombasa"],["29","Murang\u2019a"],["30","Nairobi County"],["31","Nakuru"],["32","Nandi"],["33","Narok"],["34","Nyamira"],["35","Nyandarua"],["36","Nyeri"],["37","Samburu"],["38","Siaya"],["39","Taita-Taveta"],["40","Tana River"],["41","Tharaka-Nithi"],["42","Trans Nzoia"],["43","Turkana"],["44","Uasin Gishu"],["45","Vihiga"],["46","Wajir"],["47","West Pokot"]],"Korea, Republic of":[["11","Seoul",9],["26","Busan",8],["27","Daegu",8],["30","Daejeon",8],["29","Gwangju",8],["28","Incheon",8],["31","Ulsan",8],["43","Chungbuk"],["44","Chungnam"],["42","Gangwon"],["41","Gyeonggi"],["47","Gyeongbuk"],["48","Gyeongnam"],["45","Jeonbuk"],["46","Jeonnam"],["49","Jeju",-1],["50","Sejong",-2]],"Lao People's Democratic Republic":[["VT","Vientiane Prefecture",1],["AT","Attapeu"],["BK","Bokeo"],["BL","Bolikhamsai"],["CH","Champasak"],["HO","Houaphanh"],["KH","Khammouane"],["LM","Luang Namtha"],["LP","Luang Prabang"],["OU","Oudomxay"],["PH","Phongsaly"],["SL","Salavan"],["SV","Savannakhet"],["VI","Vientiane Province"],["XA","Sainyabuli"],["XE","Sekong"],["XI","Xiangkhouang"],["XS","Xaisomboun"]],"Latvia":[["DGV","Daugavpils",1],["JEL","Jelgava",1],["JUR","J\u016brmala",1],["LPX","Liep\u0101ja",1],["REZ","R\u0113zekne",1],["RIX","Riga",1],["VEN","Ventspils",1],["002","Aizkraukle"],["007","Al\u016bksne"],["011","\u0100da\u017ei"],["015","Balvi"],["016","Bauska"],["022","C\u0113sis"],["026","Dobele"],["033","Gulbene"],["041","Jelgava Municipality"],["042","J\u0113kabpils"],["047","Kr\u0101slava"],["050","Kuld\u012bga"],["052","\u0136ekava"],["054","Limba\u017ei"],["056","L\u012bv\u0101ni"],["058","Ludza"],["059","Madona"],["062","M\u0101rupe"],["067","Ogre"],["068","Olaine"],["073","Prei\u013ci"],["077","R\u0113zekne Municipality"],["080","Ropa\u017ei"],["087","Salaspils"],["088","Saldus"],["089","Saulkrasti"],["091","Sigulda"],["094","Smiltene"],["097","Talsi"],["099","Tukums"],["101","Valka"],["102","Varak\u013c\u0101ni"],["106","Ventspils Municipality"],["111","Aug\u0161daugava"],["112","Dienvidkurzeme"],["113","Valmiera"]],"Liberia":[["BM","Bomi"],["BG","Bong"],["GP","Gbarpolu"],["GB","Grand Bassa"],["CM","Grand Cape Mount"],["GG","Grand Gedeh"],["GK","Grand Kru"],["LO","Lofa"],["MG","Margibi"],["MY","Maryland"],["MO","Montserrado"],["NI","Nimba"],["RI","Rivercess"],["RG","River Gee"],["SI","Sinoe"]],"Lithuania":[["AL","Alytus County"],["KU","Kaunas County"],["KL","Klaip\u0117da County"],["MR","Marijampol\u0117 County"],["PN","Panev\u0117\u017eys County"],["SA","\u0160iauliai County"],["TA","Taurag\u0117 County"],["TE","Tel\u0161iai County"],["UT","Utena County"],["VL","Vilnius County"]],"Luxembourg":[["CA","Capellen"],["CL","Clervaux"],["DI","Diekirch"],["EC","Echternach"],["ES","Esch-sur-Alzette"],["GR","Grevenmacher"],["LU","Luxembourg"],["ME","Mersch"],["RD","Redange"],["RM","Remich"],["VD","Vianden"],["WI","Wiltz"]],"Malaysia":[["01","Johor"],["02","Kedah"],["03","Kelantan"],["15","Labuan"],["04","Melaka"],["05","Negeri Sembilan"],["06","Pahang"],["07","Pulau Pinang"],["08","Perak"],["09","Perlis"],["12","Sabah"],["13","Sarawak"],["10","Selangor"],["11","Terengganu"],["16","Putrajaya"],["14","Kuala Lumpur"]],"Mexico":[["CMX","Ciudad de M\u00e9xico",1],["AGU","Aguascalientes"],["BCN","Baja California"],["BCS","Baja California Sur"],["CAM","Campeche"],["CHP","Chiapas"],["CHH","Chihuahua"],["COA","Coahuila"],["COL","Colima"],["DUR","Durango"],["GUA","Guanajuato"],["GRO","Guerrero"],["HID","Hidalgo"],["MEX","M\u00e9xico"],["JAL","Jalisco"],["MIC","Michoac\u00e1n"],["MOR","Morelos"],["NAY","Nayarit"],["NLE","Nuevo Le\u00f3n"],["OAX","Oaxaca"],["PUE","Puebla"],["QUE","Quer\u00e9taro"],["ROO","Quintana Roo"],["SLP","San Luis Potos\u00ed"],["SIN","Sinaloa"],["SON","Sonora"],["TAB","Tabasco"],["TAM","Tamaulipas"],["TLA","Tlaxcala"],["VER","Veracruz"],["YUC","Yucat\u00e1n"],["ZAC","Zacatecas"]],"Moldova":[["CU","Chi\u0219in\u0103u",1],["BA","B\u0103l\u021bi",2],["AN","Anenii Noi"],["BS","Basarabeasca"],["BR","Briceni"],["CA","Cahul"],["CT","Cantemir"],["CL","C\u0103l\u0103ra\u0219i"],["CS","C\u0103u\u0219eni"],["CM","Cimi\u0219lia"],["CR","Criuleni"],["DO","Dondu\u0219eni"],["DR","Drochia"],["DU","Dub\u0103sari"],["ED","Edine\u021b"],["FA","F\u0103le\u0219ti"],["FL","Flore\u0219ti"],["GA","UTA G\u0103g\u0103uzia"],["GL","Glodeni"],["HI","H\u00eence\u0219ti"],["IA","Ialoveni"],["LE","Leova"],["NI","Nisporeni"],["OC","Ocni\u021ba"],["OR","Orhei"],["RE","Rezina"],["RI","R\u00ee\u0219cani"],["SI","S\u00eengerei"],["SO","Soroca"],["ST","Str\u0103\u0219eni"],["SD","\u0218old\u0103ne\u0219ti"],["SV","\u0218tefan Vod\u0103"],["TA","Taraclia"],["TE","Telene\u0219ti"],["UN","Ungheni"]],"Montenegro":[["16","Podgorica Capital City",1],["01","Andrijevica"],["02","Bar"],["03","Berane"],["04","Bijelo Polje"],["05","Budva"],["06","Cetinje"],["07","Danilovgrad"],["08","Herceg-Novi"],["09","Kola\u0161in"],["10","Kotor"],["11","Mojkovac"],["12","Nik\u0161i\u0107"],["13","Plav"],["14","Pljevlja"],["15","Plu\u017eine"],["17","Ro\u017eaje"],["18","\u0160avnik"],["19","Tivat"],["20","Ulcinj"],["21","\u017dabljak"],["22","Gusinje"],["23","Petnjica"],["24","Tuzi"]],"Mozambique":[["MPM","Maputo City",1],["P","Cabo Delgado"],["G","Gaza"],["I","Inhambane"],["B","Manica"],["L","Maputo Province"],["N","Nampula"],["A","Niassa"],["S","Sofala"],["T","Tete"],["Q","Zamb\u00e9zia"]],"Namibia":[["ER","Erongo"],["HA","Hardap"],["KA","Karas"],["KE","Kavango East"],["KW","Kavango West"],["KH","Khomas"],["KU","Kunene"],["OW","Ohangwena"],["OH","Omaheke"],["OS","Omusati"],["ON","Oshana"],["OT","Oshikoto"],["OD","Otjozondjupa"],["CA","Zambezi"]],"Nepal":[["BA","Bagmati"],["BH","Bheri"],["DH","Dhawalagiri"],["GA","Gandaki"],["JA","Janakpur"],["KA","Karnali"],["KO","Koshi"],["LU","Lumbini"],["MA","Mahakali"],["ME","Mechi"],["NA","Narayani"],["RA","Rapti"],["SA","Sagarmatha"],["SE","Seti"]],"Netherlands":[["DR","Drenthe"],["FL","Flevoland"],["FR","Friesland"],["GE","Gelderland"],["GR","Groningen"],["LI","Limburg"],["NB","North Brabant"],["NH","North Holland"],["OV","Overijssel"],["UT","Utrecht"],["ZE","Zeeland"],["ZH","South Holland"]],"New Zealand":[["AUK","Auckland"],["BOP","Bay of Plenty"],["CAN","Canterbury"],["GIS","Gisborne"],["HKB","Hawke's Bay"],["MWT","Manawatu-Wanganui"],["MBH","Marlborough"],["NSN","Nelson"],["NTL","Northland"],["OTA","Otago"],["STL","Southland"],["TKI","Taranaki"],["TAS","Tasman"],["WKO","Waikato"],["WGN","Wellington"],["WTC","West Coast"],["CIT","Chatham Islands",-1]],"Nicaragua":[["BO","Boaco"],["CA","Carazo"],["CI","Chinandega"],["CO","Chontales"],["ES","Estel\u00ed"],["GR","Granada"],["JI","Jinotega"],["LE","Le\u00f3n"],["MD","Madriz"],["MN","Managua"],["MS","Masaya"],["MT","Matagalpa"],["NS","Nueva Segovia"],["RI","Rivas"],["SJ","R\u00edo San Juan"],["AN","Costa Caribe Norte",-1],["AS","Costa Caribe Sur",-2]],"Nigeria":[["FC","Abuja"],["AB","Abia"],["AD","Adamawa"],["AK","Akwa Ibom"],["AN","Anambra"],["BA","Bauchi"],["BY","Bayelsa"],["BE","Benue"],["BO","Borno"],["CR","Cross River"],["DE","Delta"],["EB","Ebonyi"],["ED","Edo"],["EK","Ekiti"],["EN","Enugu"],["GO","Gombe"],["IM","Imo"],["JI","Jigawa"],["KD","Kaduna"],["KN","Kano"],["KT","Katsina"],["KE","Kebbi"],["KO","Kogi"],["KW","Kwara"],["LA","Lagos"],["NA","Nasarawa"],["NI","Niger"],["OG","Ogun"],["ON","Ondo"],["OS","Osun"],["OY","Oyo"],["PL","Plateau"],["RI","Rivers"],["SO","Sokoto"],["TA","Taraba"],["YO","Yobe"],["ZA","Zamfara"]],"North Macedonia":[["801","Aerodrom"],["802","Ara\u010dinovo"],["201","Berovo"],["501","Bitola"],["401","Bogdanci"],["601","Bogovinje"],["402","Bosilovo"],["602","Brvenica"],["803","Butel"],["814","Centar"],["313","Centar \u017dupa"],["815","\u010cair"],["109","\u010ca\u0161ka"],["210","\u010ce\u0161inovo-Oble\u0161evo"],["816","\u010cu\u010der-Sandevo"],["303","Debar"],["304","Debarca"],["203","Del\u010devo"],["502","Demir Hisar"],["103","Demir Kapija"],["406","Dojran"],["503","Dolneni"],["804","Gazi Baba"],["405","Gevgelija"],["805","Gjor\u010de Petrov"],["604","Gostivar"],["102","Gradsko"],["807","Ilinden"],["606","Jegunovce"],["205","Karbinci"],["808","Karpo\u0161"],["104","Kavadarci"],["307","Ki\u010devo"],["809","Kisela Voda"],["206","Ko\u010dani"],["407","Kon\u010de"],["701","Kratovo"],["702","Kriva Palanka"],["504","Krivoga\u0161tani"],["505","Kru\u0161evo"],["703","Kumanovo"],["704","Lipkovo"],["105","Lozovo"],["207","Makedonska Kamenica"],["308","Makedonski Brod"],["607","Mavrovo and Rostu\u0161a"],["506","Mogila"],["106","Negotino"],["507","Novaci"],["408","Novo Selo"],["310","Ohrid"],["208","Peh\u010devo"],["810","Petrovec"],["311","Plasnica"],["508","Prilep"],["209","Probi\u0161tip"],["409","Radovi\u0161"],["705","Rankovce"],["509","Resen"],["107","Rosoman"],["811","Saraj"],["812","Sopi\u0161te"],["706","Staro Nagori\u010dane"],["312","Struga"],["410","Strumica"],["813","Studeni\u010dani"],["108","Sveti Nikole"],["211","\u0160tip"],["817","\u0160uto Orizari"],["608","Tearce"],["609","Tetovo"],["403","Valandovo"],["404","Vasilevo"],["101","Veles"],["301","Vev\u010dani"],["202","Vinica"],["603","Vrap\u010di\u0161te"],["806","Zelenikovo"],["204","Zrnovci"],["605","\u017delino"]],"Norway":[["03","Oslo",1],["42","Agder"],["34","Innlandet"],["15","M\u00f8re and Romsdal"],["18","Nordland"],["11","Rogaland"],["54","Troms and Finnmark"],["50","Tr\u00f8ndelag"],["38","Vestfold and Telemark"],["46","Vestland"],["30","Viken"],["22","Jan Mayen",-1],["21","Svalbard",-1]],"Pakistan":[["IS","Islamabad Capital Territory",1],["JK","Azad Jammu and Kashmir"],["BA","Balochistan"],["TA","FATA"],["GB","Gilgit-Baltistan"],["KP","Khyber Pakhtunkhwa"],["PB","Punjab"],["SD","Sindh"]],"Panama":[["1","Bocas del Toro"],["2","Cocl\u00e9"],["3","Col\u00f3n"],["4","Chiriqu\u00ed"],["5","Dari\u00e9n"],["6","Herrera"],["7","Los Santos"],["8","Panam\u00e1"],["9","Veraguas"],["10","West Panam\u00e1"],["EM","Ember\u00e1"],["KY","Guna Yala"],["NB","Ng\u00f6be-Bugl\u00e9"]],"Paraguay":[["ASU","Asunci\u00f3n"],["16","Alto Paraguay"],["10","Alto Paran\u00e1"],["13","Amambay"],["19","Boquer\u00f3n"],["5","Caaguaz\u00fa"],["6","Caazap\u00e1"],["14","Canindey\u00fa"],["11","Central"],["1","Concepci\u00f3n"],["3","Cordillera"],["4","Guair\u00e1"],["7","Itap\u00faa"],["8","Misiones"],["12","\u00d1eembuc\u00fa"],["9","Paraguar\u00ed"],["15","Presidente Hayes"],["2","San Pedro"]],"Peru":[["AMA","Amazonas"],["ANC","Ancash"],["APU","Apur\u00edmac"],["ARE","Arequipa"],["AYA","Ayacucho"],["CAJ","Cajamarca"],["CUS","Cusco"],["CAL","El Callao"],["HUV","Huancavelica"],["HUC","Hu\u00e1nuco"],["ICA","Ica"],["JUN","Jun\u00edn"],["LAL","La Libertad"],["LAM","Lambayeque"],["LMA","Lima"],["LIM","Lima"],["LOR","Loreto"],["MDD","Madre de Dios"],["MOQ","Moquegua"],["PAS","Pasco"],["PIU","Piura"],["PUN","Puno"],["SAM","San Mart\u00edn"],["TAC","Tacna"],["TUM","Tumbes"],["UCA","Ucayali"]],"Philippines":[["00","Metro Manila",1],["ABR","Abra"],["AGN","Agusan del Norte"],["AGS","Agusan del Sur"],["AKL","Aklan"],["ALB","Albay"],["ANT","Antique"],["APA","Apayao"],["AUR","Aurora"],["BAS","Basilan"],["BAN","Bataan"],["BTN","Batanes"],["BTG","Batangas"],["BEN","Benguet"],["BIL","Biliran"],["BOH","Bohol"],["BUK","Bukidnon"],["BUL","Bulacan"],["CAG","Cagayan"],["CAN","Camarines Norte"],["CAS","Camarines Sur"],["CAM","Camiguin"],["CAP","Capiz"],["CAT","Catanduanes"],["CAV","Cavite"],["CEB","Cebu"],["COM","Compostela Valley"],["NCO","Cotabato"],["DAV","Davao del Norte"],["DAS","Davao del Sur"],["DAC","Davao Occidental"],["DAO","Davao Oriental"],["DIN","Dinagat Islands"],["EAS","Eastern Samar"],["GUI","Guimaras"],["IFU","Ifugao"],["ILN","Ilocos Norte"],["ILS","Ilocos Sur"],["ILI","Iloilo"],["ISA","Isabela"],["KAL","Kalinga"],["LUN","La Union"],["LAG","Laguna"],["LAN","Lanao del Norte"],["LAS","Lanao del Sur"],["LEY","Leyte"],["MAG","Maguindanao"],["MAD","Marinduque"],["MAS","Masbate"],["MSC","Misamis Occidental"],["MSR","Misamis Oriental"],["MOU","Mountain Province"],["NEC","Negros Occidental"],["NER","Negros Oriental"],["NSA","Northern Samar"],["NUE","Nueva Ecija"],["NUV","Nueva Vizcaya"],["MDC","Occidental Mindoro"],["MDR","Oriental Mindoro"],["PLW","Palawan"],["PAM","Pampanga"],["PAN","Pangasinan"],["QUE","Quezon"],["QUI","Quirino"],["RIZ","Rizal"],["ROM","Romblon"],["WSA","Samar"],["SAR","Sarangani"],["SIQ","Siquijor"],["SOR","Sorsogon"],["SCO","South Cotabato"],["SLE","Southern Leyte"],["SUK","Sultan Kudarat"],["SLU","Sulu"],["SUN","Surigao del Norte"],["SUR","Surigao del Sur"],["TAR","Tarlac"],["TAW","Tawi-Tawi"],["ZMB","Zambales"],["ZAN","Zamboanga del Norte"],["ZAS","Zamboanga del Sur"],["ZSI","Zamboanga Sibugay"]],"Poland":[["02","Lower Silesia"],["04","Kuyavia-Pomerania"],["06","Lublin"],["08","Lubusz"],["10","\u0141\u00f3d\u017a"],["12","Lesser Poland"],["14","Mazovia"],["16","Opole (Upper Silesia)"],["18","Subcarpathia"],["20","Podlaskie"],["22","Pomerania"],["24","Silesia"],["26","Holy Cross"],["28","Warmia-Masuria"],["30","Greater Poland"],["32","West Pomerania"]],"Portugal":[["01","Aveiro"],["02","Beja"],["03","Braga"],["04","Bragan\u00e7a"],["05","Castelo Branco"],["06","Coimbra"],["07","\u00c9vora"],["08","Faro"],["09","Guarda"],["10","Leiria"],["11","Lisbon"],["12","Portalegre"],["13","Porto"],["14","Santar\u00e9m"],["15","Set\u00fabal"],["16","Viana do Castelo"],["17","Vila Real"],["18","Viseu"],["20","Azores",-10],["30","Madeira",-10]],"Romania":[["B","Bucharest",1],["AB","Alba"],["AR","Arad"],["AG","Arge\u0219"],["BC","Bac\u0103u"],["BH","Bihor"],["BN","Bistri\u021ba-N\u0103s\u0103ud"],["BT","Boto\u0219ani"],["BR","Br\u0103ila"],["BV","Bra\u0219ov"],["BZ","Buz\u0103u"],["CL","C\u0103l\u0103ra\u0219i"],["CS","Cara\u0219-Severin"],["CJ","Cluj"],["CT","Constan\u021ba"],["CV","Covasna"],["DB","D\u00e2mbovi\u021ba"],["DJ","Dolj"],["GL","Gala\u021bi"],["GR","Giurgiu"],["GJ","Gorj"],["HR","Harghita"],["HD","Hunedoara"],["IL","Ialomi\u021ba"],["IS","Ia\u0219i"],["IF","Ilfov"],["MM","Maramure\u0219"],["MH","Mehedin\u021bi"],["MS","Mure\u0219"],["NT","Neam\u021b"],["OT","Olt"],["PH","Prahova"],["SJ","S\u0103laj"],["SM","Satu Mare"],["SB","Sibiu"],["SV","Suceava"],["TR","Teleorman"],["TM","Timi\u0219"],["TL","Tulcea"],["VL","V\u00e2lcea"],["VS","Vaslui"],["VN","Vrancea"]],"Russian Federation":[["MOW","Moscow",10],["SPE","Saint Petersburg",9],["AD","Adygea"],["AL","Altai"],["BA","Bashkortostan"],["BU","Buryatia"],["CE","Chechnya"],["CU","Chuvashia"],["DA","Dagestan"],["IN","Ingushetia"],["KB","Kabardino-Balkaria"],["KL","Kalmykia"],["KC","Karachay-Cherkessia"],["KR","Karelia"],["KK","Khakassia"],["KO","Komi"],["ME","Mari El"],["MO","Mordovia"],["SA","Sakha"],["SE","North Ossetia (Alania)"],["TA","Tatarstan"],["TY","Tuva"],["UD","Udmurtia"],["ALT","Altai Krai",-1],["KAM","Kamchatka Krai",-1],["KHA","Khabarovsk Krai",-1],["KDA","Krasnodar Krai",-1],["KYA","Krasnoyarsk Krai",-1],["PER","Perm Krai",-1],["PRI","Primorsky Krai",-1],["STA","Stavropol Krai",-1],["ZAB","Zabaykalsky Krai",-1],["AMU","Amur Oblast",-2],["ARK","Arkhangelsk Oblast",-2],["AST","Astrakhan Oblast",-2],["BEL","Belgorod Oblast",-2],["BRY","Bryansk Oblast",-2],["CHE","Chelyabinsk Oblast",-2],["IRK","Irkutsk Oblast",-2],["IVA","Ivanovo Oblast",-2],["KGD","Kaliningrad Oblast",-2],["KLU","Kaluga Oblast",-2],["KEM","Kemerovo Oblast",-2],["KIR","Kirov Oblast",-2],["KOS","Kostroma Oblast",-2],["KGN","Kurgan Oblast",-2],["KRS","Kursk Oblast",-2],["LEN","Leningrad Oblast",-2],["LIP","Lipetsk Oblast",-2],["MAG","Magadan Oblast",-2],["MOS","Moscow Oblast",-2],["MUR","Murmansk Oblast",-2],["NIZ","Nizhny Novgorod Oblast",-2],["NGR","Novgorod Oblast",-2],["NVS","Novosibirsk Oblast",-2],["OMS","Omsk Oblast",-2],["ORE","Orenburg Oblast",-2],["ORL","Oryol Oblast",-2],["PNZ","Penza Oblast",-2],["PSK","Pskov Oblast",-2],["ROS","Rostov Oblast",-2],["RYA","Ryazan Oblast",-2],["SAK","Sakhalin Oblast",-2],["SAM","Samara Oblast",-2],["SAR","Saratov Oblast",-2],["SMO","Smolensk Oblast",-2],["SVE","Sverdlovsk Oblast",-2],["TAM","Tambov Oblast",-2],["TOM","Tomsk Oblast",-2],["TUL","Tula Oblast",-2],["TVE","Tver Oblast",-2],["TYU","Tyumen Oblast",-2],["ULY","Ulyanovsk Oblast",-2],["VLA","Vladimir Oblast",-2],["VGG","Volgograd Oblast",-2],["VLG","Vologda Oblast",-2],["VOR","Voronezh Oblast",-2],["YAR","Yaroslavl Oblast",-2],["YEV","Jewish Autonomous Oblast",-3],["CHU","Chukotka Autonomous Okrug",-4],["KHM","Khanty-Mansi Autonomous Okrug",-4],["NEN","Nenets Autonomous Okrug",-4],["YAN","Yamalo-Nenets Autonomous Okrug",-4]],"Saint Kitts and Nevis":[["KN-K","Saint Kitts",2],["KN-N","Nevis",1],["KN-01","Christ Church Nichola Town"],["KN-02","Saint Anne Sandy Point"],["KN-03","Saint George Basseterre"],["KN-04","Saint George Gingerland"],["KN-05","Saint James Windward"],["KN-06","Saint John Capisterre"],["KN-07","Saint John Figtree"],["KN-08","Saint Mary Cayon"],["KN-09","Saint Paul Capisterre"],["KN-10","Saint Paul Charlestown"],["KN-11","Saint Peter Basseterre"],["KN-12","Saint Thomas Lowland"],["KN-13","Saint Thomas Middle Island"],["KN-15","Trinity Palmetto Point"]],"Senegal":[["DB","Diourbel"],["DK","Dakar"],["FK","Fatick"],["KA","Kaffrine"],["KD","Kolda"],["KE","K\u00e9dougou"],["KL","Kaolack"],["LG","Louga"],["MT","Matam"],["SE","S\u00e9dhiou"],["SL","Saint-Louis"],["TC","Tambacounda"],["TH","Thi\u00e8s"],["ZG","Ziguinchor"]],"Serbia":[["00","Belgrade",10],["14","Bor"],["11","Brani\u010devo"],["02","Central Banat"],["10","Danube"],["23","Jablanica"],["09","Kolubara"],["08","Ma\u010dva"],["17","Morava"],["20","Ni\u0161ava"],["01","North Ba\u010dka"],["03","North Banat"],["24","P\u010dinja"],["22","Pirot"],["13","Pomoravlje"],["19","Rasina"],["18","Ra\u0161ka"],["06","South Ba\u010dka"],["04","South Banat"],["07","Srem"],["12","\u0160umadija"],["21","Toplica"],["05","West Ba\u010dka"],["15","Zaje\u010dar"],["16","Zlatibor"],["25","Kosovo"],["26","Pe\u0107"],["27","Prizren"],["28","Kosovska Mitrovica"],["29","Kosovo-Pomoravlje"],["KM","Kosovo-Metohija"],["VO","Vojvodina"]],"Slovakia":[["BC","Bansk\u00e1 Bystrica"],["BL","Bratislava"],["KI","Ko\u0161ice"],["NI","Nitra"],["PV","Pre\u0161ov"],["TC","Tren\u010d\u00edn"],["TA","Trnava"],["ZI","\u017dilina"]],"Slovenia":[["001","Ajdov\u0161\u010dina"],["213","Ancarano"],["195","Apa\u010de"],["002","Beltinci"],["148","Benedikt"],["149","Bistrica ob Sotli"],["003","Bled"],["150","Bloke"],["004","Bohinj"],["005","Borovnica"],["006","Bovec"],["151","Braslov\u010de"],["007","Brda"],["008","Brezovica"],["009","Bre\u017eice"],["152","Cankova"],["011","Celje"],["012","Cerklje na Gorenjskem"],["013","Cerknica"],["014","Cerkno"],["153","Cerkvenjak"],["196","Cirkulane"],["015","\u010cren\u0161ovci"],["016","\u010crna na Koro\u0161kem"],["017","\u010crnomelj"],["018","Destrnik"],["019","Diva\u010da"],["154","Dobje"],["020","Dobrepolje"],["155","Dobrna"],["021","Dobrova-Polhov Gradec"],["156","Dobronak"],["022","Dol pri Ljubljani"],["157","Dolenjske Toplice"],["023","Dom\u017eale"],["024","Dornava"],["025","Dravograd"],["026","Duplek"],["027","Gorenja vas-Poljane"],["028","Gori\u0161nica"],["207","Gorje"],["029","Gornja Radgona"],["030","Gornji Grad"],["031","Gornji Petrovci"],["158","Grad"],["032","Grosuplje"],["159","Hajdina"],["160","Ho\u010de-Slivnica"],["161","Hodos"],["162","Horjul"],["034","Hrastnik"],["035","Hrpelje-Kozina"],["036","Idrija"],["037","Ig"],["038","Ilirska Bistrica"],["039","Ivan\u010dna Gorica"],["040","Isola"],["041","Jesenice"],["163","Jezersko"],["042","Jur\u0161inci"],["043","Kamnik"],["044","Kanal"],["045","Kidri\u010devo"],["046","Kobarid"],["047","Kobilje"],["048","Ko\u010devje"],["049","Komen"],["164","Komenda"],["050","Capodistria"],["197","Kosanjevica na Krki"],["165","Kostel"],["051","Kozje"],["052","Kranj"],["053","Kranjska Gora"],["166","Kri\u017eevci"],["054","Kr\u0161ko"],["055","Kungota"],["056","Kuzma"],["057","La\u0161ko"],["058","Lenart"],["059","Lendva"],["060","Litija"],["061","Ljubljana"],["062","Ljubno"],["063","Ljutomer"],["208","Log-Dragomer"],["064","Logatec"],["065","Lo\u0161ka dolina"],["066","Lo\u0161ki Potok"],["167","Lovrenc na Pohorju"],["067","Lu\u010de"],["068","Lukovica"],["069","Maj\u0161perk"],["198","Makole"],["070","Maribor"],["168","Markovci"],["071","Medvode"],["072","Menge\u0161"],["073","Metlika"],["074","Me\u017eica"],["169","Miklav\u017e na Dravskem polju"],["075","Miren-Kostanjevica"],["212","Mirna"],["170","Mirna Pe\u010d"],["076","Mislinja"],["199","Mokronog-Trebelno"],["077","Morav\u010de"],["078","Moravske Toplice"],["079","Mozirje"],["080","Murska Sobota"],["081","Muta"],["082","Naklo"],["083","Nazarje"],["084","Nova Gorica"],["085","Novo Mesto"],["086","Odranci"],["171","Oplotnica"],["087","Ormo\u017e"],["088","Osilnica"],["089","Pesnica"],["090","Pirano"],["091","Pivka"],["092","Pod\u010detrtek"],["172","Podlehnik"],["093","Podvelka"],["200","Polj\u010dane"],["173","Polzela"],["094","Postojna"],["174","Prebold"],["095","Preddvor"],["175","Prevalje"],["096","Ptuj"],["097","Puconci"],["098","Ra\u010de-Fram"],["099","Rade\u010de"],["100","Radenci"],["101","Radlje ob Dravi"],["102","Radovljica"],["103","Ravne na Koro\u0161kem"],["176","Razkri\u017eje"],["209","Re\u010dica ob Savinji"],["201","Ren\u010de-Vogrsko"],["104","Ribnica"],["177","Ribnica na Pohorju"],["106","Roga\u0161ka Slatina"],["105","Roga\u0161ovci"],["107","Rogatec"],["108","Ru\u0161e"],["178","Selnica ob Dravi"],["109","Semi\u010d"],["110","Sevnica"],["111","Se\u017eana"],["112","Slovenj Gradec"],["113","Slovenska Bistrica"],["114","Slovenske Konjice"],["179","Sodra\u017eica"],["180","Sol\u010dava"],["202","Sredi\u0161\u010de ob Dravi"],["115","Star\u0161e"],["203","Stra\u017ea"],["181","Sveta Ana"],["204","Sveta Trojica v Slovenskih goricah"],["182","Sveti Andra\u017e v Slovenskih goricah"],["116","Sveti Jurij ob \u0160\u010davnici"],["210","Sveti Jurij v Slovenskih goricah"],["205","Sveti Toma\u017e"],["033","\u0160alovci"],["183","\u0160empeter-Vrtojba"],["117","\u0160en\u010dur"],["118","\u0160entilj"],["119","\u0160entjernej"],["120","\u0160entjur"],["211","\u0160entrupert"],["121","\u0160kocjan"],["122","\u0160kofja Loka"],["123","\u0160kofljica"],["124","\u0160marje pri Jel\u0161ah"],["206","\u0160marje\u0161ke Toplice"],["125","\u0160martno ob Paki"],["194","\u0160martno pri Litiji"],["126","\u0160o\u0161tanj"],["127","\u0160tore"],["184","Tabor"],["010","Ti\u0161ina"],["128","Tolmin"],["129","Trbovlje"],["130","Trebnje"],["185","Trnovska Vas"],["186","Trzin"],["131","Tr\u017ei\u010d"],["132","Turni\u0161\u010de"],["133","Velenje"],["187","Velika Polana"],["134","Velike La\u0161\u010de"],["188","Ver\u017eej"],["135","Videm"],["136","Vipava"],["137","Vitanje"],["138","Vodice"],["139","Vojnik"],["189","Vransko"],["140","Vrhnika"],["141","Vuzenica"],["142","Zagorje ob Savi"],["143","Zavr\u010d"],["144","Zre\u010de"],["190","\u017dalec"],["146","\u017delezniki"],["191","\u017detale"],["147","\u017diri"],["192","\u017dirovnica"],["193","\u017du\u017eemberk"]],"South Africa":[["EC","Eastern Cape"],["FS","Free State"],["GP","Gauteng"],["NL","Kwazulu-Natal"],["LP","Limpopo"],["MP","Mpumalanga"],["NC","Northern Cape"],["NW","North-West"],["WC","Western Cape"]],"Spain":[["C","A Coru\u00f1a"],["AB","Albacete"],["A","Alicante"],["AL","Almer\u00eda"],["VI","Araba\/\u00c1lava"],["O","Asturias"],["AV","\u00c1vila"],["BA","Badajoz"],["PM","Baleares"],["B","Barcelona"],["BI","Biscay"],["BU","Burgos"],["CC","C\u00e1ceres"],["CA","C\u00e1diz"],["S","Cantabria"],["CS","Castell\u00f3n"],["CE","Ceuta"],["CR","Ciudad Real"],["CO","C\u00f3rdoba"],["CU","Cuenca"],["SS","Gipuzkoa"],["GI","Girona"],["GR","Granada"],["GU","Guadalajara"],["H","Huelva"],["HU","Huesca"],["J","Ja\u00e9n"],["LO","La Rioja"],["GC","Las Palmas"],["LE","Le\u00f3n"],["L","Lleida"],["LU","Lugo"],["M","Madrid"],["MA","M\u00e1laga"],["ML","Melilla"],["MU","Murcia"],["NA","Navarra"],["OR","Ourense"],["P","Palencia"],["PO","Pontevedra"],["SA","Salamanca"],["TF","Santa Cruz de Tenerife"],["SG","Segovia"],["SE","Sevilla"],["SO","Soria"],["T","Tarragona"],["TE","Teruel"],["TO","Toledo"],["V","Valencia"],["VA","Valladolid"],["ZA","Zamora"],["Z","Zaragoza"]],"Sweden":[["K","Blekinge"],["W","Dalarna"],["I","Gotland"],["X","G\u00e4vleborg"],["N","Halland"],["Z","J\u00e4mtland"],["F","J\u00f6nk\u00f6ping"],["H","Kalmar"],["G","Kronoberg"],["BD","Norrbotten"],["M","Sk\u00e5ne"],["AB","Stockholm"],["D","S\u00f6dermanland"],["C","Uppsala"],["S","V\u00e4rmland"],["AC","V\u00e4sterbotten"],["Y","V\u00e4sternorrland"],["U","V\u00e4stmanland"],["O","V\u00e4stra G\u00f6taland"],["T","\u00d6rebro"],["E","\u00d6sterg\u00f6tland"]],"Switzerland":[["AG","Aargau"],["AR","Appenzell Ausserrhoden"],["AI","Appenzell Innerrhoden"],["BL","Basel-Landschaft"],["BS","Basel-Stadt"],["BE","Bern"],["FR","Fribourg"],["GE","Geneva"],["GL","Glarus"],["GR","Graub\u00fcnden"],["JU","Jura"],["LU","Luzern"],["NE","Neuch\u00e2tel"],["NW","Nidwalden"],["OW","Obwalden"],["SH","Schaffhausen"],["SZ","Schwyz"],["SO","Solothurn"],["SG","St. Gallen"],["TG","Thurgau"],["TI","Ticino"],["UR","Uri"],["VS","Valais"],["VD","Vaud"],["ZG","Zug"],["ZH","Z\u00fcrich"]],"Tanzania, the United Republic of":[["01","Arusha"],["19","Coast"],["02","Dar es Salaam"],["03","Dodoma"],["27","Geita"],["04","Iringa"],["05","Kagera"],["08","Kigoma"],["09","Kilimanjaro"],["12","Lindi"],["28","Katavi"],["26","Manyara"],["13","Mara"],["14","Mbeya"],["16","Morogoro"],["17","Mtwara"],["18","Mwanza"],["29","Njombe"],["06","Pemba North"],["10","Pemba South"],["20","Rukwa"],["21","Ruvuma"],["22","Shinyanga"],["30","Simiyu"],["23","Singida"],["31","Songwe"],["24","Tabora"],["25","Tanga"],["07","Zanzibar North"],["11","Zanzibar South"],["15","Zanzibar West"]],"Thailand":[["37","Amnat Charoen"],["15","Ang Thong"],["14","Ayutthaya"],["10","Bangkok"],["38","Bueng Kan"],["31","Buri Ram"],["24","Chachoengsao"],["18","Chai Nat"],["36","Chaiyaphum"],["22","Chanthaburi"],["50","Chiang Mai"],["57","Chiang Rai"],["20","Chonburi"],["86","Chumphon"],["46","Kalasin"],["62","Kamphaeng Phet"],["71","Kanchanaburi"],["40","Khon Kaen"],["81","Krabi"],["52","Lampang"],["51","Lamphun"],["42","Loei"],["16","Lopburi"],["58","Mae Hong Son"],["44","Maha Sarakham"],["49","Mukdahan"],["26","Nakhon Nayok"],["73","Nakhon Pathom"],["48","Nakhon Phanom"],["30","Nakhon Ratchasima"],["60","Nakhon Sawan"],["80","Nakhon Si Thammarat"],["55","Nan"],["96","Narathiwat"],["39","Nong Bua Lam Phu"],["43","Nong Khai"],["12","Nonthaburi"],["13","Pathum Thani"],["94","Pattani"],["82","Phang Nga"],["93","Phatthalung"],["56","Phayao"],["67","Phetchabun"],["76","Phetchaburi"],["66","Phichit"],["65","Phitsanulok"],["54","Phrae"],["83","Phuket"],["25","Prachin Buri"],["77","Prachuap Khiri Khan"],["85","Ranong"],["70","Ratchaburi"],["21","Rayong"],["45","Roi Et"],["27","Sa Kaeo"],["47","Sakon Nakhon"],["11","Samut Prakan"],["74","Samut Sakhon"],["75","Samut Songkhram"],["19","Saraburi"],["91","Satun"],["17","Sing Buri"],["33","Sisaket"],["90","Songkhla"],["64","Sukhothai"],["72","Suphan Buri"],["84","Surat Thani"],["32","Surin"],["63","Tak"],["92","Trang"],["23","Trat"],["34","Ubon Ratchathani"],["41","Udon Thani"],["61","Uthai Thani"],["53","Uttaradit"],["95","Yala"],["35","Yasothon"]],"T\u00fcrkiye":[["01","Adana"],["02","Ad\u0131yaman"],["03","Afyonkarahisar"],["04","A\u011fr\u0131"],["68","Aksaray"],["05","Amasya"],["06","Ankara"],["07","Antalya"],["75","Ardahan"],["08","Artvin"],["09","Ayd\u0131n"],["10","Bal\u0131kesir"],["74","Bart\u0131n"],["72","Batman"],["69","Bayburt"],["11","Bilecik"],["12","Bing\u00f6l"],["13","Bitlis"],["14","Bolu"],["15","Burdur"],["16","Bursa"],["17","\u00c7anakkale"],["18","\u00c7ank\u0131r\u0131"],["19","\u00c7orum"],["20","Denizli"],["21","Diyarbak\u0131r"],["81","D\u00fczce"],["22","Edirne"],["23","Elaz\u0131\u011f"],["24","Erzincan"],["25","Erzurum"],["26","Eski\u015fehir"],["27","Gaziantep"],["28","Giresun"],["29","G\u00fcm\u00fc\u015fhane"],["30","Hakkari"],["31","Hatay"],["33","Mersin"],["76","I\u011fd\u0131r"],["32","Isparta"],["34","\u0130stanbul"],["35","\u0130zmir"],["36","Kars"],["37","Kastamonu"],["38","Kayseri"],["39","K\u0131rklareli"],["40","K\u0131r\u015fehir"],["41","Kocaeli"],["42","Konya"],["43","K\u00fctahya"],["44","Malatya"],["45","Manisa"],["46","Kahramanmara\u015f"],["78","Karab\u00fck"],["70","Karaman"],["79","Kilis"],["71","K\u0131r\u0131kkale"],["47","Mardin"],["48","Mu\u011fla"],["49","Mu\u015f"],["50","Nev\u015fehir"],["51","Ni\u011fde"],["52","Ordu"],["80","Osmaniye"],["53","Rize"],["54","Sakarya"],["55","Samsun"],["63","\u015eanl\u0131urfa"],["56","Siirt"],["57","Sinop"],["58","Sivas"],["59","Tekirda\u011f"],["60","Tokat"],["61","Trabzon"],["62","Tunceli"],["64","U\u015fak"],["65","Van"],["73","\u015e\u0131rnak"],["77","Yalova"],["66","Yozgat"],["67","Zonguldak"]],"Uganda":[["314","Abim"],["301","Adjumani"],["322","Agago"],["323","Alebtong"],["315","Amolatar"],["324","Amudat"],["216","Amuria"],["316","Amuru"],["302","Apac"],["303","Arua"],["217","Budaka"],["218","Bududa"],["201","Bugiri"],["235","Bugweri"],["420","Buhweju"],["117","Buikwe"],["219","Bukedea"],["118","Bukomansibi"],["220","Bukwa"],["225","Bulambuli"],["416","Buliisa"],["401","Bundibugyo"],["430","Bunyangabu"],["402","Bushenyi"],["202","Busia"],["221","Butaleja"],["119","Butambala"],["233","Butebo"],["120","Buvuma"],["226","Buyende"],["317","Dokolo"],["121","Gomba"],["304","Gulu"],["403","Hoima"],["417","Ibanda"],["203","Iganga"],["418","Isingiro"],["204","Jinja"],["318","Kaabong"],["404","Kabale"],["405","Kabarole"],["213","Kaberamaido"],["427","Kagadi"],["428","Kakumiro"],["101","Kalangala"],["222","Kaliro"],["122","Kalungu"],["102","Kampala"],["205","Kamuli"],["413","Kamwenge"],["414","Kanungu"],["206","Kapchorwa"],["236","Kapelebyong"],["126","Kasanda"],["406","Kasese"],["207","Katakwi"],["112","Kayunga"],["407","Kibaale"],["103","Kiboga"],["227","Kibuku"],["432","Kikuube"],["419","Kiruhura"],["421","Kiryandongo"],["408","Kisoro"],["305","Kitgum"],["319","Koboko"],["325","Kole"],["306","Kotido"],["208","Kumi"],["333","Kwania"],["228","Kween"],["123","Kyankwanzi"],["422","Kyegegwa"],["415","Kyenjojo"],["125","Kyotera"],["326","Lamwo"],["307","Lira"],["229","Luuka"],["104","Luwero"],["124","Lwengo"],["114","Lyantonde"],["223","Manafwa"],["320","Maracha"],["105","Masaka"],["409","Masindi"],["214","Mayuge"],["209","Mbale"],["410","Mbarara"],["423","Mitooma"],["115","Mityana"],["308","Moroto"],["309","Moyo"],["106","Mpigi"],["107","Mubende"],["108","Mukono"],["334","Nabilatuk"],["311","Nakapiripirit"],["116","Nakaseke"],["109","Nakasongola"],["230","Namayingo"],["234","Namisindwa"],["224","Namutumba"],["327","Napak"],["310","Nebbi"],["231","Ngora"],["424","Ntoroko"],["411","Ntungamo"],["328","Nwoya"],["331","Omoro"],["329","Otuke"],["321","Oyam"],["312","Pader"],["332","Pakwach"],["210","Pallisa"],["110","Rakai"],["429","Rubanda"],["425","Rubirizi"],["431","Rukiga"],["412","Rukungiri"],["111","Sembabule"],["232","Serere"],["426","Sheema"],["215","Sironko"],["211","Soroti"],["212","Tororo"],["113","Wakiso"],["313","Yumbe"],["330","Zombo"]],"Ukraine":[["30","Kyiv",10],["71","Cherkasy Oblast"],["74","Chernihiv Oblast"],["77","Chernivtsi Oblast"],["12","Dnipropetrovsk Oblast"],["14","Donetsk Oblast"],["26","Ivano-Frankivsk Oblast"],["63","Kharkiv Oblast"],["65","Kherson Oblast"],["68","Khmelnytskyi Oblast"],["35","Kirovohrad Oblast"],["32","Kyiv Oblast"],["09","Luhansk Oblast"],["46","Lviv Oblast"],["48","Mykolaiv Oblast"],["51","Odessa Oblast"],["53","Poltava Oblast"],["56","Rivne Oblast"],["59","Sumy Oblast"],["61","Ternopil Oblast"],["05","Vinnytsia Oblast"],["07","Volyn Oblast"],["21","Zakarpattia Oblast"],["23","Zaporizhzhia Oblast"],["18","Zhytomyr Oblast"],["40","Sevastopol",-1],["43","Crimea",-2]],"United Kingdom":{"groups":[{"name":"England","states":[["BDG","Barking and Dagenham"],["BNE","Barnet"],["BNS","Barnsley"],["BAS","Bath and North East Somerset"],["BDF","Bedford"],["BEX","Bexley"],["BIR","Birmingham"],["BBD","Blackburn with Darwen"],["BPL","Blackpool"],["BOL","Bolton"],["BCP","Bournemouth, Christchurch and Poole"],["BRC","Bracknell Forest"],["BRD","Bradford"],["BEN","Brent"],["BNH","Brighton and Hove"],["BST","Bristol, City of"],["BRY","Bromley"],["BKM","Buckinghamshire"],["BUR","Bury"],["CLD","Calderdale"],["CAM","Cambridgeshire"],["CMD","Camden"],["CBF","Central Bedfordshire"],["CHE","Cheshire East"],["CHW","Cheshire West and Chester"],["CON","Cornwall"],["COV","Coventry"],["CRY","Croydon"],["CMA","Cumbria"],["DAL","Darlington"],["DER","Derby"],["DBY","Derbyshire"],["DEV","Devon"],["DNC","Doncaster"],["DOR","Dorset"],["DUD","Dudley"],["DUR","Durham, County"],["EAL","Ealing"],["ERY","East Riding of Yorkshire"],["ESX","East Sussex"],["ENF","Enfield"],["ESS","Essex"],["GAT","Gateshead"],["GLS","Gloucestershire"],["GRE","Greenwich"],["HCK","Hackney"],["HAL","Halton"],["HMF","Hammersmith and Fulham"],["HAM","Hampshire"],["HRY","Haringey"],["HRW","Harrow"],["HPL","Hartlepool"],["HAV","Havering"],["HEF","Herefordshire"],["HRT","Hertfordshire"],["HIL","Hillingdon"],["HNS","Hounslow"],["IOW","Isle of Wight"],["IOS","Isles of Scilly"],["ISL","Islington"],["KEC","Kensington and Chelsea"],["KEN","Kent"],["KHL","Kingston upon Hull"],["KTT","Kingston upon Thames"],["KIR","Kirklees"],["KWL","Knowsley"],["LBH","Lambeth"],["LAN","Lancashire"],["LDS","Leeds"],["LCE","Leicester"],["LEC","Leicestershire"],["LEW","Lewisham"],["LIN","Lincolnshire"],["LIV","Liverpool"],["LND","London, City of"],["LUT","Luton"],["MAN","Manchester"],["MDW","Medway"],["MRT","Merton"],["MDB","Middlesbrough"],["MIK","Milton Keynes"],["NET","Newcastle upon Tyne"],["NWM","Newham"],["NFK","Norfolk"],["NEL","North East Lincolnshire"],["NLN","North Lincolnshire"],["NSM","North Somerset"],["NTY","North Tyneside"],["NYK","North Yorkshire"],["NTH","Northamptonshire"],["NBL","Northumberland"],["NGM","Nottingham"],["NTT","Nottinghamshire"],["OLD","Oldham"],["OXF","Oxfordshire"],["PTE","Peterborough"],["PLY","Plymouth"],["POR","Portsmouth"],["RDG","Reading"],["RDB","Redbridge"],["RCC","Redcar and Cleveland"],["RIC","Richmond upon Thames"],["RCH","Rochdale"],["ROT","Rotherham"],["RUT","Rutland"],["SLF","Salford"],["SAW","Sandwell"],["SFT","Sefton"],["SHF","Sheffield"],["SHR","Shropshire"],["SLG","Slough"],["SOL","Solihull"],["SOM","Somerset"],["SGC","South Gloucestershire"],["STY","South Tyneside"],["STH","Southampton"],["SOS","Southend-on-Sea"],["SWK","Southwark"],["SHN","St. Helens"],["STS","Staffordshire"],["SKP","Stockport"],["STT","Stockton-on-Tees"],["STE","Stoke-on-Trent"],["SFK","Suffolk"],["SND","Sunderland"],["SRY","Surrey"],["STN","Sutton"],["SWD","Swindon"],["TAM","Tameside"],["TFW","Telford and Wrekin"],["THR","Thurrock"],["TOB","Torbay"],["TWH","Tower Hamlets"],["TRF","Trafford"],["WKF","Wakefield"],["WLL","Walsall"],["WFT","Waltham Forest"],["WND","Wandsworth"],["WRT","Warrington"],["WAR","Warwickshire"],["WBK","West Berkshire"],["WSX","West Sussex"],["WSM","Westminster"],["WGN","Wigan"],["WIL","Wiltshire"],["WNM","Windsor and Maidenhead"],["WRL","Wirral"],["WOK","Wokingham"],["WLV","Wolverhampton"],["WOR","Worcestershire"],["YOR","York"]]},{"name":"Scotland","states":[["ABE","Aberdeen City"],["ABD","Aberdeenshire"],["ANS","Angus"],["AGB","Argyll and Bute"],["CLK","Clackmannanshire"],["DGY","Dumfries and Galloway"],["DND","Dundee City"],["EAY","East Ayrshire"],["EDU","East Dunbartonshire"],["ELN","East Lothian"],["ERW","East Renfrewshire"],["EDH","Edinburgh, City of"],["ELS","Eilean Siar"],["FAL","Falkirk"],["FIF","Fife"],["GLG","Glasgow City"],["HLD","Highland"],["IVC","Inverclyde"],["MLN","Midlothian"],["MRY","Moray"],["NAY","North Ayrshire"],["NLK","North Lanarkshire"],["ORK","Orkney Islands"],["PKN","Perth and Kinross"],["RFW","Renfrewshire"],["SCB","Scottish Borders"],["ZET","Shetland Islands"],["SAY","South Ayrshire"],["SLK","South Lanarkshire"],["STG","Stirling"],["WDU","West Dunbartonshire"],["WLN","West Lothian"]]},{"name":"Wales","states":[["BGW","Blaenau Gwent"],["BGE","Bridgend"],["CAY","Caerphilly"],["CRF","Cardiff"],["CMN","Carmarthenshire"],["CGN","Ceredigion"],["CWY","Conwy"],["DEN","Denbighshire"],["FLN","Flintshire"],["GWN","Gwynedd"],["AGY","Isle of Anglesey"],["MTY","Merthyr Tydfil"],["MON","Monmouthshire"],["NTL","Neath Port Talbot"],["NWP","Newport"],["PEM","Pembrokeshire"],["POW","Powys"],["RCT","Rhondda Cynon Taff"],["SWA","Swansea"],["TOF","Torfaen"],["VGL","Vale of Glamorgan"],["WRX","Wrexham"]]},{"name":"Northern Ireland","states":[["ANN","Antrim and Newtownabbey"],["AND","Ards and North Down"],["ABC","Armagh City, Banbridge and Craigavon"],["BFS","Belfast City"],["CCG","Causeway Coast and Glens"],["DRS","Derry and Strabane"],["FMO","Fermanagh and Omagh"],["LBC","Lisburn and Castlereagh"],["MEA","Mid and East Antrim"],["MUL","Mid-Ulster"],["NMD","Newry, Mourne and Down"]]}]},"United States":[["AL","Alabama"],["AK","Alaska"],["AZ","Arizona"],["AR","Arkansas"],["CA","California"],["CO","Colorado"],["CT","Connecticut"],["DE","Delaware"],["DC","District Of Columbia"],["FL","Florida"],["GA","Georgia"],["HI","Hawaii"],["ID","Idaho"],["IL","Illinois"],["IN","Indiana"],["IA","Iowa"],["KS","Kansas"],["KY","Kentucky"],["LA","Louisiana"],["ME","Maine"],["MD","Maryland"],["MA","Massachusetts"],["MI","Michigan"],["MN","Minnesota"],["MS","Mississippi"],["MO","Missouri"],["MT","Montana"],["NE","Nebraska"],["NV","Nevada"],["NH","New Hampshire"],["NJ","New Jersey"],["NM","New Mexico"],["NY","New York"],["NC","North Carolina"],["ND","North Dakota"],["OH","Ohio"],["OK","Oklahoma"],["OR","Oregon"],["PA","Pennsylvania"],["RI","Rhode Island"],["SC","South Carolina"],["SD","South Dakota"],["TN","Tennessee"],["TX","Texas"],["UT","Utah"],["VT","Vermont"],["VA","Virginia"],["WA","Washington"],["WV","West Virginia"],["WI","Wisconsin"],["WY","Wyoming"],["AA","Armed Forces Americas",-10],["AE","Armed Forces Europe",-11],["AP","Armed Forces Pacific",-12]],"Uruguay":[["AR","Artigas"],["CA","Canelones"],["CL","Cerro Largo"],["CO","Colonia"],["DU","Durazno"],["FS","Flores"],["FD","Florida"],["LA","Lavalleja"],["MA","Maldonado"],["MO","Montevideo"],["PA","Paysand\u00fa"],["RN","R\u00edo Negro"],["RV","Rivera"],["RO","Rocha"],["SA","Salto"],["SJ","San Jos\u00e9"],["SO","Soriano"],["TA","Tacuaremb\u00f3"],["TT","Treinta y Tres"]],"US Minor Outlying Islands":[["81","Baker Island"],["84","Howland Island"],["86","Jarvis Island"],["67","Johnston Atoll"],["89","Kingman Reef"],["71","Midway Islands"],["76","Navassa Island"],["95","Palmyra Atoll"],["79","Wake Island"]],"Venezuela":[["A","Capital",1],["B","Anzo\u00e1tegui"],["C","Apure"],["D","Aragua"],["E","Barinas"],["F","Bol\u00edvar"],["G","Carabobo"],["H","Cojedes"],["I","Falc\u00f3n"],["J","Gu\u00e1rico"],["K","Lara"],["L","M\u00e9rida"],["M","Miranda"],["N","Monagas"],["O","Nueva Esparta"],["P","Portuguesa"],["R","Sucre"],["S","T\u00e1chira"],["T","Trujillo"],["U","Yaracuy"],["V","Zulia"],["W","Federal Dependencies",-10],["X","La Guaira (Vargas)"],["Y","Delta Amacuro"],["Z","Amazonas"]],"Zambia":[["02","Central"],["08","Copperbelt"],["03","Eastern"],["04","Luapula"],["09","Lusaka"],["10","Muchinga"],["05","Northern"],["06","North-Western"],["07","Southern"],["01","Western"]],"Zimbabwe":[["BU","Bulawayo"],["HA","Harare"],["MA","Manicaland"],["MC","Mashonaland Central"],["ME","Mashonaland East"],["MW","Mashonaland West"],["MV","Masvingo"],["MN","Matabeleland North"],["MS","Matabeleland South"],["MI","Midlands"]]},"labels":{"default":"Enter State \/ Province \/ Region","countries":{"Select County":["Albania","Croatia","Estonia","Hungary","Ireland","Kenya","Liberia","Lithuania","Norway","Romania","Sweden"],"Select Province":["Algeria","Angola","Argentina","Belgium","Canada","China","Costa Rica","Ecuador","Indonesia","Iran","Italy","Korea, Republic of","Lao People's Democratic Republic","Mozambique","Netherlands","Pakistan","Panama","Paraguay","Philippines","Poland","South Africa","Spain","Thailand","T\u00fcrkiye","Zambia","Zimbabwe"],"Select Region":["Armenia","Belarus","Chile","Denmark","Finland","Georgia","Ghana","Greece","Hong Kong","Iceland","Namibia","New Zealand","Peru","Russian Federation","Senegal","Slovakia","Tanzania, the United Republic of","Ukraine"],"Select State":["Australia","Austria","Brazil","Germany","India","Japan","Malaysia","Mexico","Nigeria","United States","Venezuela"],"Select District":["Azerbaijan","Bangladesh","Bulgaria","Cyprus","Czechia","Moldova","Portugal","Serbia","Uganda"],"Select Department":["Benin","Bolivia","Colombia","El Salvador","France","Guatemala","Honduras","Nicaragua","Uruguay"],"Select Entity \/ District":["Bosnia and Herzegovina"],"Select Province \/ Region":["Dominican Republic"],"Select Governorate":["Egypt"],"Select Parish":["Jamaica","Saint Kitts and Nevis"],"Select Municipality":["Latvia","Montenegro","North Macedonia","Slovenia"],"Select Canton":["Luxembourg","Switzerland"],"Select Zone":["Nepal"],"Select County \/ District":["United Kingdom"],"Select Island":["US Minor Outlying Islands"]}},"fields":{"input_44_42_4":{"state_as_code":false,"placeholder":""}}};	
	/*! GF Address Enhanced Smart States script */
!function(r,e,d){let l,i,c,o;const u={};function n(e){return r.getElementById(e)}function a(e){e=e.querySelectorAll(".gf-address-enhanced-smart-states");if(0<e.length){if(!l){l=wp.template("gf-address-enhanced-state-list"),i=wp.template("gf-address-enhanced-state-list-grouped"),c=wp.template("gf-address-enhanced-state-any"),o=new Intl.Collator(r.documentElement.getAttribute("lang")||"en");const t=d.labels;Object.keys(t.countries).forEach(r=>{var o=t.countries[r];for(let e=0,t=o.length;e<t;e++)u[o[e]]=r})}e.forEach(function(e){m(n(e.id.replace(/field_([0-9]+)_([0-9]+)/,"input_$1_$2_6")),!1)})}}function f(e,t){e.insertAdjacentHTML("afterend",t),e.parentElement.removeChild(e)}function p(e,t){var r={field_name:e.name,field_id:e.id,tabindex:e.tabIndex,state:e.value},e=d.fields[r.field_id];if(e&&(r.autocomplete=e.autocomplete,r.required=e.required,r.describedby=e.describedby),r.field_id in d.fields&&(r.placeholder=d.fields[r.field_id].placeholder),void 0!==t)if(void 0!==t.groups){r.groups=t.groups;for(let e=0,t=r.groups.length;e<t;e++)r.groups[e].states.sort(s)}else r.states=t,r.states.sort(s);return r}function s(e,t){return 2<e.length&&2<t.length?e[2]===t[2]?o.compare(e[1],t[1]):t[2]-e[2]:2<t.length?t[2]:2<e.length?-e[2]:o.compare(e[1],t[1])}function m(e,t){var r,o,n,a=e.closest(".ginput_container_address"),s=a.querySelector(".address_state select,.address_state input");s&&(a=a.querySelector(".address_state label"),e=e.value,(r=d.fields[s.id])&&(r.autocomplete=s.getAttribute("autocomplete"),r.required=s.getAttribute("aria-required"),r.describedby=s.getAttribute("aria-describedby")),d.states[e]?(r=s,o=d.states[e],(n=t)&&(r.value=""),n=p(r,o),f(r,(n.groups?i:l)(n))):(o=t,"INPUT"!==(r=s).tagName&&(o&&(r.value=""),f(r,c(p(r))))),a.textContent=(n=e)in u?u[n]:d.labels.default)}NodeList.prototype.forEach||(NodeList.prototype.forEach=Array.prototype.forEach),Element.prototype.matches||(Element.prototype.matches=Element.prototype.msMatchesSelector||Element.prototype.webkitMatchesSelector),Element.prototype.closest||(Element.prototype.closest=function(e){let t=this;do{if(t.matches(e))return t}while(t=t.parentElement);return null}),e(r.body).on("change",".gf-address-enhanced-smart-states .address_country select",function(){m(this,!0)}),e(r).on("gform_post_render",function(e,t){t=n("gform_"+t);t&&a(t)}),d.wc_gf_product&&(e=r.querySelector("form.cart"))&&a(e),"gform"in window&&gform.addFilter("gform_is_value_match",function(e,t,r){var o;return r&&r.fieldId&&(t="input_"+t+"_"+r.fieldId.replace(".","_"))in d.fields&&!d.fields[t].state_as_code&&"SELECT"===(t=n(t)).tagName&&(t=-1===(o=t.selectedIndex)?"":t.options[o].textContent,e=gf_matches_operation(t,r.value,r.operator)),e})}(document,jQuery,gf_address_enhanced_smart_states);
	
	/*! This file is auto-generated */
window.wp = window.wp || {},
function(s) {
    var t = "undefined" == typeof _wpUtilSettings ? {} : _wpUtilSettings;
    wp.template = _.memoize(function(e) {
        var n, a = {
            evaluate: /<#([\s\S]+?)#>/g,
            interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
            escape: /\{\{([^\}]+?)\}\}(?!\})/g,
            variable: "data"
        };
        return function(t) {
            if (document.getElementById("tmpl-" + e))
                return (n = n || _.template(s("#tmpl-" + e).html(), a))(t);
            throw new Error("Template not found: #tmpl-" + e)
        }
    }) 
     
}(jQuery);
	
	
</script>

<?php } ?>

