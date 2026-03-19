<?php

trait AcceptDates
{
    protected $date_acceptance_id;
    protected const event_date_mapping = [
        'twb_1_web' => 'Teacher Wellbeing 1: Looking After Yourself (Webinar)',
        'twb_1_inp' => 'Teacher Wellbeing 1: Looking After Yourself (In Person)',
        'twb_2_web' => 'Teacher Wellbeing 2: Looking After Each Other (Webinar)',
        'twb_2_inp' => 'Teacher Wellbeing 2: Looking After Each Other (In Person)',
        'twb_3_web' => 'Teacher Wellbeing 3: Sharing Success (Webinar)',
        'twb_3_inp' => 'Teacher Wellbeing 3: Sharing Success (In Person)',
        'ac_staff' => 'Authentic Connection for Staff (Webinar)',
        'brh_web' => 'Building Resilience at Home for Parents/Carers (Webinar)',
        'brh_inp' => 'Building Resilience at Home for Parents/Carers (In Person)',
        'ac_parents' => 'Parenting with ACE (Webinar)',
        'cp' => 'Connected Parenting with Lael Stone (Webinar)',
        'dwf_web' => 'Digital Wellbeing for Families (Webinar)',
        'dwf_inp' => 'Digital Wellbeing for Families (In Person)',

    ];
    protected function get_date_acceptance_body()
    {

        log_debug('Building date acceptance body', ['data' => $this->data]);
        $body = '<ul>';
        foreach (self::event_date_mapping as $data_key => $event_name) {
            if ($this->isset_data($data_key)) {
                $body .= '<li><b>'. $event_name . '</b><br />' . $this->data[$data_key] . '<br /><br /></li>';
            }

        }
        $body .= '</ul>';
        return $body;
    }

    protected function create_date_acceptance_record()
    {
        $request_body = [
            'dateAcceptanceSubject' => '2026 School Date Acceptance',
            'email' => $this->data['email'],
            'emailBody' => $this->get_date_acceptance_body(),
            'organisationId' => '3x'.$this->data['school_id'],
            'acceptedEvents' => $this->data['event_nos'],
        ];

        $response = $this->post_request_to_vt('createDateAcceptance', $request_body);
        $response_data = $response->result;
        $this->date_acceptance_id = $response_data->id;
    }

    protected function link_documents()
    {
        $event_nos = explode(',', $this->data['event_nos']);

        foreach ($event_nos as $i => $event_no) {
            $request_body = [
                'eventNo' => $event_no,
                'dateAcceptanceId' => $this->date_acceptance_id,
            ];

            if ($i == count($event_nos) - 1) {
                $request_body['allDocumentsLinked'] = true;
            }
            $response = $this->post_request_to_vt('updateDateAcceptance', $request_body);
        }
    }
    public function accept_dates()
    {
        try {
            $this->create_date_acceptance_record();
            // $this->link_documents();
        } catch (Exception $e) {
            return false;
        }


    }

}
