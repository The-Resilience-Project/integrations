<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/api_helpers.php';
require dirname(__FILE__).'/../init.php';
require dirname(__FILE__).'/classes/requests/ContactInfo.php';
require dirname(__FILE__).'/classes/requests/EnquiryRequest.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();
$data = get_request_data();

if ($method === 'POST') {
    try {
        $request = EnquiryRequest::fromFormData($data);

        log_info('Enquiry request started', [
            'endpoint' => 'enquiry',
            'service_type' => $request->serviceType,
            'organisation' => $request->organisationDisplayName(),
            'contact_email' => $request->contact->email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        $controller_class = $request->controllerClass();
        log_debug('Creating controller', ['controller' => $controller_class]);

        $data_controller = new $controller_class($request->toArray());
        $success = $data_controller->submit_enquiry();

        if ($success) {
            log_info('Enquiry processed successfully', [
                'service_type' => $request->serviceType,
                'organisation' => $request->organisationDisplayName(),
            ]);
        } else {
            log_error('Enquiry processing failed', [
                'service_type' => $request->serviceType,
                'organisation' => $request->organisationDisplayName(),
            ]);
        }

        send_response([
            'status' => $success ? 'success' : 'fail',
        ]);
        exit;

    } catch (InvalidArgumentException $e) {
        log_warning('Invalid enquiry request', [
            'error' => $e->getMessage(),
        ]);

        send_response([
            'status' => 'fail',
            'message' => 'Invalid request: ' . $e->getMessage(),
        ], 400);
        exit;

    } catch (Exception $e) {
        log_exception($e, [
            'endpoint' => 'enquiry',
            'service_type' => $data['service_type'] ?? 'unknown',
        ]);

        send_response([
            'status' => 'fail',
            'message' => 'Error processing enquiry: ' . $e->getMessage(),
        ]);
        exit;
    }
}
