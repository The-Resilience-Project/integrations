<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/api_helpers.php';
require dirname(__FILE__).'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();
$data = get_request_data();

if ($method === 'GET') {
    $data_controller = new SchoolVTController($data);
    $account_no = $data['school_account_no'];
    $for_2026 = !empty($data['for_2026']) ? true : false;

    $form_data = $data_controller->get_info_for_curric_ordering_form($account_no, $for_2026);


    // Then, respond with a success
    send_response([
        'data' => $form_data,
    ]);
    exit;

}
