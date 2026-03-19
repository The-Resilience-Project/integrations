<?php

trait Qualify
{
    protected function update_qualifier_info()
    {
        $request_body = [
            'dealId' => $this->deal_id,
            'interestedPrograms' => $this->data['interested_programs'],
            'description' => $this->deal_details['description']."\n\n".$this->data['enquiry'],
        ];

        $response = $this->post_request_to_vt('updateDeal', $request_body);
        $response_data = $response->result[0];
    }

    protected function create_qualifier_enquiry()
    {
        $enquiry_subject = $this->data['contact_first_name'].' '. $this->data['contact_last_name'];
        if (!is_null($this->organisation_name)) {
            $enquiry_subject .= ' | '.$this->organisation_name;
        }

        $request_body = [
            'enquirySubject' => $enquiry_subject,
            'enquiryBody' => $this->data['enquiry'],
            'contactId' => $this->contact_id,
            'assignee' => $this->get_enquiry_assignee(),
            'enquiryType' => $this->enquiry_type,
            'workplaceInterestedPrograms' => explode(', ', $this->data['interested_programs']),
            'source' => $this->data['source_form'],
        ];

        $response = $this->post_request_to_vt('createEnquiry', $request_body);

    }

    public function submit_qualifier()
    {
        try {
            $deal_close_date = $this->calculate_close_date('+2 Weeks');

            $this->capture_customer_info();
            $deal_status = $this->data['deal_status'];
            $this->update_or_create_deal($deal_status, $deal_close_date);

            if ($this->data['deal_status'] === 'Ready To Close' and in_array($this->deal_details['sales_stage'], ['New', 'Considering', 'In Campaign'])) {
                $request_body = [
                    'dealId' => $this->deal_id,
                    'dealStage' => 'Ready To Close',
                ];
                $this->post_request_to_vt('updateDeal', $request_body);

            } elseif ($this->data['deal_status'] === 'In Campaign' and in_array($this->deal_details['sales_stage'], ['New', 'Considering'])) {
                $request_body = [
                    'dealId' => $this->deal_id,
                    'dealStage' => 'In Campaign',
                ];
                $this->post_request_to_vt('updateDeal', $request_body);

            }

            // $this->update_qualifier_info();
            $this->create_qualifier_enquiry();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
