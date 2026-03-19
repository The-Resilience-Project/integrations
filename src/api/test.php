<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/api_helpers.php';
require dirname(__FILE__).'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();
$data = get_request_data();

if ($method === 'POST') {
    error_log(print_r($data, 1));

    $success = true;

    // Then, respond with a success
    send_response([
        'status' => $success ? 'success' : 'failed',
        'message' => 'Test endpoint received data',
        'php_version' => PHP_VERSION,
    ]);
    exit;

}
