<?php

class VTController
{
    protected $baseUrl = 'https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/';
    protected $data;
    protected $organisation_name;
    protected $enquiry_type;

    protected const capture_customer_info_token = 'j2bXkMP4TaPmKjTBFXVIsq1K';
    protected const capture_customer_info_with_account_no_token = 'ACyvS7YEKlzdFnUGQs25YFii';
    protected const deactivate_contacts_token = 'P2VyeIMyd8oBZSNzm7tkCPBE';
    protected const create_deal_token = 'r8ZUEYcio6VpH0O54jDtE55L';
    protected const update_or_create_deal_token = 'rUrrGktcgYOMPRwg6tyyaFFq';
    protected const update_deal_token = 'ftC3lOd8l9LPs5VoCGC1y8SY';
    protected const create_enquiry_token = '8di4F24NumqITmuAky325Vj3';
    protected const register_contact_token = 'xfQg4BDmOPg7TIkUb3kPWFqc';
    protected const get_contact_by_email_token = 'cmgsCER088SIbv913HcTVUKd';
    protected const get_event_details_token = 'ErMGAwNfnc0fPzspaw9diQyz';
    protected const get_organisation_details_token = 'DdtiDMSsq9ETjSe2FMEZBICu';
    protected const update_contact_token = 'YECve9NgFG1C6Os4DjRGeoHC';
    protected const check_contact_registered_for_event_token = 'znlPIG5g7I4ajSP3YMTCvetO';
    protected const get_contact_details_token = 'EBKJfO9StV8vQyeM0epx0SR9';
    protected const get_deal_details_token = 'RtpXl9YAcWzWL4B5ZHDHQGuX';
    protected const get_deal_details_from_accountno_token = 'K7Ub30Q3dLe8dT9UKCpjOPKP';
    protected const create_quote_token = 'aYgcfg1PFm1abA3a8QUEZGpJ';
    protected const get_services_token = 'jMgenBKJZxTi0mpz4Ga4rQom';
    protected const set_deal_line_items_token = 'QfbZs2azB5yo2ps1yBIDzSp1';
    protected const set_quote_line_items_token = 'g3j6HqHbKLjANpWSBAYAuA0R';
    protected const update_organisation_token = 'BP4MhG0zOY0qcY4fEzXnWIFZ';

    protected const MADDIE = '19x1';
    protected const LAURA = '19x8';
    protected const VICTOR = '19x33';
    protected const DAWN = '19x22';
    protected const EMMA = '19x15';
    protected const ED_TEAM = '20x47';
    protected const HELENOR = '19x24';

    protected $contact_id;
    protected $billing_contact_id;
    protected $billing_contact_email;
    protected $organisation_id;
    protected $organisation_details;
    protected $deal_id;
    protected $deal_first_info_session_date;
    protected $quote_id;

    public function __construct($data)
    {
        $this->data = $data;
    }

    protected function post_request_to_vt($token, $endpoint, $request_body, $get = false)
    {
        $request_header = [];
        $request_header[] = 'token: '.$token;
        $request_header[] = 'Content-Type: application/json';

        $request_method = $get ? 'GET' : 'POST';


        $request_handle = curl_init($this->baseUrl.$endpoint);
        curl_setopt_array($request_handle, [
            CURLOPT_CUSTOMREQUEST => $request_method,
            CURLOPT_POSTFIELDS => json_encode($request_body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request_header,
        ]);

        $response = curl_exec($request_handle);
        $json_response = json_decode($response);
        curl_close($request_handle);
        return $json_response;
    }

    private function post_request_with_line_items($token, $endpoint, $request_body, $line_items)
    {
        $request_string = '';
        foreach ($request_body as $key => $value) {
            $request_string .= $key.'='.$value.'&';

        }
        $request_string .= 'lineItems='.json_encode($line_items);

        $request_header = [];
        $request_header[] = 'token: '.$token;
        $request_header[] = 'Content-Type: application/x-www-form-urlencoded';


        $request_handle = curl_init($this->baseUrl.$endpoint);
        curl_setopt_array($request_handle, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $request_string,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request_header,
        ]);

        $response = curl_exec($request_handle);
        $json_response = json_decode($response);
        curl_close($request_handle);
        return $json_response;
    }

