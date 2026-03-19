<?php
trait Deal {    

	protected function capture_deal_in_vt($request_body){
		$response = $this->post_request_to_vt("getOrCreateDeal", $request_body);
		return $response->result[0];
	}
	
    protected function add_one_day($date_string){
            $date = new DateTime($date_string);
            $date->add(new DateInterval('P1D'));
            return $date->format('d/m/Y');
    }

    private function build_deal_payload($deal_stage, $deal_close_date){
    	$request_body = array(
    		"dealName" => $this->deal_name,
    		"dealType" => $this->deal_type,
    		"dealOrgType" => $this->deal_org_type,
    		"dealStage" => $deal_stage,
    		"dealCloseDate" => $deal_close_date,
    		"contactId" => $this->contact_id,
    		"organisationId" => $this->organisation_id,
    		"assignee" => $this->get_contact_assignee(),
    	);

        if($this->isset_data("participating_num_of_students")){
    	    $request_body["dealNumOfParticipants"] = $this->data["participating_num_of_students"];
    	}
        else if($this->isset_data("num_of_students")){
    	    $request_body["dealNumOfParticipants"] = $this->data["num_of_students"];
    	}

    	if($this->isset_data("num_of_ey_children")){
    	    $request_body["dealNumOfParticipants"] = $this->data["num_of_ey_children"];
    	}

    	if($this->isset_data("num_of_employees")){
    	    $request_body["dealNumOfParticipants"] = $this->data["num_of_employees"];
    	}

    	return $request_body;
    }

    protected function update_or_create_deal($deal_stage, $deal_close_date){
    	$request_body = $this->build_deal_payload($deal_stage, $deal_close_date);

    	if($this->isset_data("state")){
    	    $request_body["dealState"] = $this->data["state"];
    	}

    	$response_data = $this->capture_deal_in_vt($request_body);

        $this->deal_id = $response_data->id;
        $this->deal_details = array(
            "cf_potentials_firstinfosessiondate" => $response_data->cf_potentials_firstinfosessiondate,
            "description" => $response_data->description,
            "sales_stage" => $response_data->sales_stage,
            "cf_potentials_billingnote" => $response_data->cf_potentials_billingnote,
        );
    }

    protected function create_deal($deal_stage, $deal_close_date){
    	$request_body = $this->build_deal_payload($deal_stage, $deal_close_date);

		$response = $this->post_request_to_vt("getOrCreateDeal", $request_body);
		$response_data = $response->result[0];

        $this->deal_id = $response_data->id;
        $this->deal_details = array(
            "cf_potentials_firstinfosessiondate" => $response_data->cf_potentials_firstinfosessiondate,
            "description" => $response_data->description,
            "sales_stage" => $response_data->sales_stage,
        );
    }    
}