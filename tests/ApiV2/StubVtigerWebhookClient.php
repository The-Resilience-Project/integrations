<?php

use ApiV2\Infrastructure\VtigerWebhookClientInterface;

/**
 * In-memory stub for VtigerWebhookClientInterface.
 *
 * Records all calls and returns configured responses per endpoint.
 */
class StubVtigerWebhookClient implements VtigerWebhookClientInterface
{
    /** @var array<string, object> Endpoint name → response to return */
    private array $responses = [];

    /** @var array<int, array{endpoint: string, body: array<string, mixed>, get: bool}> */
    private array $calls = [];

    /**
     * Set the response for a given endpoint.
     *
     * @param object $response The decoded JSON response to return
     */
    public function setResponse(string $endpoint, object $response): void
    {
        $this->responses[$endpoint] = $response;
    }

    public function post(string $endpoint, array $requestBody, bool $get = false): ?object
    {
        $this->calls[] = ['endpoint' => $endpoint, 'body' => $requestBody, 'get' => $get];

        return $this->responses[$endpoint] ?? (object) ['result' => []];
    }

    public function postWithLineItems(string $endpoint, array $requestBody, array $lineItems): ?object
    {
        $this->calls[] = ['endpoint' => $endpoint, 'body' => $requestBody, 'get' => false];

        return $this->responses[$endpoint] ?? (object) ['result' => []];
    }

    /**
     * Check if a specific endpoint was called.
     */
    public function wasCalled(string $endpoint): bool
    {
        foreach ($this->calls as $call) {
            if ($call['endpoint'] === $endpoint) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all calls to a specific endpoint.
     *
     * @return array<int, array{endpoint: string, body: array<string, mixed>, get: bool}>
     */
    public function getCallsTo(string $endpoint): array
    {
        return array_values(array_filter($this->calls, fn ($c) => $c['endpoint'] === $endpoint));
    }

    /**
     * Get the request body of the first call to an endpoint.
     *
     * @return array<string, mixed>|null
     */
    public function getFirstCallBody(string $endpoint): ?array
    {
        $calls = $this->getCallsTo($endpoint);

        return $calls[0]['body'] ?? null;
    }

    /**
     * Get all recorded calls.
     *
     * @return array<int, array{endpoint: string, body: array<string, mixed>, get: bool}>
     */
    public function getAllCalls(): array
    {
        return $this->calls;
    }

    /**
     * Get the sequence of endpoint names called.
     *
     * @return array<int, string>
     */
    public function getCallSequence(): array
    {
        return array_map(fn ($c) => $c['endpoint'], $this->calls);
    }

    /**
     * Helper to create a standard capture customer info response.
     */
    public static function makeCaptureResponse(
        string $contactId = '4x100',
        string $accountId = '3x200',
        string $assignedUserId = '19x1',
        string $formsCompleted = '',
    ): object {
        return (object) [
            'result' => [
                (object) [
                    'id' => $contactId,
                    'account_id' => $accountId,
                    'assigned_user_id' => $assignedUserId,
                    'cf_contacts_formscompleted' => $formsCompleted,
                ],
            ],
        ];
    }

    /**
     * Helper to create a standard org details response.
     */
    public static function makeOrgDetailsResponse(
        string $accountname = 'Test School',
        string $assignedUserId = '19x1',
        string $salesEvents = '',
        string $confirmationStatus2026 = '',
        string $yearsWithTrp = '',
    ): object {
        return (object) [
            'result' => [
                (object) [
                    'accountname' => $accountname,
                    'assigned_user_id' => $assignedUserId,
                    'cf_accounts_2025salesevents' => $salesEvents,
                    'cf_accounts_freetravel' => '',
                    'cf_accounts_yearswithtrp' => $yearsWithTrp,
                    'cf_accounts_2024inspire' => '',
                    'cf_accounts_2025inspire' => '',
                    'cf_accounts_2025confirmationstatus' => '',
                    'cf_accounts_2024confirmationstatus' => '',
                    'cf_accounts_2026confirmationstatus' => $confirmationStatus2026,
                ],
            ],
        ];
    }

    /**
     * Helper to create a standard deal response.
     */
    public static function makeDealResponse(
        string $dealId = '2x300',
        string $salesStage = 'New',
        string $firstInfoSessionDate = '',
    ): object {
        return (object) [
            'result' => [
                (object) [
                    'id' => $dealId,
                    'sales_stage' => $salesStage,
                    'cf_potentials_firstinfosessiondate' => $firstInfoSessionDate,
                    'description' => '',
                    'cf_potentials_billingnote' => '',
                ],
            ],
        ];
    }

    /**
     * Helper to create a standard update org response.
     */
    public static function makeUpdateOrgResponse(string $assignedUserId = '19x8'): object
    {
        return (object) [
            'result' => [
                (object) [
                    'assigned_user_id' => $assignedUserId,
                ],
            ],
        ];
    }
}