    protected function deactivate_contacts($email)
    {

        $request_body = [
            'contactEmail' => $email,
        ];

        $response = $this->post_request_to_vt(self::deactivate_contacts_token, 'setContactsInactive', $request_body);
    }

    protected function get_contact_by_email()
    {
        $request_body = [
            'contactEmail' => $this->data['contact_email'],
            'contactFirstName' => $this->data['contact_first_name'],
            'contactLastName' => $this->data['contact_last_name'],
        ];

        if (isset($this->data['contact_phone'])) {
            $request_body['contactPhone'] = $this->data['contact_phone'];
        }

        $response = $this->post_request_to_vt(self::get_contact_by_email_token, 'getContactByEmail', $request_body);
        $response_data = $response->result[0];

        $this->contact_id = $response_data->id;
    }

    protected function get_organisation_details()
    {
        $request_body = [
            'organisationId' => $this->organisation_id,
        ];

        $response = $this->post_request_to_vt(self::get_organisation_details_token, 'getOrgDetails', $request_body, true);
        $response_data = $response->result[0];
        return $response_data;
    }

    protected function set_organisation_details()
    {
        if ($this->organisation_details) {
            return;
        }

        $org = $this->get_organisation_details();

        $this->organisation_name = $org->accountname;
        $this->organisation_details = [
            'assigned_user_id' => $org->assigned_user_id,
            'cf_accounts_2025salesevents' => $org->cf_accounts_2025salesevents,
            'cf_accounts_yearswithtrp' => $org->cf_accounts_yearswithtrp,
        ];

        $this->update_org_assignee();

    }

    protected function update_org_assignee()
    {
        $request_body = [];

        $new_org_assignee = $this->get_org_assignee();
        $existing_org_assignee = $this->organisation_details['assigned_user_id'];
        if ($new_org_assignee != $existing_org_assignee) {
            $request_body['assignee'] = $new_org_assignee;
        }

        if (count($request_body) == 0) {
            return;
        }

        $request_body['organisationId'] = $this->organisation_id;

        $response = $this->post_request_to_vt(self::update_organisation_token, 'updateOrganisation', $request_body);

        $response_data = $response->result[0];
        $this->organisation_details['assigned_user_id'] = $response_data->assigned_user_id;
    }

    protected function update_forms_completed_and_assignee($contact)
    {
        $request_body = [];

        $current_form = $this->data['source_form'];
        $existing_forms_array = explode(' |##| ', $contact->cf_contacts_formscompleted);
        if (!in_array($current_form, $existing_forms_array)) {
            array_push($existing_forms_array, $current_form);
            $request_body['contactLeadSource'] = $existing_forms_array;
        }

        $new_contact_assignee = $this->get_contact_assignee();
        $existing_contact_assignee = $contact->assigned_user_id;
        if ($new_contact_assignee != $existing_contact_assignee) {
            $request_body['assignee'] = $new_contact_assignee;
        }

        if (count($request_body) == 0) {
            return;
        }

        $request_body['contactId'] = $contact->id;

        $response = $this->post_request_to_vt(self::update_contact_token, 'updateContactById', $request_body);

    }

    protected function update_years_with_trp($year)
    {
        $years_with_trp_array = explode(' |##| ', $this->organisation_details['cf_accounts_yearswithtrp']);
        if (in_array($year, $years_with_trp_array)) {
            return;
        }
        array_push($years_with_trp_array, $year);

        $request_body = [
            'organisationId' => $this->organisation_id,
            'yearsWithTrp' => $years_with_trp_array,
        ];

        $response = $this->post_request_to_vt(self::update_organisation_token, 'updateOrganisation', $request_body);

        $response_data = $response->result[0];
        $this->organisation_details['cf_accounts_yearswithtrp'] = $response_data->cf_accounts_yearswithtrp;
    }

    protected function capture_main_customer_info($organisation_status = null)
    {
        $main_customer_data = [
            'contact_email' => $this->data['contact_email'],
            'contact_first_name' => $this->data['contact_first_name'],
            'contact_last_name' => $this->data['contact_last_name'],
            'contact_type' => 'Primary',
        ];

        if (isset($this->data['contact_phone'])) {
            $main_customer_data['contact_phone'] = $this->data['contact_phone'];
        }

        $response_data = $this->capture_customer_info_in_vt($main_customer_data, $organisation_status);

        $this->contact_id = $response_data->id;
        $this->organisation_id = $response_data->account_id;

        $this->set_organisation_details();
        $this->update_forms_completed_and_assignee($response_data);

    }

