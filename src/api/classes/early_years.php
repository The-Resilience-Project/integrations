<?php
require_once dirname(__FILE__)."/base.php";

require_once dirname(__FILE__)."/traits/enquiry.php";
require_once dirname(__FILE__)."/traits/lead.php";
require_once dirname(__FILE__)."/traits/registration.php";
require_once dirname(__FILE__)."/traits/confirmation.php";


class EarlyYearsVTController extends VTController {
    use Enquiry;
    use Lead;
    use Registration;
    use Confirmation;


	protected $organisation_type = "Early Years";
	protected $deal_name = "2026 Early Years Partnership Program";
	protected $deal_type = "Early Years";
	protected $deal_org_type = "Early Years - New";
	protected $enquiry_type = "Early Years";
	
	protected $inspire = "EY Digital";
	protected $quote_name = "2026 Early Years Partnership Program";
	protected $quote_type = "Early Years";
	protected $quote_program = "Early Years";
    
    protected function get_enquiry_assignee(){
        return self::BRENDAN;
    }
    
    protected function get_contact_assignee(){
        $org_assignee = $this->organisation_details["assigned_user_id"];
        if($org_assignee != self::MADDIE){
            return $org_assignee;
        }  
        return self::BRENDAN;
    }
    
    protected function get_org_assignee(){
        $org_assignee = $this->organisation_details["assigned_user_id"];
        if($org_assignee != self::MADDIE){
            return $org_assignee;
        }  
        return self::BRENDAN;
    }

    // protected function capture_customer_info_in_vt($customer_data){
    //     $this->deactivate_contacts($customer_data["contact_email"]);

    //     $request_body = $this->format_customer_info_payload($customer_data);
    //     $response = $this->post_request_to_vt("captureCustomerInfo", $request_body);
    //     $response_data = $response->result[0];
            
    //     return $response_data;

    // }
    protected function capture_customer_info_in_vt($customer_data){
        log_info("Capturing Early Years customer info in VTiger", [
            'contact_email' => $customer_data["contact_email"] ?? 'unknown',
            'is_new_organisation' => $this->data["service_name_other_selected"] ?? false
        ]);

        log_debug("Deactivating existing contacts for email", [
            'email' => $customer_data["contact_email"]
        ]);
        $this->deactivate_contacts($customer_data["contact_email"]);

    	$request_body = $this->format_customer_info_payload($customer_data);

        if($this->data["service_name_other_selected"]){
            $request_body["organisationName"] = $this->data["earlyyears_name_other"];
            log_info("Creating new Early Years organisation", [
                'organisation_name' => $this->data["earlyyears_name_other"]
            ]);
            $response = $this->post_request_to_vt("captureCustomerInfo", $request_body);
        } else {
            $request_body["organisationAccountNo"] = $this->data["earlyyears_account_no"];
            log_info("Looking up existing Early Years organisation by account number", [
                'account_no' => $this->data["earlyyears_account_no"]
            ]);
            $response = $this->post_request_to_vt("captureCustomerInfoWithAccountNo", $request_body);
        }

        $response_data = $response->result[0];
        log_info("Early Years customer info captured successfully", [
            'organisation_id' => $response_data->organisationId ?? 'unknown',
            'contact_id' => $response_data->contactId ?? 'unknown'
        ]);
        return $response_data;
    }
    
