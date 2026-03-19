<?php

require_once dirname(__FILE__).'/base.php';

require_once dirname(__FILE__).'/traits/enquiry.php';
require_once dirname(__FILE__).'/traits/registration.php';
require_once dirname(__FILE__).'/traits/qualify.php';
require_once dirname(__FILE__).'/traits/calendly_prospect.php';
require_once dirname(__FILE__).'/traits/lead.php';

class WorkplaceVTController extends VTController
{
    use Enquiry;
    use Registration;
    use Qualify;
    use CalendlyProspect;
    use Lead;

    protected $organisation_type = 'Workplace';
    protected $deal_name = '2026 Workplace Partner';
    protected $deal_type = 'Workplace';
    protected $deal_org_type = 'Workplace - New';
    protected $enquiry_type = 'Workplace';


    protected function get_enquiry_assignee()
    {
        return self::LAURA;
    }

    private function get_assignee_or_default()
    {
        $org_assignee = $this->organisation_details['assigned_user_id'];
        if ($org_assignee != self::MADDIE) {
            return $org_assignee;
        }
        return self::LAURA;
    }

    protected function get_contact_assignee()
    {
        return $this->get_assignee_or_default();
    }

    protected function get_org_assignee()
    {
        return $this->get_assignee_or_default();
    }

    public function submit_enquiry()
    {
        log_info('Starting workplace enquiry submission');

        try {
            $deal_close_date = $this->calculate_close_date('+10 Days');
            log_debug('Calculated deal close date', ['close_date' => $deal_close_date]);

            log_debug('Capturing workplace customer info');
            $this->capture_customer_info();

            log_info('Updating or creating workplace deal', [
                'status' => 'New',
                'close_date' => $deal_close_date,
                'deal_name' => $this->deal_name,
            ]);
            $this->update_or_create_deal('New', $deal_close_date);

            log_debug('Creating workplace enquiry record');
            $this->create_enquiry();

            log_info('Workplace enquiry submitted successfully');
            return true;
        } catch (Exception $e) {
            log_exception($e, [
                'method' => 'submit_enquiry',
                'controller' => 'WorkplaceVTController',
                'organization' => $this->data['organisation_name'] ?? $this->data['workplace_name_other'] ?? 'unknown',
            ]);
            return false;
        }
    }

    protected function capture_customer_info_in_vt($customer_data)
    {
        $this->deactivate_contacts($customer_data['contact_email']);

        $request_body = $this->format_customer_info_payload($customer_data);

        if ($this->isset_data('organisation_name')) {
            $request_body['organisationName'] = $this->data['organisation_name'];
            $response = $this->post_request_to_vt('captureCustomerInfo', $request_body);
        } elseif ($this->isset_data('workplace_name_other_selected')) {
            $request_body['organisationName'] = $this->data['workplace_name_other'];
            $response = $this->post_request_to_vt('captureCustomerInfo', $request_body);
        } else {
            $request_body['organisationAccountNo'] = $this->data['workplace_account_no'];
            $response = $this->post_request_to_vt('captureCustomerInfoWithAccountNo', $request_body);
        }

        $response_data = $response->result[0];
        return $response_data;
    }



    public function submit_event_registration()
    {
        log_info('Starting workplace event registration');

        try {
            $event = $this->get_event_details($this->data['event_id']);
            log_debug('Retrieved event details', [
                'event_id' => $this->data['event_id'],
                'event_name' => $event->eventtitle ?? 'unknown',
            ]);

            if ($this->isset_data('role_workplace')) {
                $this->data['job_title'] = $this->data['role_workplace'];
            }

            if ($this->isset_data('role_school')) {
                $this->data['job_title'] = $this->data['role_school'];
            }

            if ($this->isset_data('role_ey')) {
                $this->data['job_title'] = $this->data['role_ey'];
            }

            log_debug('Capturing customer info for event registration');
            $this->capture_customer_info();

            if ($this->data['source_form'] === 'Workplace Webinar Recording 2025') {
                $size = (int)$this->data['num_of_employees'] >= 100 ? ' >100' : ' <100';
                $this->data['source_form'] .= $size;
                log_debug('Workplace webinar recording detected', [
                    'size' => $size,
                    'num_employees' => $this->data['num_of_employees'],
                ]);

                if ($this->organisation_details['cf_accounts_2025confirmationstatus'] === '' and in_array($this->data['organisation_sub_type'], ['Professional Services', 'Healthcare','Government','Not for Profit','Retail/Wholesale'])) {
                    $deal_close_date = $this->calculate_close_date('+10 Days');
                    log_info('Creating workplace deal for webinar recording', [
                        'status' => 'In Campaign',
                        'close_date' => $deal_close_date,
                        'org_sub_type' => $this->data['organisation_sub_type'],
                        '2025_confirmation_status' => 'empty',
                    ]);
                    $this->create_deal('In Campaign', $deal_close_date);
                } else {
                    log_debug('Skipping deal creation', [
                        '2025_confirmation_status' => $this->organisation_details['cf_accounts_2025confirmationstatus'],
                        'org_sub_type' => $this->data['organisation_sub_type'] ?? 'unknown',
                    ]);
                }
            }

            log_debug('Registering contact for event', [
                'event_id' => $this->data['event_id'],
                'contact_email' => $this->data['contact_email'] ?? 'unknown',
            ]);
            $this->register_contact_for_event($event);

            log_info('Workplace event registration completed successfully');
            return true;

        } catch (Exception $e) {
            log_exception($e, [
                'method' => 'submit_event_registration',
                'controller' => 'WorkplaceVTController',
                'event_id' => $this->data['event_id'] ?? 'unknown',
                'organization' => $this->data['organisation_name'] ?? $this->data['workplace_name_other'] ?? 'unknown',
            ]);
            return false;
        }
    }

}
