<?php

require_once dirname(__FILE__).'/base.php';
require_once dirname(__FILE__).'/../domain/AssigneeResolver.php';

require_once dirname(__FILE__).'/../traits/enquiry.php';

class GeneralVTController extends VTController
{
    use Enquiry;

    protected $enquiry_type = 'General';

    private AssigneeResolver $assigneeResolver;

    public function __construct($data, ?VtApiClient $api = null)
    {
        parent::__construct($data, $api);
        $this->assigneeResolver = new AssigneeResolver();
    }

    public function submit_enquiry(): bool
    {
        log_info('Starting general enquiry submission', [
            'enquiry_type' => $this->enquiry_type,
            'contact_email' => $this->data['contact_email'] ?? 'unknown',
        ]);

        try {
            log_debug('Capturing customer info');
            $this->capture_customer_info();

            log_debug('Creating enquiry record');
            $this->create_enquiry();

            log_info('General enquiry submitted successfully', [
                'enquiry_type' => $this->enquiry_type,
            ]);
            return true;
        } catch (Exception $e) {
            log_exception($e, [
                'method' => 'submit_enquiry',
                'service_type' => $this->enquiry_type,
            ]);
            return false;
        }
    }

    protected function get_enquiry_assignee()
    {
        return $this->assigneeResolver->resolveGeneralEnquiryAssignee();
    }

    protected function get_contact_assignee()
    {
        return $this->assigneeResolver->resolveGeneralContactAssignee();
    }

    protected function get_org_assignee()
    {
        return $this->assigneeResolver->resolveGeneralOrgAssignee();
    }

    // Overrides the full capture_customer_info() (not just the _in_vt hook)
    // because General/Imperfects only need a contact lookup — no org capture,
    // deactivation, or assignee updates.
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