    protected function capture_customer_info($organisation_status = null)
    {
        $main_customer_data = [
            'contact_email' => $this->data['contact_email'],
            'contact_first_name' => $this->data['contact_first_name'],
            'contact_last_name' => $this->data['contact_last_name'],
        ];

        if (isset($this->data['contact_phone'])) {
            $main_customer_data['contact_phone'] = $this->data['contact_phone'];
        }

        $response_data = $this->capture_customer_info_in_vt($main_customer_data, $organisation_status);

        $this->contact_id = $response_data->id;
        $this->organisation_id = $response_data->account_id;

        $this->set_organisation_details();
        $this->update_forms_completed_and_assignee($response_data);

    }

    protected function capture_billing_contact_info($organisation_status = null)
    {
        if ($this->data['different_billing_contact'] == 'No') {
            $this->billing_contact_id = $this->contact_id;
            $this->billing_contact_email = $this->data['contact_email'];
            return;
        }

        $billing_customer_data = [
            'contact_email' => $this->data['billing_contact_email'],
            'contact_first_name' => $this->data['billing_contact_first_name'],
            'contact_last_name' => $this->data['billing_contact_last_name'],
            'contact_type' => 'Billing',

        ];

        if (isset($this->data['billing_contact_phone'])) {
            $main_customer_data['billing_contact_phone'] = $this->data['billing_contact_phone'];
        }

        $response_data = $this->capture_customer_info_in_vt($billing_customer_data, $organisation_status);
        $this->set_organisation_details();
        $this->update_forms_completed_and_assignee($response_data);

        $this->billing_contact_id = $response_data->id;
        $this->billing_contact_email = $this->data['billing_contact_email'];
    }

    protected function format_customer_info_payload($customer_data, $organisation_status)
    {
        $request_body = [
            'contactEmail' => $customer_data['contact_email'],
            'contactFirstName' => $customer_data['contact_first_name'],
            'contactLastName' => $customer_data['contact_last_name'],
            'organisationType' => $this->organisation_type,
        ];

        if ($organisation_status) {
            $request_body['organisationStatus'] = $organisation_status;
        }

        if ($this->organisation_name) {
            $request_body['organisationName'] = $this->organisation_name;
        }

        if (isset($this->data['state'])) {
            $request_body['state'] = $this->data['state'];
        }

        if (isset($customer_data['contact_type'])) {
            $request_body['contactType'] = $customer_data['contact_type'];
        }

        if (isset($customer_data['contact_phone'])) {
            $request_body['contactPhone'] = $customer_data['contact_phone'];
        }

        if (isset($this->data['contact_lead_source'])) {
            $request_body['contactLeadSource'] = $this->data['contact_lead_source'];
        }

        if (isset($this->data['num_of_students'])) {
            $request_body['organisationNumOfStudents'] = $this->data['num_of_students'];
        }

        if (isset($this->data['num_of_ey_children'])) {
            $request_body['organisationNumOfStudents'] = $this->data['num_of_ey_children'];
        }

        if (isset($this->data['num_of_employees'])) {
            $request_body['organisationNumOfEmployees'] = $this->data['num_of_employees'];
        }

        if (isset($this->data['contact_lead_source'])) {
            $request_body['contactLeadSource'] = $this->data['contact_lead_source'];
        }
        if (isset($this->data['organisation_sub_type'])) {
            $request_body['organisationSubType'] = $this->data['organisation_sub_type'];
        }

        return $request_body;
    }

    protected function capture_customer_info_in_vt($customer_data, $organisation_status = null)
    {
        $this->deactivate_contacts($customer_data['contact_email']);

        $request_body = $this->format_customer_info_payload($customer_data, $organisation_status);
        $response = $this->post_request_to_vt(self::capture_customer_info_token, 'captureCustomerInfo', $request_body);
        $response_data = $response->result[0];

        return $response_data;

    }

