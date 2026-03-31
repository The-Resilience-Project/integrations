<?php

require_once dirname(__FILE__).'/base.php';

require_once dirname(__FILE__).'/traits/enquiry.php';
require_once dirname(__FILE__).'/traits/confirmation.php';
require_once dirname(__FILE__).'/traits/lead.php';
require_once dirname(__FILE__).'/traits/registration.php';
// require_once dirname(__FILE__)."/traits/order_resources.php";
require_once dirname(__FILE__).'/traits/order_resources_26.php';
require_once dirname(__FILE__).'/traits/accept_dates.php';
require_once dirname(__FILE__).'/traits/assess.php';

class SchoolVTController extends VTController
{
    use Enquiry;
    use Confirmation;
    use Lead;
    use Registration;
    use OrderResources;
    use AcceptDates;
    use Assess;

    protected $organisation_type = 'School';
    protected $deal_name = '2026 School Partnership Program';
    protected $deal_type = 'School';
    protected $deal_org_type = 'School - New';
    protected $enquiry_type = 'School';
    protected $quote_name = '2026 School Partnership Program';
    protected $quote_type = 'School - New';
    protected $quote_program = 'School';
    protected $quote_stage = 'Delivered';
    protected $invoice_name = '2026 School Partnership Program';
    protected $seip_name = '2026 SEIP';

    protected $previous_deal_name = '2025 School Partnership Program';
    protected $previous_quote_name = '2025 School Partnership Program';
    protected $previous_invoice_name = '2025 School Partnership Program';



    protected $inspire = 'Inspire 1';
    protected $engage = ['Journals'];
    protected $extend = [];
    protected $billing_note = '';



    protected function capture_customer_info_in_vt($customer_data)
    {
        $this->deactivate_contacts($customer_data['contact_email']);

        $request_body = $this->format_customer_info_payload($customer_data);

        $response;

        if ($this->isset_data('school_name_other_selected')) {
            $request_body['organisationName'] = $this->data['school_name_other'];
            $response = $this->post_request_to_vt('captureCustomerInfo', $request_body);
        } else {
            $request_body['organisationAccountNo'] = $this->data['school_account_no'];
            $response = $this->post_request_to_vt('captureCustomerInfoWithAccountNo', $request_body);
        }

        $response_data = $response->result[0];
        return $response_data;
    }


    protected function get_enquiry_assignee()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        if (is_null($org_assignee)) {
            return self::LAURA;
        }
        if ($org_assignee != self::MADDIE) {
            return $org_assignee;
        }

