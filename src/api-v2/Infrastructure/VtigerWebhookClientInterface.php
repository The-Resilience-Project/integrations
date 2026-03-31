<?php

declare(strict_types=1);

namespace ApiV2\Infrastructure;

interface VtigerWebhookClientInterface
{
    /**
     * POST (or GET) a JSON payload to a Vtiger VTAP webhook endpoint.
     *
     * @param  string               $endpoint     Webhook name (e.g. 'captureCustomerInfo')
     * @param  array<string, mixed> $requestBody  Payload to JSON-encode
     * @param  bool                 $get          Use GET instead of POST
     * @return object|null          Decoded JSON response
     */
    public function post(string $endpoint, array $requestBody, bool $get = false): ?object;

    /**
     * POST with form-encoded body + JSON line items (for quotes/invoices).
     *
     * @param  string               $endpoint     Webhook name
     * @param  array<string, mixed> $requestBody  Form fields
     * @param  array<int, mixed>    $lineItems    Line items array (JSON-encoded into 'lineItems' param)
     * @return object|null          Decoded JSON response
     */
    public function postWithLineItems(string $endpoint, array $requestBody, array $lineItems): ?object;
}
