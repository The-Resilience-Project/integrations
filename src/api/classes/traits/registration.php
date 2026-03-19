<?php

trait Registration
{
    protected $short_event_name;
    protected function get_event_details($event_id)
    {
        $request_body = [
            'eventId' => $event_id,
        ];

        $response = $this->post_request_to_vt('getEventDetails', $request_body, true);

        return $response->result[0];
    }

    protected function is_contact_registered_for_event($event_no)
    {
        $request_body = [
            'eventNo' => $event_no,
            'contactId' => $this->contact_id,
        ];

        $response = $this->post_request_to_vt('checkContactRegisteredForEvent', $request_body);

        return !empty($response->result);
    }

    protected function register_contact_for_event($event, $reply_to = null)
    {
        if ($this->is_contact_registered_for_event($event->event_no)) {
            return;
        }

        $name = '';
        if ($this->isset_data('contact_first_name')) {
            $name = $this->data['contact_first_name'].' '. $this->data['contact_last_name'];
        } else {
            $name = $this->contact_name;
        }

        $event_start_date = $event->date_start;
        $event_start_datetime = $event_start_date.' '.$event->time_start;

        $request_body = [
            'eventId' => $this->data['event_id'],
            'eventNo' => $event->event_no,
            'eventShortName' => $event->cf_events_shorteventname,
            'eventStart' => $event_start_datetime,
            'eventZoomLink' => $event->cf_events_zoomlink,
            'registrationName' => $name .' | '.$event->event_no,
            'contactId' => $this->contact_id,
            'dealId' => $this->deal_id,
            'source' => $this->data['source_form'],
        ];

        if ($this->isset_data('attendance_type')) {
            $request_body['attendanceType'] = $this->data['attendance_type'];
        }

        if ($this->short_event_name) {
            $request_body['eventShortName'] = $this->short_event_name;
        }

        if ($reply_to) {
            $request_body['replyTo'] = $reply_to;
        }

        $response = $this->post_request_to_vt('registerContact', $request_body);
    }

    public function submit_seminar_registration($event_no, $date_start, $time_start, $cf_events_shorteventname, $cf_events_zoomlink)
    {
        $event = (object)[
            'event_no' => $event_no,
            'date_start' => $date_start,
            'time_start' => $time_start,
            'cf_events_shorteventname' => $cf_events_shorteventname,
            'cf_events_zoomlink' => $cf_events_zoomlink,
        ];
        try {
            $this->capture_customer_info();
            $response = $this->register_contact_for_event($event);
            return $response->result[0]->id;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function update_deal_with_registration($info_session, $close_date)
    {
        $request_body = [
            'dealId' => $this->deal_id,
            'dealCloseDate' => $close_date,
        ];
        if ($info_session) {
            $request_body['firstInfoSessionDate'] = $info_session;
        }
        if (in_array($this->deal_details['sales_stage'], ['New'])) {
            $request_body['dealStage'] = 'Considering';
        }

        $response = $this->post_request_to_vt('updateDeal', $request_body);
        $response_data = $response->result[0];
    }

    protected function update_deal_with_wp_webinar_recording($close_date)
    {
        $request_body = [
            'dealId' => $this->deal_id,
            'dealCloseDate' => $close_date,
        ];
        if (in_array($this->deal_details['sales_stage'], ['New', 'Considering'])) {
            $request_body['dealStage'] = 'In Campaign';
        }

        $response = $this->post_request_to_vt('updateDeal', $request_body);
        $response_data = $response->result[0];
    }

}
