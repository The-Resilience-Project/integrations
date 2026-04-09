<?php

require dirname(__FILE__).'/../../../api/utils.php';
require dirname(__FILE__).'/../../../init.php';
require dirname(__FILE__).'/../../../../vendor/autoload.php';

use ApiV2\Application\Schools\SubmitConfirmationHandler;
use ApiV2\Domain\ConfirmationRequest;
use ApiV2\Infrastructure\VtigerWebhookClient;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();

if ($method === 'OPTIONS') {
    send_response([], 204);
    exit;
}

if ($method === 'POST') {
    $data = get_request_data();

    $request = ConfirmationRequest::fromFormData($data);

    log_info('v2 School confirmation request started', [
        'endpoint' => 'v2/schools/confirm',
        'contact_email' => $request->contactEmail,
        'school_account_no' => $request->schoolAccountNo ?? 'unknown',
    ]);

    try {
        $tokens = require dirname(__FILE__).'/../../Config/webhook_tokens.php';
        $client = new VtigerWebhookClient(
            'https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/',
            $tokens,
        );

        $handler = new SubmitConfirmationHandler($client);
        $result = $handler->handle($request);

        log_info('v2 School confirmation processed', ['status' => $result ? 'success' : 'fail']);
        send_response(['status' => $result ? 'success' : 'fail']);
    } catch (Exception $e) {
        log_exception($e, ['endpoint' => 'v2/schools/confirm']);
        send_response([
            'status' => 'fail',
            'message' => 'Error processing confirmation request: '.$e->getMessage(),
        ]);
    }
}
