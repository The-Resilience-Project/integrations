<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/api_helpers.php';
require dirname(__FILE__).'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();
$data = get_request_data();

// POST request
// Store some data or something
if ($method === 'POST') {
    $data_controller = new SchoolVTController($data);

    $success = $data_controller->create_culture_assessment();


    // Then, respond with a success
    send_response([
        'status' => $success ? 'success' : 'fail',
    ]);
    exit;

}
