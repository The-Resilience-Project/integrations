<?php

class EnquiryResult
{
    public readonly bool $success;
    public readonly string $serviceType;
    public readonly string $organisation;
    public readonly string $contactEmail;
    public readonly ?string $errorMessage;

    public function __construct(
        bool $success,
        string $serviceType,
        string $organisation,
        string $contactEmail,
        ?string $errorMessage = null,
    ) {
        $this->success = $success;
        $this->serviceType = $serviceType;
        $this->organisation = $organisation;
        $this->contactEmail = $contactEmail;
        $this->errorMessage = $errorMessage;
    }

    public function status(): string
    {
        return $this->success ? 'success' : 'fail';
    }

    public function toResponse(): array
    {
        $response = ['status' => $this->status()];
        if (!$this->success && $this->errorMessage) {
            $response['message'] = $this->errorMessage;
        }
        return $response;
    }
}
