<?php

trait CalendlyProspect
{
    protected $short_event_name;

    public function create_calendly_prospect()
    {
        try {

            $this->capture_customer_info();

            $date_string = $this->data['scheduled_date'];
            $date = new DateTime($date_string);
            $date->modify('+10 days');
            $deal_close_date = $date->format('d/m/Y');

            $this->update_or_create_deal('Considering', $deal_close_date);
            $this->update_deal_status_for_calendly();


        } catch (Exception $e) {
            return false;
        }
    }

    protected function update_deal_status_for_calendly()
    {
        $date_string = $this->data['scheduled_date'];
        $date = new DateTime($date_string);
        $melb_time = new DateTimeZone('Australia/Melbourne');
        $date->setTimezone($melb_time);
        $meeting_date = $date->format('d/m/Y H:i:s');


        $description = $this->deal_details['description'] ? $this->deal_details['description'] . '<br /><br />' : '';
        $description .= 'Calendly meeting booked for: ' . $meeting_date;
        if ($this->data['info_provided'] !== '') {
            $description .= '<br />Info provided: ' . $this->data['info_provided'];
        }
        $request_body = [
            'dealId' => $this->deal_id,
            'description' => $description,

        ];
        if (in_array($this->deal_details['sales_stage'], ['New'])) {
            $request_body['dealStage'] = 'Considering';
        }
        $response = $this->post_request_to_vt('updateDeal', $request_body);
    }

}
