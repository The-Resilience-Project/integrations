<?php
require_once dirname(__FILE__)."/base.php";

require_once dirname(__FILE__)."/traits/enquiry.php";

class GeneralVTController extends VTController {
    use Enquiry;
    
    protected $enquiry_type = "General";
    
    public function submit_enquiry(){
        try{
            $this->capture_customer_info();
            $this->create_enquiry();
            return true;
        }
        catch(Exception $e){
            log_exception($e, [
                'method' => 'submit_enquiry',
                'service_type' => $this->enquiry_type,
            ]);
            return false;
        }
    }
    
    protected function get_enquiry_assignee(){
        return self::ASHLEE;
    }
    
    protected function get_contact_assignee(){
        return self::MADDIE;
    }
    
    protected function get_org_assignee(){
        return self::MADDIE;
    }

    // Overrides the full capture_customer_info() (not just the _in_vt hook)
    // because General/Imperfects only need a contact lookup — no org capture,
    // deactivation, or assignee updates.
    protected function capture_customer_info(){
        $request_body = array(
            "contactEmail" => $this->data["contact_email"], 
            "contactFirstName" => $this->data["contact_first_name"], 
            "contactLastName" => $this->data["contact_last_name"],
        );
        
        if($this->isset_data("contact_phone")){
            $request_body["contactPhone"] = $this->data["contact_phone"];
        }
        
        $response = $this->post_request_to_vt("getContactByEmail", $request_body);
        $response_data = $response->result[0];
        
        $this->contact_id = $response_data->id;
    }
    
}

class ImperfectsVTController extends GeneralVTController{
    protected $enquiry_type = "Imperfects";
}