        $state = $this->data['state'];
        if ($state == 'NSW' or $state == 'QLD') {
            return self::BRENDAN;
        }
        return self::LAURA;
    }

    protected function get_contact_assignee()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        if ($org_assignee != self::MADDIE) {
            return $org_assignee;
        }

        $state = $this->data['state'];
        if ($state == 'NSW' or $state == 'QLD') {
            return self::BRENDAN;
        }
        return self::LAURA;

    }

    protected function get_org_assignee()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        if ($org_assignee != self::MADDIE) {
            return $org_assignee;
        }

        $state = $this->data['state'];
        if ($state == 'NSW' or $state == 'QLD') {
            return self::BRENDAN;
        }
        return self::LAURA;
    }

    protected function is_new_school()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        $not_spms = [self::MADDIE, self::LAURA, self::VICTOR, self::HELENOR, self::BRENDAN];
        return in_array($org_assignee, $not_spms);
    }

    protected function get_registration_reply_to()
    {

        $state = $this->data['state'];
        if ($state == 'NSW' or $state == 'QLD') {
            return self::BRENDAN;
        }
        return self::LAURA;
    }



    public function submit_enquiry()
    {
        try {
            $deal_close_date = date('d/m/Y', strtotime('+2 Weeks'));

            $this->capture_customer_info();
            if ($this->is_new_school()) {
                $this->update_or_create_deal('New', $deal_close_date);
            }
            $this->create_enquiry();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function get_info_for_confirmation_form($account_no = null, $accountname = null)
    {
        $deal_response;
        $org_response;
        $request_body = [
            'dealName' => $this->deal_name,
        ];
        if (!empty($account_no)) {
            $request_body['organisationAccountNo'] = $account_no;
            $org_response = $this->post_request_to_vt('getOrgWithAccountNo', $request_body, true);
            $deal_response = $this->post_request_to_vt('getDealDetailsFromAccountNo', $request_body, true);
        } else {
            $request_body['organisationName'] = $accountname;
            $org_response = $this->post_request_to_vt('getOrgWithName', $request_body, true);
            $deal_response = $this->post_request_to_vt('getDealDetails', $request_body, true);
        }

        $deal_status = '';
        $deal_org_type = '';
        $engage = '';
        if (!empty($deal_response) and !empty($deal_response->result) and !empty($deal_response->result[0])) {
            $deal_details = $deal_response->result[0];
            $deal_status = $deal_details->sales_stage;
            $deal_org_type = $deal_details->cf_potentials_orgtype;
            $engage = $deal_details->cf_potentials_curriculum;

        }

        $free_travel = '';
        $priority = '';
        $f2f = '';
        $funded_years = '';
        $org_state = '';
        $org_leading_trp = '';
        if (!empty($org_response) and !empty($org_response->result) and !empty($org_response->result[0])) {
            $org_details = $org_response->result[0];
            $free_travel = $org_details->cf_accounts_freetravel;
            $priority = $org_details->cf_accounts_priority;
            $f2f = $org_details->cf_accounts_extendoffering === 'F2F';
            $funded_years = $org_details->cf_accounts_fundedyears;
            $org_state = $org_details->cf_accounts_statenew;
            $org_leading_trp = $org_details->cf_accounts_leadingtrp;
        }

        return [
            'deal_status' => $deal_status,
            'deal_org_type' => $deal_org_type,
            'engage' => $engage,
            'free_travel' => $free_travel,
            'priority' => $priority,
            'f2f' => $f2f,
            'funded_years' => $funded_years,
            'org_state' => $org_state,
            'leading_trp' => $org_leading_trp,
        ];

    }

    public function get_info_for_curric_ordering_form($account_no, $for_2026)
    {
        $deal_response;
        $invoice_response;
        $org_response;
        $request_body = null;

        if (!$for_2026) {
            $request_body = [
                'dealName' => $this->previous_deal_name,
                'invoiceName' => $this->previous_invoice_name,
            ];
        } else {
            $request_body = [
                'dealName' => $this->deal_name,
                'invoiceName' => $this->invoice_name,
            ];
        }


        $request_body['organisationAccountNo'] = $account_no;
        $deal_response = $this->post_request_to_vt('getDealDetailsFromAccountNo', $request_body, true);
        $invoice_response = $this->post_request_to_vt('getInvoicesFromAccountNo', $request_body, true);
        $org_response = $this->post_request_to_vt('getOrgWithAccountNo', $request_body, true);



        $engage = '';
        $deal_type = '';
        if (!empty($deal_response) and !empty($deal_response->result) and !empty($deal_response->result[0])) {
            $deal_details = $deal_response->result[0];
            $engage = $deal_details->cf_potentials_curriculum;
            $deal_type = $deal_details->cf_potentials_orgtype;

        }


        $free_shipping = false;
        if (!empty($invoice_response) and !empty($invoice_response->result)) {
            $invoices = $invoice_response->result;
            $target_datetime = $for_2026 ? '2025-11-29 00:00' : '2024-11-08 12:59';
            if (count($invoices) == 1 and strtotime($invoices[0]->createdtime) < strtotime($target_datetime)) {
                $free_shipping = true;
            }
        }


        $funded_years = '';
        if (!empty($org_response) and !empty($org_response->result) and !empty($org_response->result[0])) {
            $org_details = $org_response->result[0];
            $funded_years = $org_details->cf_accounts_fundedyears;
        }



        return [
            'engage' => $engage,
            'free_shipping' => $free_shipping,
            'funded_years' => $funded_years,
            'deal_type' => $deal_type,
        ];
    }


    public function get_info_for_ltrp_form($org_id)
    {
        log_debug('get_info_for_ltrp_form() called', ['org_id' => $org_id]);

        $request_body = [
            'organisationAccountNo' => $org_id,
        ];

        log_debug('Fetching organization from Vtiger', ['org_id' => $org_id]);
        $org_response = $this->post_request_to_vt('getOrgWithAccountNo', $request_body, true);

        log_debug('Organization response received', [
            'has_response' => !empty($org_response),
            'has_result' => !empty($org_response->result ?? null),
            'result_count' => count($org_response->result ?? []),
        ]);

        $org_found = !empty($org_response) && !empty($org_response->result) && !empty($org_response->result[0]);

        if (!$org_found) {
            log_warning('Organization not found in Vtiger', [
                'org_id' => $org_id,
                'response' => $org_response,
            ]);
            return ['error' => true];
        }

        $org_details = $org_response->result[0];

        log_info('Organization found', [
            'org_id' => $org_id,
            'vtiger_id' => $org_details->id,
            'account_name' => $org_details->accountname,
        ]);

        $seip_request_body = [
            'organisationId' => $org_details->id,
            'seipName' => $this->seip_name,
        ];

        log_debug('Creating or updating SEIP record', [
            'org_vtiger_id' => $org_details->id,
            'seip_name' => $this->seip_name,
        ]);

        $seip_response = $this->post_request_to_vt('createOrUpdateSEIP', $seip_request_body);

        log_debug('SEIP response received', [
            'has_response' => !empty($seip_response),
            'has_result' => !empty($seip_response->result ?? null),
            'result_count' => count($seip_response->result ?? []),
        ]);

        if (empty($seip_response) || empty($seip_response->result) || empty($seip_response->result[0])) {
            log_error('Failed to create or retrieve SEIP record', [
                'org_id' => $org_id,
                'seip_response' => $seip_response,
            ]);
            return ['error' => true];
        }

        $seip_details = $seip_response->result[0];

        log_info('SEIP details retrieved successfully', [
            'seip_id' => $seip_details->id ?? 'unknown',
            'ltrp_watched' => $seip_details->fld_leadingtrpwatched ?? 'empty',
            'ca_completed' => $seip_details->fld_cacompleted ?? 'empty',
            'participants' => $seip_details->cf_vtcmseip_numberofparticipants ?? 'empty',
        ]);

        return [
            'ltrp' => $seip_details->fld_leadingtrpwatched,
            'ca' => $seip_details->fld_cacompleted,
            'name' => $org_details->accountname,
            'id' => $org_details->id,
            'participants' => $seip_details->cf_vtcmseip_numberofparticipants,
            'error' => false,
        ];
    }

    protected function get_line_items()
    {
        $engage_code = 'SER12';

        $this->inspire = 'Inspire 1';


        $inspire_code = 'SER157';
        $using_mhf = $this->isset_data('mental_health_funding') ? $this->data['mental_health_funding'] === 'Yes' : false;
        $is_small_school = $this->isset_data('num_of_students') ? $this->data['num_of_students'] <= 200 : false;

        if ((!$using_mhf) and $is_small_school) {
            if ($this->data['num_of_students'] > 100) {
                // 101 - 200 students
                $inspire_code = 'SER158';
            } else {
                // 0 - 100 students
                $inspire_code = 'SER159';
            }
        }

        $items = [
            [
                'qty' => 1,
                'code' => $inspire_code,
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ],
            [
                'qty' => $this->data['participating_num_of_students'],
                'code' => $engage_code,
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ],
        ];


        $services = $this->get_services(array_column($items, 'code'));
        $line_items = [];

        foreach ($items as $item) {
            $code = $item['code'];
            $service = $services[array_search($code, array_column($services, 'service_no'))];

            $line_item = [
                'productid' => $service->id,
                'quantity' => $item['qty'],
                'listprice' => $service->unit_price,
                'tax5' => '10',
                'cf_quotes_xerocode' => $service->cf_services_xerocode,
                'duration' => $item['duration'],
                'section_name' => $item['section_name'],
                'section_no' => $item['section_no'],
            ];

            array_push($line_items, $line_item);
        }
        return $line_items;
    }

    public function submit_event_registration()
    {
        try {
            $event_id = $this->data['event_id'];
            if (!str_contains($this->data['event_id'], '18x')) {
                $event_id = '18x' . $this->data['event_id'];
            }
            $event = $this->get_event_details($event_id);
            $event_start_date = $event->date_start;
            $event_start_datetime = $event_start_date.' '.$event->time_start;
            $reply_to = null;
            $create_reg = true;


            if ($this->data['source_form'] === 'Info Session Registration') {
                $this->capture_customer_info();
                if ($this->is_new_school()) {
                    $deal_close_date = $this->add_one_day($event_start_date);
                    $this->update_or_create_deal('Considering', $deal_close_date);

                    $first_info_session_date = $this->deal_details['cf_potentials_firstinfosessiondate'];

                    if (empty($first_info_session_date) or strcmp($event_start_datetime, $first_info_session_date) == -1) {
                        $first_info_session_date = $event_start_datetime;
                    }

                    $this->update_deal_with_registration($first_info_session_date, $this->add_one_day($first_info_session_date));

                    $reply_to = $this->get_registration_reply_to();
                } else {
                    $this->data['enquiry'] = 'Request for live Info Session';
                    $create_reg = false;
                }
            }
            if ($this->data['source_form'] === 'Info Session Recording') {
                $this->capture_customer_info();
                if ($this->is_new_school()) {
                    $deal_close_date = date('d/m/Y', strtotime('+4 Weeks'));

                    $this->update_or_create_deal('Considering', $deal_close_date);
                    $this->update_deal_with_registration(null, $deal_close_date);
                    $reply_to = $this->get_registration_reply_to();
                } else {
                    $this->data['enquiry'] = 'Request for Info Session Recording';
                    $create_reg = false;
                }
            }
            if ($this->data['source_form'] === 'Leading TRP Registration') {
                $this->capture_customer_info();
                $request_body = [
                    'organisationId' => $this->organisation_id,
                    'leadingTrp' => $event_start_datetime,
                ];

                $this->post_request_to_vt('updateOrganisation', $request_body);
            }
            if ($this->data['source_form'] === 'Event Confirmation') {
                if ($this->isset_data('contact_id')) {
                    // ambassador
                    $this->get_contact_details($this->data['contact_id']);
                } else {
                    // teacher/parent
                    $this->capture_other_contact_info();
                    $this->data['attendance_type'] = 'Attending Live';
                }
                $request_body = [
                    'organisationId' => $this->organisation_id,
                    'eventId' => $event->event_no,
                    'status' => 'Date Confirmed',
                    'name' => $this->previous_deal_name,
                ];
                $this->short_event_name = $this->data['event_name_display'] . ' on ' . $event->cf_events_shorteventname;

                $this->post_request_to_vt('createOrUpdateInvitation', $request_body);
            }

            if ($create_reg) {
                $this->register_contact_for_event($event, $reply_to);
            } else {
                $this->create_enquiry();
            }


            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function get_quote_stage()
    {
        return 'Delivered';
    }

}

class ExistingSchoolVTController extends SchoolVTController
{
    protected $deal_org_type = 'School - Existing';
    protected $quote_type = 'School - Existing';

    protected const extend_payload_options = [
        'teacher_wellbeing_program',
        'twb_1_online_only',
        'twb_1_workshop_paid',
        'twb_1_workshop_free',
        'twb_2_online_only',
        'twb_2_workshop_paid',
        'twb_2_workshop_free',
        'twb_3_online_only',
        'twb_3_workshop_paid',
        'twb_3_workshop_free',
        // "authentic_connection_staff",
        'dwf_online_only',
        'dwf_workshop_paid',
        'dwf_workshop_free',
        'brh_online_only',
        'brh_workshop_paid',
        'brh_workshop_free',
        'feeling_ace',
        // "authentic_connection_parents",
        'connected_parenting',
    ];

    protected const extend_code_map = [
        'Teacher Wellbeing Program' => 'SER23',
        'Wellbeing Webinar 1 (Self)' => 'SER26',
        'Wellbeing Workshop 1 (Self)' => 'SER24',
        'Wellbeing Webinar 2 (Others)' => 'SER27',
        'Wellbeing Workshop 2 (Others)' => 'SER25',
        'Wellbeing Webinar 3 (Success)' => 'SER117',
        'Wellbeing Workshop 3 (Success)' => 'SER118',
        // "Hugh Staff AC Webinar" => "SER131",
        // "Martin Staff AC Webinar" => "SER132",
        'Family Digital Wellbeing Webinar' => 'SER120',
        'Family Digital Wellbeing Workshop' => 'SER119',
        'Building Resilience at Home Webinar' => 'SER30',
        'Building Resilience at Home Workshop' => 'SER104',
        'Hugh Parent Webinar' => 'SER160', // change this to new Feeling ACE line item if needed
        'Martin Parent Webinar' => 'SER161', // change this to new Feeling ACE line item if needed
        'Connected Parenting Webinar' => 'SER32',
    ];

    public function get_line_items()
    {
        $journal_qty = 0;
        $planner_qty = 0;
        if ($this->data['school_type'] === 'Primary') {
            $journal_qty = $this->data['participating_num_of_students'];
        } elseif ($this->data['school_type'] === 'Secondary') {
            if ($this->data['secondary_engage'] === 'Journals') {
                $journal_qty = $this->data['participating_num_of_students'];
            } else {
                $planner_qty = $this->data['participating_num_of_students'];
            }
        } else {
            if ($this->data['secondary_engage'] === 'Journals') {
                $journal_qty = $this->data['participating_num_of_students'];
            } else {
                $journal_qty = $this->data['participating_journal_students'];
                $planner_qty = $this->data['participating_planner_students'];
            }

        }

        $items = [];
        $engage = [];

        if ($journal_qty) {
            array_push($items, [
                'qty' => $journal_qty,
                'code' => 'SER12',
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ]);
            array_push($engage, 'Journals');
        }
        if ($planner_qty) {
            array_push($items, [
                'qty' => $planner_qty,
                'code' => 'SER65',
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ]);
            array_push($engage, 'Planners');
        }
        $this->engage = $engage;

        if ($this->data['inspire_added'] === 'Yes') {
            $this->inspire = 'Inspire 2';
            if ($this->organisation_details['cf_accounts_2025inspire'] === 'Inspire 3') {
                $this->inspire = 'Inspire 3';
            } elseif ($this->organisation_details['cf_accounts_2025inspire'] === 'Inspire 4') {
                $this->inspire = 'Inspire 4';
            }
            $inspire_code = 'SER147';
            $using_mhf = $this->isset_data('mental_health_funding') ? $this->data['mental_health_funding'] === 'Yes' : false;
            $num_of_students_provided = false;
            $num_of_students = 0;
            if ($this->isset_data('num_of_students_1')) {
                $num_of_students_provided = true;
                $num_of_students = $this->data['num_of_students_1'];
            } elseif ($this->isset_data('num_of_students_2')) {
                $num_of_students_provided = true;
                $num_of_students = $this->data['num_of_students_2'];
            }
            $is_small_school = $num_of_students_provided && $num_of_students <= 200;
            if ($using_mhf) {
                $inspire_code = 'SER146';
            } elseif ((!$using_mhf) and $is_small_school) {
                if ($num_of_students > 100) {
                    // 101 - 200 students
                    $inspire_code = 'SER148';
                } elseif ($num_of_students <= 100) {
                    // 0 - 100 students
                    $inspire_code = 'SER149';
                }
            }
            $additional = 0;
            if (!$using_mhf && $this->isset_data('inspire_year_levels') && $this->data['inspire_year_levels'] === 'Primary and Secondary') {
                $additional = 1000;
                $this->billing_note = 'Additional $1000 for P-12 Inspire';
            }
            array_push($items, [
                'qty' => 1,
                'code' => $inspire_code,
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
                'additional' => $additional,
            ]);
        } else {
            $this->inspire = '';
        }

        // extend options
        $extend_options = [];

        foreach (self::extend_payload_options as $extend_payload_option) {
            if ($this->isset_data($extend_payload_option)) {
                $current_selected_extends = explode(', ', $this->data[$extend_payload_option]);
                foreach ($current_selected_extends as $current_extend) {
                    $formatted_extend = str_replace('One', '1', $current_extend);
                    $formatted_extend = str_replace('Two', '2', $formatted_extend);
                    $formatted_extend = str_replace('Three', '3', $formatted_extend);
                    $formatted_extend = substr($formatted_extend, 0, strpos($formatted_extend, '$'));

                    array_push($extend_options, $formatted_extend);

                    array_push($items, [
                        'qty' => 1,
                        'code' => self::extend_code_map[$formatted_extend],
                        'duration' => 1,
                        'section_name' => 'Display on Invoice',
                        'section_no' => 1,
                    ]);

                }
            }
        }

        $this->extend = $extend_options;


        $services = $this->get_services(array_column($items, 'code'));
        $line_items = [];

        foreach ($items as $item) {
            $code = $item['code'];
            $service = $services[array_search($code, array_column($services, 'service_no'))];

            $line_item = [
                'productid' => $service->id,
                'quantity' => $item['qty'],
                'listprice' => (int)$service->unit_price + (isset($item['additional']) ? $item['additional'] : 0),
                'tax5' => '10',
                'cf_quotes_xerocode' => $service->cf_services_xerocode,
                'duration' => $item['duration'],
                'section_name' => $item['section_name'],
                'section_no' => $item['section_no'],
            ];

            array_push($line_items, $line_item);
        }
        return $line_items;
    }

    protected function get_quote_stage()
    {
        if ($this->organisation_details['cf_accounts_freetravel'] == '1') {
            return 'Delivered';
        }

        $num_of_workshops = count(
            array_filter($this->extend, function ($k) {
                return strpos($k, 'Workshop');
            })
        );

        if ($num_of_workshops > 0) {
            return 'New';
        }
        return 'Delivered';

    }

}
