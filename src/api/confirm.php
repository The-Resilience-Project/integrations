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
    log_info('Program confirmation request started', [
        'endpoint' => 'confirm',
        'method' => $method,
        'service_type' => $data['service_type'] ?? 'unknown',
        'school_name' => $data['school_name_other'] ?? $data['school_account_no'] ?? 'unknown',
        'contact_email' => $data['contact_email'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);

    log_debug('Program confirmation data received', ['data' => $data]);

    try {
        if ($data['service_type'] === 'School') {
            log_debug('Creating SchoolVTController for program confirmation');
            $data_controller = new SchoolVTController($data);
        } elseif ($data['service_type'] === 'Early Years') {
            log_debug('Creating EarlyYearsVTController for program confirmation');
            $data_controller = new EarlyYearsVTController($data);
        } else {
            log_error('Invalid service type for program confirmation', [
                'service_type' => $data['service_type'] ?? 'not provided',
            ]);
            send_response([
                'status' => 'fail',
                'message' => 'Invalid service type',
            ]);
            exit;
        }

        log_info('Calling confirm_program() to process confirmation');
        $success = $data_controller->confirm_program();

        if ($success) {
            log_info('Program confirmation processed successfully', [
                'service_type' => $data['service_type'],
                'school_name' => $data['school_name_other'] ?? $data['school_account_no'] ?? 'unknown',
                'status' => 'success',
            ]);
        } else {
            log_error('Program confirmation processing failed', [
                'service_type' => $data['service_type'],
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
            'endpoint' => 'confirm',
            'service_type' => $data['service_type'] ?? 'unknown',
            'school_name' => $data['school_name_other'] ?? $data['school_account_no'] ?? 'unknown',
        ]);

        send_response([
            'status' => 'fail',
            'message' => 'Error processing confirmation: ' . $e->getMessage(),
        ]);
        exit;
    }

}
