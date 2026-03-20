<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/api_helpers.php';
require dirname(__FILE__).'/../init.php';
require dirname(__FILE__).'/classes/requests/ContactInfo.php';
require dirname(__FILE__).'/classes/requests/EnquiryRequest.php';
require dirname(__FILE__).'/classes/requests/EnquiryResult.php';
require dirname(__FILE__).'/classes/usecases/SubmitEnquiry.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();
$data = get_request_data();

if ($method === 'POST') {
    try {
        $request = EnquiryRequest::fromFormData($data);
    } catch (InvalidArgumentException $e) {
        log_warning('Invalid enquiry request', ['error' => $e->getMessage()]);
        send_response(['status' => 'fail', 'message' => 'Invalid request: ' . $e->getMessage()], 400);
        exit;
    }

    $useCase = new SubmitEnquiry();
    $result = $useCase->execute($request);

    send_response($result->toResponse(), $result->success ? 200 : 500);
    exit;
}
