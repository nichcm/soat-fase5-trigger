<?php

namespace Tests\Unit;

use App\Domain\Entity\Trigger;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TriggerEntityTest extends TestCase
{
    public function test_pode_ser_instanciado_com_todos_os_campos(): void
    {
        $createdAt = new DateTimeImmutable('2026-04-16 10:00:00');
        $payload   = ['protocol_uuid' => 'uuid-123', 'file' => ['url' => 'http://...']];

        $trigger = new Trigger(
            id:           1,
            protocolUuid: 'uuid-123',
            payload:      $payload,
            createdAt:    $createdAt,
        );

        $this->assertSame(1, $trigger->id);
        $this->assertSame('uuid-123', $trigger->protocolUuid);
        $this->assertSame($payload, $trigger->payload);
        $this->assertSame($createdAt, $trigger->createdAt);
    }

    public function test_id_pode_ser_nulo(): void
    {
        $trigger = new Trigger(
            id:           null,
            protocolUuid: 'uuid-123',
            payload:      [],
            createdAt:    new DateTimeImmutable(),
        );

        $this->assertNull($trigger->id);
    }

    public function test_payload_e_um_array(): void
    {
        $payload = ['protocol_uuid' => 'abc', 'file' => ['name' => 'diagram.pdf']];

        $trigger = new Trigger(
            id:           1,
            protocolUuid: 'abc',
            payload:      $payload,
            createdAt:    new DateTimeImmutable(),
        );

        $this->assertIsArray($trigger->payload);
        $this->assertArrayHasKey('protocol_uuid', $trigger->payload);
        $this->assertArrayHasKey('file', $trigger->payload);
    }

    public function test_created_at_e_date_time_immutable(): void
    {
        $trigger = new Trigger(
            id:           1,
            protocolUuid: 'uuid-123',
            payload:      [],
            createdAt:    new DateTimeImmutable('2026-01-01'),
        );

        $this->assertInstanceOf(DateTimeImmutable::class, $trigger->createdAt);
        $this->assertSame('2026-01-01', $trigger->createdAt->format('Y-m-d'));
    }
}
