<?php

class CurlVtApiClient implements VtApiClient
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function request(string $endpoint, string $token, array $body, bool $get = false): ?object
    {
        $request_header = [
            'token: ' . $token,
            'Content-Type: application/json',
        ];

        $request_method = $get ? 'GET' : 'POST';

        $request_handle = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($request_handle, [
            CURLOPT_CUSTOMREQUEST => $request_method,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request_header,
        ]);

        $response = curl_exec($request_handle);
        if ($response === false) {
            $curl_error = curl_error($request_handle);
            log_error('cURL request failed', [
                'error' => $curl_error,
                'endpoint' => $endpoint,
            ]);
        }
        $json_response = json_decode($response);
        curl_close($request_handle);
        return $json_response;
    }

    public function requestWithLineItems(string $endpoint, string $token, array $body, array $lineItems): ?object
    {
        $request_string = '';
        foreach ($body as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $array_item) {
                    $request_string .= $key . '[]=' . $array_item . '&';
                }
            } else {
                $request_string .= $key . '=' . $value . '&';
            }
        }
        $request_string .= 'lineItems=' . json_encode($lineItems);

        $request_header = [
            'token: ' . $token,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $request_handle = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($request_handle, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $request_string,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request_header,
        ]);

        $response = curl_exec($request_handle);
        $json_response = json_decode($response);
        curl_close($request_handle);
        return $json_response;
    }
}
