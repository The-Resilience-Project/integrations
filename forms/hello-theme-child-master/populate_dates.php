<?php
class TrpDates{
    protected $form;
    protected $org_id;
    protected $org_name;
    function __construct($form) {
        $this->form = $form;
        $this->get_org_id();
        $this->get_school_name();

    }
    
    protected function get_org_id(){
        $potential_id = rgpost("input_20");
        if(str_starts_with($potential_id, 'ACC')){
            $request_header = array();
            $request_header[] = "token: iE9d32UPGTrbd89DUVY2grvg";
            $request_header[] = "Content-Type: application/json";
            
            $request_method = "GET";
    
            $request_handle = curl_init( "https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/getOrgWithAccountNo" );
            curl_setopt_array( $request_handle, array(
                CURLOPT_CUSTOMREQUEST => $request_method,
                CURLOPT_POSTFIELDS => json_encode(array("organisationAccountNo" => $potential_id)),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => $request_header,
            ));
        
            $response = curl_exec( $request_handle );
            $json_response = json_decode($response, true);
            curl_close($request_handle);
            $this->org_id = $json_response["result"][0]["id"];
            $this->org_name = $json_response["result"][0]["accountname"];
        } else{
            $this->org_id = "3x".rgpost("input_20");
        }
    }
    
    protected function get_school_name(){
        if($this->org_name){
            return;
        }
    	$request_header = array();
    	$request_header[] = "token: DdtiDMSsq9ETjSe2FMEZBICu";
        $request_header[] = "Content-Type: application/json";
        
        $request_method = "GET";

    	$request_handle = curl_init( "https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/getOrgDetails" );
    	curl_setopt_array( $request_handle, array(
    		CURLOPT_CUSTOMREQUEST => $request_method,
    		CURLOPT_POSTFIELDS => json_encode(array("organisationId" => $this->org_id)),
    		CURLOPT_RETURNTRANSFER => true,
    		CURLOPT_HEADER => false,
    		CURLOPT_HTTPHEADER => $request_header,
    	));
    
    	$response = curl_exec( $request_handle );
        $json_response = json_decode($response, true);
        curl_close($request_handle);
        $this->org_name = $json_response["result"][0]["accountname"];
    }
    
    protected function get_org_events_from_vt(){        
        $event_types = array(
            "'Teacher Wellbeing Workshop 1 - Looking after Yourself'", 
            "'Teacher Wellbeing Workshop 2 - Looking After Each Other'",
            "'Teacher Wellbeing Workshop 3 - Sharing Success'",
            "'Building Resilience at Home for Parents and Carers'",
            "'Parenting with ACE'"
            "'Connected Parenting'",
            "'Digital Wellbeing for Families'"
        );
        $events = array();
        foreach($event_types as $event_type){
            // $query = urlencode("select * from Events where date_start > '2025-01-01' and date_start < '2025-12-31' and cf_events_presentationworkshoptype in (" . implode(',', $event_types) .") ORDER BY date_start ASC");
            $query = urlencode("select * from Events where date_start > '2026-01-01' and date_start < '2026-12-31' and cf_events_presentationworkshoptype = " . $event_type ." ORDER BY date_start ASC");
        	$curl_handle = curl_init("https://theresilienceproject.od2.vtiger.com/restapi/v1/vtiger/default/query_related?relatedLabel=Activities&id=".$this->org_id."&query=".$query);
        	curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl_handle, CURLOPT_USERPWD, "maddie@theresilienceproject.com.au:EKCC5OlQjHZjoOMh");
        	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        	curl_setopt($curl_handle, CURLOPT_HEADER, false);  // don't return headers
        	$response = curl_exec($curl_handle);
        	
        	curl_close($curl_handle);
        	
        	$data = json_decode($response, true);
        	if(!isset($data["result"])){
        	    return array();
        	}
        	
        	$events = array_merge($events, $data["result"]);
        }
        
