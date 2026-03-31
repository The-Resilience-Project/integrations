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
    log_info('Curriculum order request started', [
        'endpoint' => 'order_resources',
        'method' => $method,
        'school_name' => $data['school_name_other'] ?? $data['school_account_no'] ?? 'unknown',
        'contact_email' => $data['contact_email'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);

    log_debug('Curriculum ordering data received', ['data' => $data]);

    try {
        log_debug('Creating SchoolVTController instance');
        $data_controller = new SchoolVTController($data);

        log_info('Calling order_resources() to process order');
        $success = $data_controller->order_resources();

        if ($success) {
            log_info('Curriculum order processed successfully', [
                'school_name' => $data['school_name_other'] ?? $data['school_account_no'] ?? 'unknown',
                'status' => 'success',
            ]);
        } else {
            log_error('Curriculum order processing failed', [
                'school_name' => $data['school_name_other'] ?? $data['school_account_no'] ?? 'unknown',
                'status' => 'fail',
            ]);
        }

        // Then, respond with a success
        send_response([
            'status' => $success ? 'success' : 'fail',
        ]);
        exit;

    } catch (Exception $e) {
        log_exception($e, [
            'endpoint' => 'order_resources',
            'school_name' => $data['school_name_other'] ?? $data['school_account_no'] ?? 'unknown',
        ]);

        send_response([
            'status' => 'fail',
            'message' => 'Error processing order: ' . $e->getMessage(),
        ]);
        exit;
    }

}
