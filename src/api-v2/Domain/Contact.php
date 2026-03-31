<?php

declare(strict_types=1);

namespace ApiV2\Domain;

class Contact
{
    public function __construct(
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $phone = null,
        public readonly ?string $orgPhone = null,
        public readonly ?string $jobTitle = null,
        public readonly ?string $type = null,
        public readonly ?string $newsletter = null,
    ) {
        if (trim($this->email) === '') {
            throw new \InvalidArgumentException('Contact email must not be empty');
        }
    }

    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    /**
     * Build a Contact from raw form submission data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromFormData(array $data): self
    {
        return new self(
            email: (string) ($data['contact_email'] ?? ''),
            firstName: (string) ($data['contact_first_name'] ?? ''),
            lastName: (string) ($data['contact_last_name'] ?? ''),
            phone: !empty($data['contact_phone']) ? (string) $data['contact_phone'] : null,
            orgPhone: !empty($data['org_phone']) ? (string) $data['org_phone'] : null,
            jobTitle: !empty($data['job_title']) ? (string) $data['job_title'] : null,
            type: !empty($data['contact_type']) ? (string) $data['contact_type'] : null,
            newsletter: !empty($data['contact_newsletter']) ? (string) $data['contact_newsletter'] : null,
        );
    }
}
