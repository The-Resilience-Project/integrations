<?php

require_once dirname(__FILE__).'/base.php';

require_once dirname(__FILE__).'/traits/enquiry.php';

class GeneralVTController extends VTController
{
    use Enquiry;

    protected $enquiry_type = 'General';

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

    protected function get_enquiry_assignee()
    {
        return self::ASHLEE; // get Dawns's ID
    }

    protected function get_contact_assignee()
    {
        return self::MADDIE;
    }

    protected function get_org_assignee()
    {
        return self::MADDIE;
    }

    protected function capture_customer_info()
    {
        $request_body = [
            'contactEmail' => $this->data['contact_email'],
            'contactFirstName' => $this->data['contact_first_name'],
            'contactLastName' => $this->data['contact_last_name'],
        ];

        if ($this->isset_data('contact_phone')) {
            $request_body['contactPhone'] = $this->data['contact_phone'];
        }

        $response = $this->post_request_to_vt('getContactByEmail', $request_body);
        $response_data = $response->result[0];

        $this->contact_id = $response_data->id;
    }

}

class ImperfectsVTController extends GeneralVTController
{
    protected $enquiry_type = 'Imperfects';
}
