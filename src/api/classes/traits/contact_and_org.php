<?php

trait ContactAndOrg
{
    protected function deactivate_contacts($email)
    {
        $request_body = [
            'contactEmail' => $email,
        ];

        $response = $this->post_request_to_vt('setContactsInactive', $request_body);
    }

    protected function get_organisation_details()
    {
        if ($this->organisation_details) {
            return;
        }

        $request_body = [
            'organisationId' => $this->organisation_id,
        ];

        $response = $this->post_request_to_vt('getOrgDetails', $request_body, true);
        $org = $response->result[0];
        $this->organisation_name = $org->accountname;
        $this->organisation_details = [
            'assigned_user_id' => $org->assigned_user_id,
            'cf_accounts_2025salesevents' => $org->cf_accounts_2025salesevents,
            'cf_accounts_freetravel' => $org->cf_accounts_freetravel,
            'cf_accounts_yearswithtrp' => $org->cf_accounts_yearswithtrp,
            'cf_accounts_2024inspire' => $org->cf_accounts_2024inspire,
            'cf_accounts_2025inspire' => $org->cf_accounts_2025inspire,
            'cf_accounts_2025confirmationstatus' => $org->cf_accounts_2025confirmationstatus,
            'cf_accounts_2024confirmationstatus' => $org->cf_accounts_2024confirmationstatus,
        ];

    }
    protected function get_contact_details($c_id)
    {
        $contact_id = $c_id;
        if (!str_contains($contact_id, '4x')) {
            $contact_id = '4x' . $contact_id;
        }

        $request_body = [
            'contactId' => $contact_id,
        ];

        $response = $this->post_request_to_vt('getContactById', $request_body, true);
        $contact = $response->result[0];
        $this->contact_name = $contact->firstname . ' '. $contact->lastname;
        $this->organisation_id = $contact->account_id;
        $this->contact_id = $contact->id;
    }


    protected function update_organisation()
    {
        $this->get_organisation_details();

        $request_body = [];

        $new_org_assignee = $this->get_org_assignee();
        $existing_org_assignee = $this->organisation_details['assigned_user_id'];
        if ($new_org_assignee != $existing_org_assignee) {
            $request_body['assignee'] = $new_org_assignee;
        }

        $current_form = $this->data['source_form'];
        $existing_forms_array = explode(' |##| ', $this->organisation_details['cf_accounts_2025salesevents']);
        if (!in_array($current_form, $existing_forms_array)) {
            array_push($existing_forms_array, $current_form);
            $request_body['salesEvents2025'] = $existing_forms_array;
        }

        if (count($request_body) == 0) {
            return;
        }

        $request_body['organisationId'] = $this->organisation_id;

        $response = $this->post_request_to_vt('updateOrganisation', $request_body);

        $response_data = $response->result[0];
        $this->organisation_details['assigned_user_id'] = $response_data->assigned_user_id;
    }

    protected function update_contact($contact)
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

