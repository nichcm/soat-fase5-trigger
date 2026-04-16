<?php

namespace Tests\Unit;

use App\Domain\Entity\AnalysisResponse;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class AnalysisResponseEntityTest extends TestCase
{
    private array $content = [
        'protocol'        => 'uuid-123',
        'components'      => [['name' => 'Auth']],
        'risks'           => [['description' => 'Risk A']],
        'recommendations' => [['description' => 'Fix A']],
    ];

    public function test_pode_ser_instanciado_com_todos_os_campos(): void
    {
        $receivedAt = new DateTimeImmutable('2026-04-16 10:00:00');

        $response = new AnalysisResponse(
            id:         1,
            protocolId: 42,
            status:     AnalysisResponse::STATUS_SUCESSO,
            content:    $this->content,
            receivedAt: $receivedAt,
        );

        $this->assertSame(1, $response->id);
        $this->assertSame(42, $response->protocolId);
        $this->assertSame(AnalysisResponse::STATUS_SUCESSO, $response->status);
        $this->assertSame($this->content, $response->content);
        $this->assertSame($receivedAt, $response->receivedAt);
    }

    public function test_id_pode_ser_nulo(): void
    {
        $response = new AnalysisResponse(
            id:         null,
            protocolId: 1,
            status:     AnalysisResponse::STATUS_RECEBIDO,
            content:    null,
            receivedAt: new DateTimeImmutable(),
        );

        $this->assertNull($response->id);
    }

    public function test_content_pode_ser_nulo(): void
    {
        $response = new AnalysisResponse(
            id:         1,
            protocolId: 1,
            status:     AnalysisResponse::STATUS_EM_PROCESSAMENTO,
            content:    null,
            receivedAt: new DateTimeImmutable(),
        );

        $this->assertNull($response->content);
    }

    public function test_constantes_de_status_estao_definidas(): void
    {
        $this->assertSame('RECEBIDO',           AnalysisResponse::STATUS_RECEBIDO);
        $this->assertSame('EM_PROCESSAMENTO',   AnalysisResponse::STATUS_EM_PROCESSAMENTO);
        $this->assertSame('ERRO',               AnalysisResponse::STATUS_ERRO);
        $this->assertSame('SUCESSO',            AnalysisResponse::STATUS_SUCESSO);
        $this->assertSame('ERRO_PROCESSAMENTO', AnalysisResponse::STATUS_ERRO_PROCESSAMENTO);
    }

    public function test_statuses_contem_todos_os_valores(): void
    {
        $this->assertCount(5, AnalysisResponse::STATUSES);
        $this->assertContains(AnalysisResponse::STATUS_RECEBIDO,           AnalysisResponse::STATUSES);
        $this->assertContains(AnalysisResponse::STATUS_EM_PROCESSAMENTO,   AnalysisResponse::STATUSES);
        $this->assertContains(AnalysisResponse::STATUS_ERRO,               AnalysisResponse::STATUSES);
        $this->assertContains(AnalysisResponse::STATUS_SUCESSO,            AnalysisResponse::STATUSES);
        $this->assertContains(AnalysisResponse::STATUS_ERRO_PROCESSAMENTO, AnalysisResponse::STATUSES);
    }

    public function test_received_at_e_date_time_immutable(): void
    {
        $response = new AnalysisResponse(
            id:         1,
            protocolId: 1,
            status:     AnalysisResponse::STATUS_SUCESSO,
            content:    null,
            receivedAt: new DateTimeImmutable('2026-04-16'),
        );

        $this->assertInstanceOf(DateTimeImmutable::class, $response->receivedAt);
        $this->assertSame('2026-04-16', $response->receivedAt->format('Y-m-d'));
    }
}
