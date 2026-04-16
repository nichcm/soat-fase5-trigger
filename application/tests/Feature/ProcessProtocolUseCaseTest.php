<?php

namespace Tests\Feature;

use App\Application\UseCase\ProcessProtocol\ProcessProtocolInput;
use App\Application\UseCase\ProcessProtocol\ProcessProtocolUseCase;
use App\Domain\Entity\AnalysisResponse;
use App\Domain\Interface\AnalysisGatewayInterface;
use App\Domain\Interface\TriggerRepositoryInterface;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProcessProtocolUseCaseTest extends TestCase
{
    private ProcessProtocolInput $input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->input = ProcessProtocolInput::fromArray([
            'protocol'      => '550e8400-e29b-41d4-a716-446655440000',
            'file_url'      => 'http://minio:9000/diagrams/abc123.pdf',
            'file_name'     => 'abc123.pdf',
            'file_mimetype' => 'application/pdf',
            'file_size'     => '204800',
            'original_name' => 'meu-diagrama.pdf',
            'hashed_name'   => 'abc123.pdf',
        ]);
    }

    public function test_cria_trigger_no_banco(): void
    {
        $gateway = Mockery::mock(AnalysisGatewayInterface::class);
        $gateway->shouldReceive('sendForAnalysis')->once();

        $useCase = new ProcessProtocolUseCase(
            $this->app->make(TriggerRepositoryInterface::class),
            $gateway,
        );

        $useCase->execute($this->input);

        $this->assertDatabaseHas('triggers', [
            'protocol_uuid' => '550e8400-e29b-41d4-a716-446655440000',
        ]);
    }

    public function test_salva_status_recebido_ao_criar_trigger(): void
    {
        $gateway = Mockery::mock(AnalysisGatewayInterface::class);
        $gateway->shouldReceive('sendForAnalysis')->once();

        $useCase = new ProcessProtocolUseCase(
            $this->app->make(TriggerRepositoryInterface::class),
            $gateway,
        );

        $useCase->execute($this->input);

        $this->assertDatabaseHas('analysis_responses', [
            'status' => AnalysisResponse::STATUS_RECEBIDO,
        ]);
    }

    public function test_salva_status_em_processamento_quando_gateway_tem_sucesso(): void
    {
        $gateway = Mockery::mock(AnalysisGatewayInterface::class);
        $gateway->shouldReceive('sendForAnalysis')->once();

        $useCase = new ProcessProtocolUseCase(
            $this->app->make(TriggerRepositoryInterface::class),
            $gateway,
        );

        $useCase->execute($this->input);

        $this->assertDatabaseHas('analysis_responses', [
            'status' => AnalysisResponse::STATUS_EM_PROCESSAMENTO,
        ]);
    }

    public function test_salva_status_erro_processamento_quando_gateway_falha(): void
    {
        $gateway = Mockery::mock(AnalysisGatewayInterface::class);
        $gateway->shouldReceive('sendForAnalysis')
            ->once()
            ->andThrow(new RuntimeException('Serviço de IA indisponível'));

        $useCase = new ProcessProtocolUseCase(
            $this->app->make(TriggerRepositoryInterface::class),
            $gateway,
        );

        $useCase->execute($this->input);

        $this->assertDatabaseHas('analysis_responses', [
            'status' => AnalysisResponse::STATUS_ERRO_PROCESSAMENTO,
        ]);
    }

    public function test_nao_salva_em_processamento_quando_gateway_falha(): void
    {
        $gateway = Mockery::mock(AnalysisGatewayInterface::class);
        $gateway->shouldReceive('sendForAnalysis')
            ->once()
            ->andThrow(new RuntimeException('Serviço de IA indisponível'));

        $useCase = new ProcessProtocolUseCase(
            $this->app->make(TriggerRepositoryInterface::class),
            $gateway,
        );

        $useCase->execute($this->input);

        $this->assertDatabaseMissing('analysis_responses', [
            'status' => AnalysisResponse::STATUS_EM_PROCESSAMENTO,
        ]);
    }

    public function test_salva_payload_completo_no_trigger(): void
    {
        $gateway = Mockery::mock(AnalysisGatewayInterface::class);
        $gateway->shouldReceive('sendForAnalysis')->once();

        $useCase = new ProcessProtocolUseCase(
            $this->app->make(TriggerRepositoryInterface::class),
            $gateway,
        );

        $useCase->execute($this->input);

        $trigger = \App\Infrastructure\Repository\Models\TriggerModel
            ::where('protocol_uuid', '550e8400-e29b-41d4-a716-446655440000')
            ->first();

        $this->assertNotNull($trigger);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $trigger->payload['protocol_uuid']);
        $this->assertArrayHasKey('file', $trigger->payload);
        $this->assertSame('application/pdf', $trigger->payload['file']['mimetype']);
    }
}