    public function submit_enquiry(){
        log_info("Starting Early Years enquiry submission", [
            'organisation' => $this->data["earlyyears_name_other"] ?? $this->data["earlyyears_account_no"] ?? 'unknown',
            'contact_email' => $this->data["contact_email"] ?? 'unknown'
        ]);

        try{
            $deal_close_date = date("d/m/Y", strtotime("+2 Weeks"));
            log_debug("Calculated deal close date", ['close_date' => $deal_close_date]);

            log_debug("Capturing customer information");
            $this->capture_customer_info();

            log_debug("Updating or creating Early Years deal", [
                'stage' => 'New',
                'close_date' => $deal_close_date
            ]);
            $this->update_or_create_deal("New", $deal_close_date);

            log_debug("Creating enquiry record");
            $this->create_enquiry();

            log_info("Early Years enquiry submitted successfully");
            return true;
        }
        catch(Exception $e){
            log_exception($e, [
                'method' => 'submit_enquiry',
                'service_type' => 'Early Years',
                'organisation' => $this->data["earlyyears_name_other"] ?? $this->data["earlyyears_account_no"] ?? 'unknown'
            ]);
            return false;
        }
    }
    
    public function submit_event_registration(){
        log_info("Starting Early Years event registration", [
            'event_id' => $this->data['event_id'] ?? 'unknown',
            'organisation' => $this->data["earlyyears_name_other"] ?? $this->data["earlyyears_account_no"] ?? 'unknown',
            'contact_email' => $this->data["contact_email"] ?? 'unknown'
        ]);

        try{
            log_debug("Retrieving event details from VTiger", [
                'event_id' => $this->data['event_id']
            ]);
            $event = $this->get_event_details($this->data['event_id']);
        	$event_start_date = $event->date_start;
        	$event_start_datetime = $event_start_date." ".$event->time_start;

            log_debug("Event details retrieved", [
                'event_start_date' => $event_start_date,
                'event_start_time' => $event->time_start
            ]);

            log_debug("Capturing customer information");
            $this->capture_customer_info();

            $deal_close_date = $this->add_one_day($event_start_date);
            log_debug("Updating or creating Early Years deal for event registration", [
                'stage' => 'Considering',
                'close_date' => $deal_close_date
            ]);
            $this->update_or_create_deal("Considering", $deal_close_date);

            $first_info_session_date = $this->deal_details["cf_potentials_firstinfosessiondate"];

            if(empty($first_info_session_date) or strcmp($event_start_datetime, $first_info_session_date) == -1){
                log_debug("Updating first info session date", [
                    'previous_date' => $first_info_session_date ?? 'none',
                    'new_date' => $event_start_datetime
                ]);
                $first_info_session_date = $event_start_datetime;
            }

            log_debug("Updating deal with registration details");
            $this->update_deal_with_registration($first_info_session_date, $this->add_one_day($first_info_session_date));

            log_debug("Registering contact for event");
            $this->register_contact_for_event($event);

            log_info("Early Years event registration submitted successfully", [
                'event_id' => $this->data['event_id'],
                'contact_id' => $this->contact_id ?? 'unknown'
            ]);
            return true;
        }
        catch(Exception $e){
            log_exception($e, [
                'method' => 'submit_event_registration',
                'service_type' => 'Early Years',
                'event_id' => $this->data['event_id'] ?? 'unknown',
                'organisation' => $this->data["earlyyears_name_other"] ?? $this->data["earlyyears_account_no"] ?? 'unknown'
            ]);
            return false;
        }
    }
    
    protected function get_quote_stage(){
        return "Delivered";
    }
    
