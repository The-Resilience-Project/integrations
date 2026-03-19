<?php
class TrpSingleDates{
    protected $form;
    protected $event;
    protected $event_error = false;
    protected $contact_error = false;
    
    function __construct($form) {
        $this->form = $form;
        $this->get_event();
        $this->check_contact();

    }
    
    protected function get_event(){
    	$request_header = array();
    	$request_header[] = "token: ErMGAwNfnc0fPzspaw9diQyz";
        $request_header[] = "Content-Type: application/json";
        
        $request_method = "GET";
        
        $event_id = "18x". rgpost("input_1");

    	$request_handle = curl_init( "https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/getEventDetails" );
    	curl_setopt_array( $request_handle, array(
    		CURLOPT_CUSTOMREQUEST => $request_method,
    		CURLOPT_POSTFIELDS => json_encode(array("eventId" => $event_id)),
    		CURLOPT_RETURNTRANSFER => true,
    		CURLOPT_HEADER => false,
    		CURLOPT_HTTPHEADER => $request_header,
    	));
    
    	$response = curl_exec( $request_handle );
        $json_response = json_decode($response, true);
        curl_close($request_handle);
        if(count($json_response["result"]) == 0){
            $this->event_error = true;
        };
        $this->event = $json_response["result"][0];
    }
    
    protected function check_contact(){
        if(rgpost("input_4") === ""){
            return;
        }
    	$request_header = array();
    	$request_header[] = "token: RjlLbIhNjmR92dtek5YQfAcg";
        $request_header[] = "Content-Type: application/json";
        
        $request_method = "GET";
        
        $contact_id = "4x". rgpost("input_4");

    	$request_handle = curl_init( "https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/getContactById" );
    	curl_setopt_array( $request_handle, array(
    		CURLOPT_CUSTOMREQUEST => $request_method,
    		CURLOPT_POSTFIELDS => json_encode(array("contactId" => $contact_id)),
    		CURLOPT_RETURNTRANSFER => true,
    		CURLOPT_HEADER => false,
    		CURLOPT_HTTPHEADER => $request_header,
    	));
    
    	$response = curl_exec( $request_handle );
        $json_response = json_decode($response, true);
        curl_close($request_handle);
        if(count($json_response["result"]) == 0){
            $this->contact_error = true;
        };
    }
    
    protected function format_date($date, $time){
        $date = new DateTime($date. ' ' . $time . ' +00');
        $date->setTimezone(new DateTimeZone('Australia/Melbourne')); // +04
        
        return date_format($date,"l, j M Y g:iA T");
    }
	
	public function populate_form(){
    	
        
        $id_lookup = array(
            "Teacher Wellbeing Workshop 1 - Looking after Yourself" => array("Teacher Wellbeing 1: Looking After Yourself", "Staff"), 
            "Teacher Wellbeing Workshop 2 - Looking After Each Other" => array("Teacher Wellbeing 2: Looking After Each Other", "Staff"),
            "Teacher Wellbeing Workshop 3 - Sharing Success" => array("Teacher Wellbeing 3: Sharing Success", "Staff"), 
            "Staff Authentic Connection Presentation" => array("Authentic Connection for Staff", "Staff"),
            "Building Resilience at Home for Parents and Carers" => array("Building Resilience at Home for Parents/Carers", "Parents / Carers"), 
            "Parenting with ACE" => array("Parenting with ACE - Authenticity, Connection & Self Esteem", "Parents / Carers"),
            "Connected Parenting" => array("Connected Parenting with Lael Stone", "Parents / Carers"),
            "Digital Wellbeing for Families" => array("Digital Wellbeing for Families", "Parents / Carers"),
        );


        $delivery = $this->event["activitytype"];
        
        $field_data = $id_lookup[$this->event["cf_events_presentationworkshoptype"]];
        $name = $field_data[0];
        $audience = $field_data[1];
        
        $date = $this->format_date($this->event['date_start'], $this->event['time_start']);
        $description= "<div class='event-container'><h5>".$name ."</h5><p>" . $date."</p><div><div><p>". $audience . " ". $delivery ."</p></div></div><div class='h-rule'></div></div>";
        $_POST['input_19'] = $name . " (". $delivery . ")";
        $_POST['input_22'] = ($this->contact_error or $this->event_error) ? "YES" : "NO";
        
  	    foreach($this->form["fields"] as &$field){
            if($field["id"] == 13){
                $field["content"] = $description;
            }
  	    }
	    
	}
}