        return $events;

    	
    }
    
    protected function format_date($date, $time){
        $date = new DateTime($date. ' ' . $time . ' +00');
        $date->setTimezone(new DateTimeZone('Australia/Melbourne')); // +04
        
        return date_format($date,"l, j M Y g:iA T");
    }
	
	public function populate_form(){
    	
        $events = $this->get_org_events_from_vt();
        
        
        $id_lookup = array(
            "Teacher Wellbeing Workshop 1 - Looking after Yourself" => array(
                "Webinar" => array(6, "Teacher Wellbeing 1: Looking After Yourself", "staff"), 
                "In person" => array(7, "Teacher Wellbeing 1: Looking After Yourself", "staff")
            ),
            "Teacher Wellbeing Workshop 2 - Looking After Each Other" => array(
                "Webinar" => array(8, "Teacher Wellbeing 2: Looking After Each Other", "staff"), 
                "In person" => array(9, "Teacher Wellbeing 2: Looking After Each Other", "staff"),
            ),
            "Teacher Wellbeing Workshop 3 - Sharing Success" => array(
                "Webinar" => array(10, "Teacher Wellbeing 3: Sharing Success", "staff"), 
                "In person" => array(11, "Teacher Wellbeing 3: Sharing Success", "staff")
            ),
            "Staff Authentic Connection Presentation" => array(
                "Webinar" => array(12, "Authentic Connection for Staff", "staff")
            ),
            "Building Resilience at Home for Parents and Carers" => array(
                "Webinar" => array(13, "Building Resilience at Home for Parents/Carers", "parents"), 
                "In person" => array(14, "Building Resilience at Home for Parents/Carers", "parents")
            ),
            "Parenting with ACE" => array(
                "Webinar" => array(15, "Parenting with ACE - Authenticity, Connection & Self Esteem", "parents")
            ),
            "Connected Parenting" => array(
                "Webinar" => array(16, "Connected Parenting with Lael Stone", "parents"),
            ),
            "Digital Wellbeing for Families" => array(
                "Webinar" => array(17, "Digital Wellbeing for Families", "parents"), 
                "In person" => array(18, "Digital Wellbeing for Families", "parents")
            ),
        );

        $description = "";
        $event_nos = array();
        
        foreach($events as $event){
            $delivery = $event["activitytype"];
            $field_data = $id_lookup[$event["cf_events_presentationworkshoptype"]][$delivery];
            $field_id = $field_data[0];
            $name = $field_data[1];
            $audience = $field_data[2];
            $date = $this->format_date($event['date_start'], $event['time_start']);
            
            $_POST['input_'.$field_id] = $date;
            $description.= "<div class='event-container " . $audience. "'><p><b>".$name ."</b> (" . $delivery . ") </p><p>" . $date."</p></div>";
            $event_nos[] = $event["event_no"];

        }
        if(count($events) == 0){
            // $description = '<div class="warning-text"><p>Something went wrong. No events found. Please contact us <a href="mailto:bookings@theresilienceproject.com.au">bookings@theresilienceproject.com.au</a></p></div>';
            // set error loading events field to YES
            $_POST['input_27'] = "YES";
        } else{
            $_POST['input_32'] = $this->org_name;
            $_POST["input_20"] = str_replace('3x', '', $this->org_id);
            $_POST['input_29'] = implode(",", $event_nos);
            $sub_header = "<p><b>Please review and click on the button below to confirm these presentation dates.</b></p><br />";
            if(count($events) == 1){
                $sub_header = "<p><b>Please review and click on the button below to confirm this presentation date.</b></p><br />";
            }
            $description = $sub_header . $description;
        }
  	    foreach($this->form["fields"] as &$field){
            if($field["id"] == 25){
                $field["content"] = $description;
            }
            if($field["id"] == 33){
                $field["content"] = $this->org_name . " - 2026 Live Presentation Dates";
            }
  	    }
	    
	}
}