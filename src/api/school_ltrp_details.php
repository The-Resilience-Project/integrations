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
    log_info('School LTRP details request started', [
        'endpoint' => 'school_ltrp_details',
        'method' => $method,
        'org_id' => $data['org_id'] ?? 'missing',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);

    log_debug('School LTRP request data received', ['data' => $data]);

    try {
        log_debug('Creating SchoolVTController instance');
        $data_controller = new SchoolVTController($data);

        $org_id = $data['org_id'] ?? null;

        if (empty($org_id)) {
            log_error('Missing org_id parameter', ['data' => $data]);
            send_response([
                'status' => 'fail',
                'message' => 'Missing required parameter: org_id',
            ]);
            exit;
        }

        log_info('Calling get_info_for_ltrp_form()', ['org_id' => $org_id]);
        $form_data = $data_controller->get_info_for_ltrp_form($org_id);

        if (isset($form_data['error']) && $form_data['error'] === true) {
            log_warning('School LTRP details not found', ['org_id' => $org_id]);
        } else {
            log_info('School LTRP details retrieved successfully', [
                'org_id' => $org_id,
                'school_name' => $form_data['name'] ?? 'unknown',
                'has_ltrp' => !empty($form_data['ltrp']),
                'has_ca' => !empty($form_data['ca']),
            ]);
        }

        send_response([
            'data' => $form_data,
        ]);
        exit;

    } catch (Exception $e) {
        log_exception($e, [
            'endpoint' => 'school_ltrp_details',
            'org_id' => $data['org_id'] ?? 'unknown',
        ]);

        send_response([
            'status' => 'fail',
            'message' => 'Error retrieving LTRP details: ' . $e->getMessage(),
        ]);
        exit;
    }

} else {
    log_error('Invalid request method', [
        'method' => $method,
        'expected' => 'GET',
        'endpoint' => 'school_ltrp_details',
    ]);

    send_response([
        'status' => 'fail',
        'message' => 'Method not allowed. Use GET.',
    ], 405);
    exit;
}
