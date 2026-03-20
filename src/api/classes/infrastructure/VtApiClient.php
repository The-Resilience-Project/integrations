<?php

interface VtApiClient
{
    public function request(string $endpoint, string $token, array $body, bool $get = false): ?object;

    public function requestWithLineItems(string $endpoint, string $token, array $body, array $lineItems): ?object;
}