        $response = $this->post_request_to_vt('updateContactById', $request_body);

    }

    protected function capture_main_customer_info()
    {
        $main_customer_data = [
            'contact_email' => $this->data['contact_email'],
            'contact_first_name' => $this->data['contact_first_name'],
            'contact_last_name' => $this->data['contact_last_name'],
            'contact_type' => 'Primary',
        ];

        if ($this->isset_data('contact_phone')) {
            $main_customer_data['contact_phone'] = $this->data['contact_phone'];
        }
        if ($this->isset_data('org_phone')) {
            $main_customer_data['org_phone'] = $this->data['org_phone'];
        }

        $response_data = $this->capture_customer_info_in_vt($main_customer_data);

        $this->contact_id = $response_data->id;
        $this->organisation_id = $response_data->account_id;

        $this->update_organisation();
        $this->update_contact($response_data);

    }

    protected function capture_customer_info()
    {
        $customer_data = [
            'contact_email' => $this->data['contact_email'],
            'contact_first_name' => $this->data['contact_first_name'],
            'contact_last_name' => $this->data['contact_last_name'],
        ];

        if ($this->isset_data('contact_phone')) {
            $customer_data['contact_phone'] = $this->data['contact_phone'];
        }
        if ($this->isset_data('org_phone')) {
            $customer_data['org_phone'] = $this->data['org_phone'];
        }
        if ($this->isset_data('job_title')) {
            $customer_data['job_title'] = $this->data['job_title'];
        }
        $response_data = $this->capture_customer_info_in_vt($customer_data);

        $this->contact_id = $response_data->id;
        $this->organisation_id = $response_data->account_id;

        $this->update_organisation();
        $this->update_contact($response_data);

    }

    protected function capture_billing_contact_info()
    {
        if ($this->data['different_billing_contact'] == 'No') {
            return;
        }

        $billing_customer_data = [
            'contact_email' => $this->data['billing_contact_email'],
            'contact_first_name' => $this->data['billing_contact_first_name'],
            'contact_last_name' => $this->data['billing_contact_last_name'],
            'contact_type' => 'Billing',

        ];

        if ($this->isset_data('billing_contact_phone')) {
            $billing_customer_data['billing_contact_phone'] = $this->data['billing_contact_phone'];
        }
        if ($this->isset_data('org_phone')) {
            $billing_customer_data['org_phone'] = $this->data['org_phone'];
        }

        $response_data = $this->capture_customer_info_in_vt($billing_customer_data);
        $this->update_organisation();
        $this->update_contact($response_data);

        $this->billing_contact_id = $response_data->id;
        $this->billing_contact_email = $this->data['billing_contact_email'];
    }

    protected function capture_other_contact_info()
    {

        $customer_data = [
            'contact_email' => $this->data['contact_email'],
            'contact_first_name' => $this->data['contact_first_name'],
            'contact_last_name' => $this->data['contact_last_name'],
            'contact_type' => $this->data['contact_type'],

        ];

        if ($this->isset_data('contact_phone')) {
            $customer_data['contact_phone'] = $this->data['contact_phone'];
        }
        if ($this->isset_data('org_phone')) {
            $customer_data['org_phone'] = $this->data['org_phone'];
        }
        if ($this->isset_data('contact_newsletter')) {
            $customer_data['contact_newsletter'] = $this->data['contact_newsletter'];
        }

        $response_data = $this->capture_customer_info_in_vt($customer_data);

        $this->contact_id = $response_data->id;
        $this->organisation_id = $response_data->account_id;
    }

    protected function format_customer_info_payload($customer_data)
    {
        $request_body = [
            'contactEmail' => $customer_data['contact_email'],
            'contactFirstName' => $customer_data['contact_first_name'],
            'contactLastName' => $customer_data['contact_last_name'],
            'organisationType' => $this->organisation_type,
        ];

        if ($this->organisation_name) {
            $request_body['organisationName'] = $this->organisation_name;
        }

        if ($this->isset_data('state')) {
            $request_body['state'] = $this->data['state'];
        }

        if (isset($customer_data['contact_type'])) {
            $request_body['contactType'] = $customer_data['contact_type'];
        }

        if (isset($customer_data['contact_phone'])) {
            $request_body['contactPhone'] = $customer_data['contact_phone'];
        }
        if (isset($customer_data['org_phone'])) {
            $request_body['orgPhone'] = $customer_data['org_phone'];
        }
        if (isset($customer_data['contact_newsletter'])) {
            $request_body['newsletter'] = $customer_data['contact_newsletter'];
        }
        if (isset($customer_data['job_title'])) {
            $request_body['jobTitle'] = $customer_data['job_title'];
        }


        if ($this->isset_data('num_of_students') and !empty($this->data['num_of_students'])) {
            $request_body['organisationNumOfStudents'] = $this->data['num_of_students'];
        } elseif ($this->isset_data('num_of_ey_children') and !empty($this->data['num_of_ey_children'])) {
            $request_body['organisationNumOfStudents'] = $this->data['num_of_ey_children'];
        }

        if ($this->isset_data('num_of_employees')) {
            $request_body['organisationNumOfEmployees'] = $this->data['num_of_employees'];
        }

        if ($this->isset_data('contact_lead_source')) {
            $request_body['contactLeadSource'] = $this->data['contact_lead_source'];
        }
        if ($this->isset_data('organisation_sub_type')) {
            $request_body['organisationSubType'] = $this->data['organisation_sub_type'];
        }

        return $request_body;
    }
}
