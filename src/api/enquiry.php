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
    log_info('Enquiry request started', [
        'endpoint' => 'enquiry',
        'method' => $method,
        'service_type' => $data['service_type'] ?? 'unknown',
        'organization_name' => $data['organisation_name'] ?? $data['workplace_name_other'] ?? $data['school_name_other'] ?? 'unknown',
        'contact_email' => $data['contact_email'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);

    log_debug('Enquiry data received', ['data' => $data]);

    try {
        $controllers = [
            'School'      => SchoolVTController::class,
            'Workplace'   => WorkplaceVTController::class,
            'Early Years' => EarlyYearsVTController::class,
            'Imperfects'  => ImperfectsVTController::class,
        ];

        $service_type = $data['service_type'] ?? null;
        $controller_class = $controllers[$service_type] ?? GeneralVTController::class;

        log_debug('Creating controller for enquiry', [
            'controller' => $controller_class,
            'service_type' => $service_type ?? 'not provided',
        ]);

        $data_controller = new $controller_class($data);

        log_info('Calling submit_enquiry() to process enquiry');
        $success = $data_controller->submit_enquiry();

        if ($success) {
            log_info('Enquiry processed successfully', [
                'service_type' => $data['service_type'],
                'organization' => $data['organisation_name'] ?? $data['workplace_name_other'] ?? $data['school_name_other'] ?? 'unknown',
                'status' => 'success',
            ]);
        } else {
            log_error('Enquiry processing failed', [
                'service_type' => $data['service_type'],
                'organization' => $data['organisation_name'] ?? $data['workplace_name_other'] ?? $data['school_name_other'] ?? 'unknown',
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
            'endpoint' => 'enquiry',
            'service_type' => $data['service_type'] ?? 'unknown',
            'organization' => $data['organisation_name'] ?? $data['workplace_name_other'] ?? $data['school_name_other'] ?? 'unknown',
        ]);

        send_response([
            'status' => 'fail',
            'message' => 'Error processing enquiry: ' . $e->getMessage(),
        ]);
        exit;
    }

}