    protected function update_or_create_deal($deal_stage, $deal_close_date, $only_create = false)
    {
        $request_body = [
            'dealName' => $this->deal_name,
            'dealType' => $this->deal_type,
            'dealOrgType' => $this->deal_org_type,
            'dealStage' => $deal_stage,
            'dealCloseDate' => $deal_close_date,
            'contactId' => $this->contact_id,
            'organisationId' => $this->organisation_id,
            'assignee' => $this->get_contact_assignee(),
        ];

        if (isset($this->data['participating_num_of_students'])) {
            $request_body['dealNumOfParticipants'] = $this->data['participating_num_of_students'];
        } elseif (isset($this->data['num_of_students'])) {
            $request_body['dealNumOfParticipants'] = $this->data['num_of_students'];
        }

        if (isset($this->data['num_of_ey_children'])) {
            $request_body['dealNumOfParticipants'] = $this->data['num_of_ey_children'];
        }

        if (isset($this->data['num_of_employees'])) {
            $request_body['dealNumOfParticipants'] = $this->data['num_of_employees'];
        }

        $response_data;

        if ($only_create) {
            $response = $this->post_request_to_vt(self::create_deal_token, 'createDeal', $request_body);
            $response_data = $response->result;

        } else {
            $response = $this->post_request_to_vt(self::update_or_create_deal_token, 'getOrCreateDeal', $request_body);
            $response_data = $response->result[0];

        }



        $this->deal_id = $response_data->id;
        $this->deal_first_info_session_date = $response_data->cf_potentials_firstinfosessiondate;
    }

    protected function update_deal_with_registration($info_session, $close_date)
    {
        $request_body = [
            'dealId' => $this->deal_id,
            'firstInfoSessionDate' => $info_session,
            'dealCloseDate' => $close_date,
        ];

        $response = $this->post_request_to_vt(self::update_deal_token, 'updateDeal', $request_body);
        $response_data = $response->result[0];
    }

    protected function get_services($codes)
    {
        $request_body = ['serviceNumbers' => $codes];
        $response = $this->post_request_to_vt(self::get_services_token, 'getServices', $request_body, true);
        return $response->result;
    }

    protected function get_line_items()
    {
        $engage_code = 'SER12';

        $inspire = 'Hugh Digital';
        if (isset($this->data['inspire']) and !is_null($this->data['inspire'])) {
            $inspire = $this->data['inspire'];
        }


        $inspire_price_map = [
            'Hugh Digital' => ['SER18', 'SER29', 'SER144'],
            'Martin Digital' => ['SER19', 'SER21', 'SER145'],
        ];

        $inspire_code = $inspire_price_map[$inspire][0];
        $not_using_mhf = isset($this->data['mental_health_funding']) ? $this->data['mental_health_funding'] === 'No' : true;
        $is_small_school = isset($this->data['num_of_students']) ? $this->data['num_of_students'] <= 200 : false;
        if ($not_using_mhf and $is_small_school) {
            if ($this->data['num_of_students'] > 100) {
                // 101 - 200 students
                $inspire_code = $inspire_price_map[$inspire][1];
            } else {
                // 0 - 100 students
                $inspire_code = $inspire_price_map[$inspire][2];
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
                'cf_quotes_xerocode' => $service->cf_services_xerocode,
                'duration' => $item['duration'],
                'section_name' => $item['section_name'],
                'section_no' => $item['section_no'],
            ];

            array_push($line_items, $line_item);
        }
        return $line_items;
    }

    protected function set_deal_line_items($line_items, $total)
    {
        $request_body = [
            'dealId' => $this->deal_id,
            'total' => $total,
        ];
        $response = $this->post_request_with_line_items(self::set_deal_line_items_token, 'setDealLineItems', $request_body, $line_items);
    }

    protected function update_deal_with_confirmation($total)
    {
        $request_body = [
            'dealId' => $this->deal_id,
            'contactId' => $this->contact_id,
            'billingContactId' => $this->billing_contact_id,
            'inspire' => [$this->data['inspire']],
            'engage' => [$this->data['engage']],
            'address' => $this->data['address'],
            'suburb' => $this->data['suburb'],
            'postcode' => $this->data['postcode'],
            'state' => $this->data['state'],
            'total' => $total,
        ];

        if (isset($this->data['mental_health_funding'])) {
            $request_body['mentalHealthFunding'] = $this->data['mental_health_funding'];
        }

        $response = $this->post_request_to_vt(self::update_deal_token, 'updateDeal', $request_body);
    }

