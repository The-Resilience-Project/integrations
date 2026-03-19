<?php

require_once dirname(__FILE__).'/base.php';
require_once dirname(__FILE__).'/AssigneeResolver.php';
require_once dirname(__FILE__).'/LineItemCalculator.php';

require_once dirname(__FILE__).'/traits/enquiry.php';
require_once dirname(__FILE__).'/traits/confirmation.php';
require_once dirname(__FILE__).'/traits/lead.php';
require_once dirname(__FILE__).'/traits/registration.php';
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

    protected AssigneeResolver $assigneeResolver;
    protected LineItemCalculator $lineItemCalculator;

    public function __construct($data, ?VtApiClient $api = null)
    {
        parent::__construct($data, $api);
        $this->assigneeResolver = new AssigneeResolver();
        $this->lineItemCalculator = new LineItemCalculator();
    }

    protected function capture_customer_info_in_vt($customer_data)
    {
        $this->deactivate_contacts($customer_data['contact_email']);

        $request_body = $this->format_customer_info_payload($customer_data);

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
        return $this->assigneeResolver->resolveSchoolEnquiryAssignee(
            $this->organisation_details['assigned_user_id'],
            $this->data['state']
        );
    }

    protected function get_contact_assignee()
    {
        return $this->assigneeResolver->resolveSchoolContactAssignee(
            $this->organisation_details['assigned_user_id'],
            $this->data['state']
        );
    }

    protected function get_org_assignee()
    {
        return $this->assigneeResolver->resolveSchoolOrgAssignee(
            $this->organisation_details['assigned_user_id'],
            $this->data['state']
        );
    }

    protected function is_new_school()
    {
        return $this->assigneeResolver->isNewSchool(
            $this->organisation_details['assigned_user_id']
        );
    }

    protected function get_registration_reply_to()
    {
        return $this->assigneeResolver->resolveSchoolRegistrationReplyTo(
            $this->data['state']
        );
    }



    public function submit_enquiry(): bool
    {
        log_info('Starting school enquiry submission', [
            'organisation' => $this->data['school_name_other'] ?? $this->data['school_account_no'] ?? 'unknown',
            'contact_email' => $this->data['contact_email'] ?? 'unknown',
        ]);

        try {
            $deal_close_date = $this->calculate_close_date('+2 Weeks');
            log_debug('Calculated deal close date', ['close_date' => $deal_close_date]);

            log_debug('Capturing school customer info');
            $this->capture_customer_info();

            if ($this->is_new_school()) {
                log_info('New school detected, creating deal', [
                    'stage' => 'New',
                    'close_date' => $deal_close_date,
                ]);
                $this->update_or_create_deal('New', $deal_close_date);
            }

            log_debug('Creating school enquiry record');
            $this->create_enquiry();

            log_info('School enquiry submitted successfully');
            return true;
        } catch (Exception $e) {
            log_exception($e, [
                'method' => 'submit_enquiry',
                'service_type' => 'School',
                'organisation' => $this->data['school_name_other'] ?? $this->data['school_account_no'] ?? 'unknown',
            ]);
            return false;
        }
    }

    public function get_info_for_confirmation_form($account_no = null, $accountname = null)
    {
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
        $result = $this->lineItemCalculator->calculateNewSchoolItems($this->data);
        $this->inspire = $result['inspire'];
        $items = $result['items'];

        $services = $this->get_services(array_column($items, 'code'));
        $line_items = [];

        foreach ($items as $item) {
            $code = $item['code'];
            $service = $this->find_service_by_code($services, $code);

            $line_items[] = [
                'productid' => $service->id,
                'quantity' => $item['qty'],
                'listprice' => $service->unit_price,
                'tax5' => '10',
                'cf_quotes_xerocode' => $service->cf_services_xerocode,
                'duration' => $item['duration'],
                'section_name' => $item['section_name'],
                'section_no' => $item['section_no'],
            ];
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
                    $deal_close_date = $this->calculate_close_date('+4 Weeks');

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

    public function get_line_items()
    {
        $result = $this->lineItemCalculator->calculateExistingSchoolItems(
            $this->data,
            $this->organisation_details
        );

        $this->inspire = $result['inspire'];
        $this->engage = $result['engage'];
        $this->extend = $result['extend'];
        $this->billing_note = $result['billing_note'];

        $items = $result['items'];
        $services = $this->get_services(array_column($items, 'code'));
        $line_items = [];

        foreach ($items as $item) {
            $code = $item['code'];
            $service = $this->find_service_by_code($services, $code);

            $line_items[] = [
                'productid' => $service->id,
                'quantity' => $item['qty'],
                'listprice' => (int) $service->unit_price + (isset($item['additional']) ? $item['additional'] : 0),
                'tax5' => '10',
                'cf_quotes_xerocode' => $service->cf_services_xerocode,
                'duration' => $item['duration'],
                'section_name' => $item['section_name'],
                'section_no' => $item['section_no'],
            ];
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
