<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/api_helpers.php';
require dirname(__FILE__).'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$raw_data = get_request_data();

$calendly_payload = $raw_data['payload'];
$vic_link = 'https://api.calendly.com/event_types/033a4470-b2f0-4e57-85b4-a2af4383b4f1';
$laura_link = 'https://api.calendly.com/event_types/c3c384f9-de9f-4155-9b9c-c86eb378facb';
$round_robin_link = 'https://api.calendly.com/event_types/053e1993-414f-4619-a4a0-b3c218fbcedb';
if (!in_array($calendly_payload['scheduled_event']['event_type'], [$vic_link, $laura_link, $round_robin_link])) {
    log_data('Integration not required');
    send_response([
        'status' => 'success',
        'message' => 'integration not required',
    ]);
    exit;
}

$question_text = 'Organisation Name';

if ($calendly_payload['scheduled_event']['event_type'] === $round_robin_link) {
    $question_text = 'Workplace Name';
}

$found_key_org = array_search($question_text, array_column($calendly_payload['questions_and_answers'], 'question'));
$org_name = $calendly_payload['questions_and_answers'][$found_key_org]['answer'];

$found_key_info = array_search('Please share anything that will help prepare for our meeting.', array_column($calendly_payload['questions_and_answers'], 'question'));
$info_provided = $calendly_payload['questions_and_answers'][$found_key_info]['answer'];

$scheduled_date = $calendly_payload['scheduled_event']['start_time'];


$data = [
    'contact_email' => $calendly_payload['email'],
    'contact_first_name' => $calendly_payload['first_name'],
    'contact_last_name' => $calendly_payload['last_name'],
    'organisation_name' => $org_name,
    'scheduled_date' => $scheduled_date,
    'info_provided' => $info_provided,
    'source_form' => 'Calendly Prospect',
];

log_data('--------------');
log_data('Creating deal for');
log_data(print_r($data, 1));
log_data('--------------');

$data_controller = new WorkplaceVTController($data);

$success = $data_controller->create_calendly_prospect();


send_response([
    'status' => $success ? 'success' : 'fail',
]);
exit;

function log_data($var)
{
    if (is_array($var) || is_object($var)) {
        log_debug('Calendly event', ['data' => $var]);
    } else {
        log_debug('Calendly event: ' . $var);
    }
}
