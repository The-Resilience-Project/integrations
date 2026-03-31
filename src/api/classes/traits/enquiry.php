<?php

trait Enquiry
{
    protected function create_enquiry()
    {
        $enquiry_subject = $this->data['contact_first_name'].' '. $this->data['contact_last_name'];
        if (!is_null($this->organisation_name)) {
            $enquiry_subject .= ' | '.$this->organisation_name;
        }

        $request_body = [
            'enquirySubject' => $enquiry_subject,
            'enquiryBody' => $this->isset_data('enquiry') ? $this->data['enquiry'] : 'Conference Enquiry',
            'contactId' => $this->contact_id,
            'assignee' => $this->get_enquiry_assignee(),
            'enquiryType' => $this->enquiry_type,
        ];

        $response = $this->post_request_to_vt('createEnquiry', $request_body);

    }
}