    protected function create_enquiry()
    {
        $enquiry_subject = $this->data['contact_first_name'].' '. $this->data['contact_last_name'];
        if (!is_null($this->organisation_name)) {
            $enquiry_subject .= ' | '.$this->organisation_name;
        }

        $request_body = [
            'enquirySubject' => $enquiry_subject,
            'enquiryBody' => $this->data['enquiry'] ? $this->data['enquiry'] : 'Conference Enquiry',
            'contactId' => $this->contact_id,
            'assignee' => $this->get_enquiry_assignee(),
            'enquiryType' => $this->enquiry_type,
        ];

        $response = $this->post_request_to_vt(self::create_enquiry_token, 'createEnquiry', $request_body);

    }

    protected function get_event_details($event_id)
    {
        $request_body = [
            'eventId' => $event_id,
        ];

        $response = $this->post_request_to_vt(self::get_event_details_token, 'getEventDetails', $request_body, true);

        return $response->result[0];
    }

    protected function is_contact_registered_for_event($event_no)
    {
        $request_body = [
            'eventNo' => $event_no,
            'contactId' => $this->contact_id,
        ];

        $response = $this->post_request_to_vt(self::check_contact_registered_for_event_token, 'checkContactRegisteredForEvent', $request_body);

        return !empty($response->result);
    }

    protected function register_contact_for_event($event)
    {
        if ($this->is_contact_registered_for_event($event->event_no)) {
            return;
        }

        $event_start_date = $event->date_start;
        $event_start_datetime = $event_start_date.' '.$event->time_start;

        $request_body = [
            'eventId' => $this->data['event_id'],
            'eventNo' => $event->event_no,
            'eventShortName' => $event->cf_events_shorteventname,
            'eventStart' => $event_start_datetime,
            'eventZoomLink' => $event->cf_events_zoomlink,
            'registrationName' => $this->data['contact_first_name'].' '. $this->data['contact_last_name'].' | '.$event->event_no,
            'contactId' => $this->contact_id,
            'dealId' => $this->deal_id,
            'source' => $this->data['source_form'],
        ];

        $response = $this->post_request_to_vt(self::register_contact_token, 'registerContact', $request_body);
    }

    protected function create_quote($line_items)
    {
        $request_body = [
            'dealId' => $this->deal_id,
            'subject' => $this->quote_name,
            'type' => $this->quote_type,
            'program' => $this->quote_program,
            'stage' => $this->quote_stage,
            'contactId' => $this->contact_id,
            'contactEmail' => $this->data['contact_email'],
            'billingContactId' => $this->billing_contact_id,
            'billingContactEmail' => $this->billing_contact_email,
            'organisationId' => $this->organisation_id,
            'assignee' => $this->get_contact_assignee(),
            'address' => $this->data['address'],
            'suburb' => $this->data['suburb'],
            'postcode' => $this->data['postcode'],
            'state' => $this->data['state'],

        ];
        $response = $this->post_request_with_line_items(self::create_quote_token, 'createQuote', $request_body, $line_items);

        $response_data = $response;
        $this->quote_id = $response_data->id;
    }

