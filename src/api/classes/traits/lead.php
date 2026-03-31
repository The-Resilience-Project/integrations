<?php

trait Lead
{
    protected function mark_org_as_2026_lead()
    {
        $this->get_organisation_details();

        if ($this->organisation_details['cf_accounts_2026confirmationstatus'] !== '') {
            return;
        }

        $request_body = [
            'organisationId' => $this->organisation_id,
            'organisation2026Status' => 'Lead',

        ];

        $response = $this->post_request_to_vt('updateOrganisation', $request_body);

    }
    protected function mark_org_as_2025_lead()
    {
        $this->get_organisation_details();

        if ($this->organisation_details['cf_accounts_2025confirmationstatus'] !== '') {
            return;
        }

        $request_body = [
            'organisationId' => $this->organisation_id,
            'organisation2025Status' => 'Lead',

        ];

        $response = $this->post_request_to_vt('updateOrganisation', $request_body);

    }

    protected function mark_org_as_2024_lead()
    {
        $this->get_organisation_details();

        if ($this->organisation_details['cf_accounts_2024confirmationstatus'] !== '') {
            return;
        }


        $request_body = [
            'organisationId' => $this->organisation_id,
            'organisation2024Status' => 'Lead',

        ];

        $response = $this->post_request_to_vt('updateOrganisation', $request_body);

    }

    public function submit_prize_pack_entry()
    {
        try {
            $this->capture_customer_info();
            $this->mark_org_as_2026_lead();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
