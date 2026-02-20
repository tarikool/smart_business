<?php

namespace App\DTOs;

use App\Enums\ContactType;

class ContactDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly string $phoneNumber,
        public readonly ContactType $contactType,
        public readonly ?string $address = null
    ) {}

    public static function fromArray($userId, $data): self
    {
        return new self(
            userId: $userId,
            name: $data['name'],
            phoneNumber: $data['phone_number'],
            contactType: ContactType::from($data['contact_type']),
            address: $data['address'] ?? null
        );
    }
}
