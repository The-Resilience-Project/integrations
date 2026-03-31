<?php

declare(strict_types=1);

namespace ApiV2\Infrastructure;

class VtigerWebhookClient implements VtigerWebhookClientInterface
{
    /** @var array<string, string> */
    private array $tokens;

    private string $baseUrl;

    /**
     * @param string               $baseUrl  Vtiger VTAP webhook base URL
     * @param array<string, string> $tokens  Endpoint name => auth token
     */
    public function __construct(string $baseUrl, array $tokens)
    {
        $this->baseUrl = rtrim($baseUrl, '/').'/';
        $this->tokens = $tokens;
    }

    public function post(string $endpoint, array $requestBody, bool $get = false): ?object
    {
        $headers = [
            'token: '.$this->getToken($endpoint),
            'Content-Type: application/json',
        ];

        $ch = curl_init($this->baseUrl.$endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $get ? 'GET' : 'POST',
            CURLOPT_POSTFIELDS => json_encode($requestBody),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            log_error('cURL request failed', [
                'error' => $error,
                'endpoint' => $endpoint,
            ]);

            return null;
        }

        curl_close($ch);

        return json_decode($response);
    }

    public function postWithLineItems(string $endpoint, array $requestBody, array $lineItems): ?object
    {
        $requestString = '';
        foreach ($requestBody as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $arrayItem) {
                    $requestString .= $key.'[]='.$arrayItem.'&';
                }
            } else {
                $requestString .= $key.'='.$value.'&';
            }
        }
        $requestString .= 'lineItems='.json_encode($lineItems);

        $headers = [
            'token: '.$this->getToken($endpoint),
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $ch = curl_init($this->baseUrl.$endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $requestString,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            log_error('cURL request failed', [
                'error' => $error,
                'endpoint' => $endpoint,
            ]);

            return null;
        }

        curl_close($ch);

        return json_decode($response);
    }

    private function getToken(string $endpoint): string
    {
        if (!isset($this->tokens[$endpoint])) {
            throw new \RuntimeException("No token configured for webhook endpoint: {$endpoint}");
        }

        return $this->tokens[$endpoint];
    }
}
