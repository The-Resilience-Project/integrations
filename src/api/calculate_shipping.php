<?php

require dirname(__FILE__).'/utils.php';
require dirname(__FILE__).'/../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$method = get_method();
$data = get_request_data();

// POST request
// Store some data or something
if ($method === 'POST') {
    $request_header = ['Account-Number: 10168525', 'Content-Type: application/json'];
    $username = '8ec1498b-1c04-485a-b642-e7d9a1d4df26';
    $password = 'yX4kEYsbJoikUVqE6SBR';
    // "Authorisation: Basic OGVjMTQ5OGItMWMwNC00ODVhLWI2NDItZTdkOWExZDRkZjI2OnlYNGtFWXNiSm9pa1VWcUU2U0JS"
    $consignment = $data['consignment'];

    if (count($consignment) == 0) {
        send_response([
            'price' => 0,
        ]);
    }


    $request_body = [
        'shipments' => [
            'from' => [
                'suburb' => 'SEAFORD',
                'state' => 'VIC',
                'postcode' => '3198',
            ],
            'to' => [
                'suburb' => $data['destination_suburb'],
                'state' => $data['destination_state'],
                'postcode' => $data['destination_postcode'],
            ],
            'items' => $consignment,
        ],
    ];


    $request_handle = curl_init('https://digitalapi.auspost.com.au/shipping/v1/prices/shipments');
    curl_setopt_array($request_handle, [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($request_body),
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => $request_header,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $username .':'.$password,
    ]);
    $response = curl_exec($request_handle);
    $json_response = json_decode($response, true);
    curl_close($request_handle);
    if (!isset($json_response['shipments'])) {
        log_error('Australia Post shipping API error', [
            'request_body' => $request_body,
            'response' => $json_response,
        ]);
        send_response([
            'error' => true,
        ]);
    }

    $price = $json_response['shipments'][0]['shipment_summary']['total_cost_ex_gst'];


    // Then, respond with a success
    send_response([
        'price' => $price,
    ]);
    exit;

}
