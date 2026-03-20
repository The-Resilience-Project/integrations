<?php

class ContactInfo
{
    public readonly string $email;
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly ?string $phone;
    public readonly ?string $orgPhone;
    public readonly ?string $jobTitle;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?string $orgPhone = null,
        ?string $jobTitle = null,
    ) {
        if (empty($email)) {
            throw new InvalidArgumentException('Contact email is required');
        }
        if (empty($firstName)) {
            throw new InvalidArgumentException('Contact first name is required');
        }
        if (empty($lastName)) {
            throw new InvalidArgumentException('Contact last name is required');
        }

        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone ?: null;
        $this->orgPhone = $orgPhone ?: null;
        $this->jobTitle = $jobTitle ?: null;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['contact_email'] ?? '',
            $data['contact_first_name'] ?? '',
            $data['contact_last_name'] ?? '',
            $data['contact_phone'] ?? null,
            $data['org_phone'] ?? null,
            $data['job_title'] ?? null,
        );
    }

    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
