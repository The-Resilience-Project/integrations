<?php

use PHPUnit\Framework\TestCase;

class ContactInfoTest extends TestCase
{
    public function test_creates_with_required_fields(): void
    {
        $contact = new ContactInfo('test@example.com', 'Jane', 'Smith');

        $this->assertEquals('test@example.com', $contact->email);
        $this->assertEquals('Jane', $contact->firstName);
        $this->assertEquals('Smith', $contact->lastName);
        $this->assertNull($contact->phone);
        $this->assertNull($contact->orgPhone);
        $this->assertNull($contact->jobTitle);
    }

    public function test_creates_with_all_fields(): void
    {
        $contact = new ContactInfo(
            'test@example.com',
            'Jane',
            'Smith',
            '0412345678',
            '0398765432',
            'Principal',
        );

        $this->assertEquals('0412345678', $contact->phone);
        $this->assertEquals('0398765432', $contact->orgPhone);
        $this->assertEquals('Principal', $contact->jobTitle);
    }

    public function test_throws_on_empty_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email');

        new ContactInfo('', 'Jane', 'Smith');
    }

    public function test_throws_on_empty_first_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('first name');

        new ContactInfo('test@example.com', '', 'Smith');
    }

    public function test_throws_on_empty_last_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('last name');

        new ContactInfo('test@example.com', 'Jane', '');
    }

    public function test_full_name(): void
    {
        $contact = new ContactInfo('test@example.com', 'Jane', 'Smith');

        $this->assertEquals('Jane Smith', $contact->fullName());
    }

    public function test_from_array(): void
    {
        $contact = ContactInfo::fromArray([
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
            'contact_phone' => '0412345678',
        ]);

        $this->assertEquals('test@example.com', $contact->email);
        $this->assertEquals('Jane', $contact->firstName);
        $this->assertEquals('Smith', $contact->lastName);
        $this->assertEquals('0412345678', $contact->phone);
        $this->assertNull($contact->orgPhone);
    }

    public function test_from_array_throws_on_missing_email(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ContactInfo::fromArray([
            'contact_first_name' => 'Jane',
            'contact_last_name' => 'Smith',
        ]);
    }

    public function test_empty_string_phone_becomes_null(): void
    {
        $contact = new ContactInfo('test@example.com', 'Jane', 'Smith', '', '');

        $this->assertNull($contact->phone);
        $this->assertNull($contact->orgPhone);
    }
}