    public function submit_enquiry()
    {
        try {
            $deal_close_date = date('d/m/Y', strtotime('+2 Weeks'));

            $this->capture_customer_info();
            $this->update_or_create_deal('New', $deal_close_date);
            $this->create_enquiry();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function add_one_day($date_string)
    {
        $date = new DateTime($date_string);
        $date->add(new DateInterval('P1D'));
        return $date->format('d/m/Y');
    }

    public function _submit_event_registration($deal_stage, $only_create = false)
    {
        try {
            $event = $this->get_event_details($this->data['event_id']);
            $event_start_date = $event->date_start;
            $event_start_datetime = $event_start_date.' '.$event->time_start;

            $deal_close_date = $this->add_one_day($event_start_date);

            $this->capture_customer_info();
            $this->update_or_create_deal($deal_stage, $deal_close_date, $only_create);
            $this->register_contact_for_event($event);

            $first_info_session_date = $this->deal_first_info_session_date;
            if (empty($this->deal_first_info_session_date) or strcmp($event_start_datetime, $this->deal_first_info_session_date) == -1) {
                $first_info_session_date = $event_start_datetime;
            }

            $this->update_deal_with_registration($first_info_session_date, $this->add_one_day($first_info_session_date));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function submit_prize_pack_entry()
    {
        try {
            $this->capture_customer_info('Lead');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function submit_seminar_registration($event_no, $date_start, $time_start, $cf_events_shorteventname, $cf_events_zoomlink)
    {
        $event = (object)[
            'event_no' => $event_no,
            'date_start' => $date_start,
            'time_start' => $time_start,
            'cf_events_shorteventname' => $cf_events_shorteventname,
            'cf_events_zoomlink' => $cf_events_zoomlink,
        ];
        try {
            $this->capture_customer_info();
            $response = $this->register_contact_for_event($event);
            return $response->result[0]->id;
        } catch (Exception $e) {
            return false;
        }
    }

    public function confirm_program()
    {
        try {
            $deal_stage = 'Deal Won';
            $deal_close_date = date('d/m/Y');
            $this->capture_main_customer_info();
            $this->update_or_create_deal($deal_stage, $deal_close_date);
            $this->capture_billing_contact_info();

            $line_items = $this->get_line_items();
            $total = array_sum(array_map(function ($item) {
                return $item['listprice'] * $item['quantity'];
            }, $line_items));

            $this->update_deal_with_confirmation($total);
            $this->set_deal_line_items($line_items, $total);

            $this->create_quote($line_items);
            $this->update_years_with_trp('2025');
        } catch (Exception $e) {
            return false;
        }
    }
}

class SchoolVTController extends VTController
{
    protected $organisation_type = 'School';
    protected $deal_name = '2025 School Partnership Program';
    protected $deal_type = 'School';
    protected $deal_org_type = 'School - New';
    protected $enquiry_type = 'School';
    protected $quote_name = '2025 School Partnership Program';
    protected $quote_type = 'School - New';
    protected $quote_program = 'School';
    protected $quote_stage = 'Delivered';



    protected function capture_customer_info_in_vt($customer_data, $organisation_status = null)
    {
        $this->deactivate_contacts($customer_data['contact_email']);

        $request_body = $this->format_customer_info_payload($customer_data, $organisation_status);

        $response;

        if ($this->data['school_name_other_selected']) {
            $request_body['organisationName'] = $this->data['school_name_other'];
            $response = $this->post_request_to_vt(self::capture_customer_info_token, 'captureCustomerInfo', $request_body);
        } else {
            $request_body['organisationAccountNo'] = $this->data['school_account_no'];
            $response = $this->post_request_to_vt(self::capture_customer_info_with_account_no_token, 'captureCustomerInfoWithAccountNo', $request_body);
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
            return self::VICTOR;
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
            return self::VICTOR;
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
            return self::VICTOR;
        }
        return self::LAURA;
    }

    protected function is_new_school()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        $not_spms = [self::MADDIE, self::LAURA, self::VICTOR, self::HELENOR];
        return in_array($org_assignee, $not_spms);
    }

    public function submit_event_registration()
    {
        return $this->_submit_event_registration('Considering');
    }

    public function get_info_for_confirmation_form($account_no = null, $accountname = null)
    {
        $deal_response;
        $request_body = [
            'dealName' => $this->deal_name,
        ];
        if (!empty($account_no)) {
            $request_body['organisationAccountNo'] = $account_no;
            $deal_response = $this->post_request_to_vt(self::get_deal_details_from_accountno_token, 'getDealDetailsFromAccountNo', $request_body, true);
        } else {
            $request_body['organisationName'] = $accountname;
            $deal_response = $this->post_request_to_vt(self::get_deal_details_token, 'getDealDetails', $request_body, true);
        }
        if (empty($deal_response) or empty($deal_response->result) or empty($deal_response->result[0])) {
            return [
                'deal_status' => '',
                'free_travel' => 'NO',
                'priority' => 'NO',
            ];
        }
        $deal_details = $deal_response->result[0];

        $deal_status = $deal_details->sales_stage;

        $org_request_body = ['organisationId' => $deal_details->related_to];
        $org_response = $this->post_request_to_vt(self::get_organisation_details_token, 'getOrgDetails', $org_request_body, true);
        $org_details = $org_response->result[0];


        return [
            'deal_status' => $deal_status,
            'free_travel' => $org_details->cf_accounts_freetravel,
            'priority' => $org_details->cf_accounts_priority,
        ];

    }

    protected function update_org_assignee()
    {
        $request_body = [];

        $current_form = $this->data['source_form'];
        $existing_forms_array = explode(' |##| ', $this->organisation_details['cf_accounts_2025salesevents']);
        if (!in_array($current_form, $existing_forms_array)) {
            array_push($existing_forms_array, $current_form);
            $request_body['salesEvents2025'] = $existing_forms_array;
        }

        $new_org_assignee = $this->get_org_assignee();
        $existing_org_assignee = $this->organisation_details['assigned_user_id'];
        if ($new_org_assignee != $existing_org_assignee) {
            $request_body['assignee'] = $new_org_assignee;
        }

        if (count($request_body) == 0) {
            return;
        }

        $request_body['organisationId'] = $this->organisation_id;

        $response = $this->post_request_to_vt(self::update_organisation_token, 'updateOrganisation', $request_body);

        $response_data = $response->result[0];
        $this->organisation_details['assigned_user_id'] = $response_data->assigned_user_id;
        $this->organisation_details['cf_accounts_2025salesevents'] = $response_data->cf_accounts_2025salesevents;
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

}

class WorkplaceVTController extends VTController
{
    protected $organisation_type = 'Workplace';
    protected $deal_name = '2024 Workplace Partnership Program';
    protected $deal_type = 'Workplace';
    protected $deal_org_type = 'Workplace';
    protected $enquiry_type = 'Workplace';

    public function __construct($data)
    {
        parent::__construct($data);
        $this->organisation_name = $data['organisation_name'];
    }

    protected function get_enquiry_assignee()
    {
        return self::LAURA;
    }

    protected function get_contact_assignee()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        if ($org_assignee != self::MADDIE) {
            return $org_assignee;
        }
        return self::LAURA;
    }

    protected function get_org_assignee()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        if ($org_assignee != self::MADDIE) {
            return $org_assignee;
        }
        return self::LAURA;
    }

    public function submit_event_registration()
    {
        return $this->_submit_event_registration('In Campaign', true);
    }

    public function submit_enquiry()
    {
        try {
            $deal_close_date = date('d/m/Y', strtotime('+2 Weeks'));

            $this->capture_customer_info();
            $this->update_or_create_deal('New', $deal_close_date, true);
            $this->create_enquiry();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

class GeneralVTController extends VTController
{
    protected $enquiry_type = 'General';

    public function submit_enquiry()
    {
        try {
            $this->get_contact_by_email();
            $this->create_enquiry();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function get_enquiry_assignee()
    {
        return self::DAWN; // get Dawns's ID
    }

    protected function get_contact_assignee()
    {
        return self::MADDIE;
    }

    protected function get_org_assignee()
    {
        return self::MADDIE;
    }
}

class ImperfectsVTController extends GeneralVTController
{
    protected $enquiry_type = 'Imperfects';
}

class EarlyYearsVTController extends VTController
{
    protected $organisation_type = 'Early Years';
    protected $deal_name = '2025 Early Years Partnership Program';
    protected $deal_type = 'Early Years';
    protected $deal_org_type = 'Early Years';
    protected $enquiry_type = 'Early Years';

    public function __construct($data)
    {
        parent::__construct($data);
        $this->organisation_name = $data['earlyyears_name'];
    }

    protected function get_enquiry_assignee()
    {
        return self::EMMA; // get Emma's ID
    }

    protected function get_contact_assignee()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        if ($org_assignee != self::MADDIE) {
            return $org_assignee;
        }
        return self::EMMA;
    }

    protected function get_org_assignee()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        if ($org_assignee != self::MADDIE) {
            return $org_assignee;
        }
        return self::EMMA;
    }

    public function submit_enquiry()
    {
        try {
            $this->capture_customer_info();
            $this->create_enquiry();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
