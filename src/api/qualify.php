<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/api_helpers_dev.php';
require dirname(__FILE__).'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();
$data = get_request_data();

if ($method === 'POST') {
    // error_log($data["interested_programs"]);
    // error_log(gettype($data["interested_programs"]));
    $data_controller = new WorkplaceVTController($data);

    $success = $data_controller->submit_qualifier();


    // Then, respond with a success
    send_response([
        'status' => $success ? 'success' : 'fail',
    ]);
    exit;

}
