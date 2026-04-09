<?php

require dirname(__FILE__).'/../../../api/utils.php';
require dirname(__FILE__).'/../../../init.php';
require dirname(__FILE__).'/../../../../vendor/autoload.php';

use ApiV2\Application\Schools\SubmitPrizePackHandler;
use ApiV2\Domain\PrizePackRequest;
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
    $request = PrizePackRequest::fromFormData($data);

    log_info('v2 School conference delegate request started', [
        'endpoint' => 'v2/schools/conference-delegate',
        'contact_email' => $request->contactEmail,
        'school_account_no' => $request->schoolAccountNo ?? 'unknown',
    ]);

    try {
        $tokens = require dirname(__FILE__).'/../../Config/webhook_tokens.php';
        $client = new VtigerWebhookClient(
            'https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/',
            $tokens,
        );

        $handler = new SubmitPrizePackHandler($client, 'Conference Delegate 2026');
        $result = $handler->handle($request);

        log_info('v2 School conference delegate processed', ['status' => $result ? 'success' : 'fail']);
        send_response(['status' => $result ? 'success' : 'fail']);
    } catch (Exception $e) {
        log_exception($e, ['endpoint' => 'v2/schools/conference-delegate']);
        send_response([
            'status' => 'fail',
            'message' => 'Error processing school conference delegate: '.$e->getMessage(),
        ]);
    }
}
