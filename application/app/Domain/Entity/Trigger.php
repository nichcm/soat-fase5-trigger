<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class Trigger
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $protocolUuid,
        public readonly array $payload,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
