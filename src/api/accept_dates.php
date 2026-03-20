<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/classes/controllers/school.php';
require dirname(__FILE__).'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();
$data = get_request_data();

if ($method === 'POST') {

    $data_controller = new SchoolVTController($data);

    $success = $data_controller->accept_dates();


    send_response([
        'status' => $success ? 'success' : 'fail',
    ]);
    exit;

}