    protected function get_line_items(){
        log_info("Building Early Years line items", [
            'participating_students' => $this->data["participating_num_of_students"] ?? 0
        ]);

        $engage_code = "SER84";
        $inspire_code = "SER13";

        $items = array(
            array(
                "qty" => 1,
                "code" => $inspire_code,
                "duration" => 1,
                "section_name" => "Display on Invoice",
                "section_no" => 1,
            ),
            array(
                "qty" => $this->data["participating_num_of_students"],
                "code" => $engage_code,
                "duration" => 1,
                "section_name" => "Display on Invoice",
                "section_no" => 1,
            ),
        );

        log_debug("Retrieving service details from VTiger", [
            'service_codes' => [$inspire_code, $engage_code]
        ]);
        $services = $this->get_services(array_column($items, "code"));
        $line_items = array();

        foreach($items as $item){
            $code = $item["code"];
            $service = $services[array_search($code, array_column($services, 'service_no'))];

            $line_item = array(
                "productid" => $service->id,
                "quantity" => $item["qty"],
                "listprice" => $service->unit_price,
                "tax5" => "10",
                "cf_quotes_xerocode" => $service->cf_services_xerocode,
                "duration" => $item["duration"],
                "section_name" => $item["section_name"],
                "section_no" => $item["section_no"],
            );

            log_debug("Added line item", [
                'service_code' => $code,
                'quantity' => $item["qty"],
                'unit_price' => $service->unit_price,
                'line_total' => $service->unit_price * $item["qty"]
            ]);

            array_push($line_items, $line_item);
        }

        $total = array_sum(array_map(function($item) {
            return $item['listprice'] * $item['quantity'];
        }, $line_items));

        log_info("Early Years line items built successfully", [
            'line_items_count' => count($line_items),
            'pre_tax_total' => $total
        ]);

        return $line_items;
    }
    
    public function get_info_for_confirmation_form($account_no=null, $accountname=null){
        log_info("Getting Early Years info for confirmation form", [
            'account_no' => $account_no ?? 'null',
            'account_name' => $accountname ?? 'null',
            'deal_name' => $this->deal_name
        ]);

        $request_body = array(
            "dealName"=> $this->deal_name
        );

        if(!empty($account_no)){
            $request_body["organisationAccountNo"] = $account_no;
            log_debug("Looking up Early Years organisation and deal by account number", [
                'account_no' => $account_no
            ]);
            $org_response = $this->post_request_to_vt("getOrgWithAccountNo", $request_body, true);
            $deal_response = $this->post_request_to_vt("getDealDetailsFromAccountNo", $request_body, true);
        } else {
            $request_body["organisationName"] = $accountname;
            log_debug("Looking up Early Years organisation and deal by name", [
                'organisation_name' => $accountname
            ]);
            $org_response = $this->post_request_to_vt("getOrgWithName", $request_body, true);
            $deal_response = $this->post_request_to_vt("getDealDetails", $request_body, true);
        }


        $deal_status = "";
        $deal_id = "";
        // $deal_org_type = "";
        // $engage = "";
        if(!empty($deal_response) and !empty($deal_response->result) and !empty($deal_response->result[0])){
            $deal_details = $deal_response->result[0];
            $deal_status = $deal_details->sales_stage;
            $deal_id = $deal_details->id;
            // $deal_org_type = $deal_details->cf_potentials_orgtype;
            // $engage = $deal_details->cf_potentials_curriculum;

            log_info("Early Years deal found", [
                'deal_id' => $deal_id,
                'deal_status' => $deal_status
            ]);
        } else {
            log_warning("No Early Years deal found for organisation", [
                'account_no' => $account_no ?? 'null',
                'account_name' => $accountname ?? 'null'
            ]);
        }

        // $free_travel = "";
        // $priority = "";
        // $funded_years = "";
        // $org_state = "";
        // $org_leading_trp = "";
        // if(!empty($org_response) and !empty($org_response->result) and !empty($org_response->result[0])){
        //     $org_details = $org_response->result[0];
        //     $free_travel = $org_details->cf_accounts_freetravel;
        //     $priority = $org_details->cf_accounts_priority;
        //     $funded_years = $org_details->cf_accounts_fundedyears;
        //     $org_state = $org_details->cf_accounts_statenew;
        //     $org_leading_trp = $org_details->cf_accounts_leadingtrp;
        // }

        return array(
            "deal_status" => $deal_status,
            "id" => $deal_id,
            // "deal_org_type" => $deal_org_type,
            // "engage" => $engage,
            // "free_travel" => $free_travel,
            // "priority" => $priority,
            // "funded_years" => $funded_years,
            // "org_state" => $org_state,
            // "leading_trp" => $org_leading_trp,
        );

    }
    

}