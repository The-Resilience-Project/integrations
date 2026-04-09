<?php

/**
 * Date & Event Forms (70, 72)
 *
 * Handles prepopulation for the date acceptance and event confirmation forms.
 */

require_once __DIR__ . '/form_ids.php';

// Date acceptance form
add_filter('gform_pre_render_' . FORM_DATE_ACCEPTANCE_2025, 'populate_date_acceptance');
function populate_date_acceptance($form)
{
    if (GFFormDisplay::get_current_page($form['id']) == 2) {
        require_once('populate_dates.php');
        $dates = new TrpDates($form);
        $dates->populate_form();
    }

    return $form;
}

// Event confirmation form
add_filter('gform_pre_render_' . FORM_EVENT_CONFIRMATION_2025, 'populate_event_acceptance');
function populate_event_acceptance($form)
{
    if (GFFormDisplay::get_current_page($form['id']) == 2) {
        require_once('populate_event_date.php');
        $dates = new TrpSingleDates($form);
        $dates->populate_form();
    }

    return $form;
}
