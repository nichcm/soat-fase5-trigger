<?php

namespace Tests\Feature;

use App\Application\UseCase\ProcessAnalysisResponse\ProcessAnalysisResponseInput;
use App\Application\UseCase\ProcessAnalysisResponse\ProcessAnalysisResponseUseCase;
use App\Domain\Entity\AnalysisResponse;
use App\Domain\Exception\DomainHttpException;
use App\Domain\Interface\TriggerRepositoryInterface;
use App\Infrastructure\Repository\Models\TriggerModel;
use Tests\TestCase;

class ProcessAnalysisResponseUseCaseTest extends TestCase
{
    private string $uuid = '550e8400-e29b-41d4-a716-446655440000';

    private array $payload = [
        'protocol'        => '550e8400-e29b-41d4-a716-446655440000',
        'components'      => [['name' => 'Auth Service']],
        'risks'           => [['description' => 'Risk A']],
        'recommendations' => [['description' => 'Fix A']],
    ];

    private function makeUseCase(): ProcessAnalysisResponseUseCase
    {
        return new ProcessAnalysisResponseUseCase(
            $this->app->make(TriggerRepositoryInterface::class),
        );
    }

    private function criarTrigger(): TriggerModel
    {
        return TriggerModel::create([
            'protocol_uuid' => $this->uuid,
            'payload'       => ['protocol_uuid' => $this->uuid, 'file' => []],
            'created_at'    => now(),
        ]);
    }

    public function test_salva_analysis_response_com_status_sucesso(): void
    {
        $this->criarTrigger();

        $this->makeUseCase()->execute(
            ProcessAnalysisResponseInput::fromArray($this->payload)
        );

        $this->assertDatabaseHas('analysis_responses', [
            'status' => AnalysisResponse::STATUS_SUCESSO,
        ]);
    }

    public function test_salva_content_completo_no_banco(): void
    {
        $trigger = $this->criarTrigger();

        $this->makeUseCase()->execute(
            ProcessAnalysisResponseInput::fromArray($this->payload)
        );

        $response = \App\Infrastructure\Repository\Models\AnalysisResponseModel
            ::where('protocol_id', $trigger->id)
            ->where('status', AnalysisResponse::STATUS_SUCESSO)
            ->first();

        $this->assertNotNull($response);
        $this->assertSame($this->uuid, $response->content['protocol']);
        $this->assertArrayHasKey('components',      $response->content);
        $this->assertArrayHasKey('risks',           $response->content);
        $this->assertArrayHasKey('recommendations', $response->content);
    }

    public function test_lanca_excecao_quando_protocolo_nao_encontrado(): void
    {
        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage("Trigger não encontrado para o protocolo: {$this->uuid}");

        $this->makeUseCase()->execute(
            ProcessAnalysisResponseInput::fromArray($this->payload)
        );
    }

    public function test_nao_persiste_nada_quando_protocolo_nao_existe(): void
    {
        try {
            $this->makeUseCase()->execute(
                ProcessAnalysisResponseInput::fromArray($this->payload)
            );
        } catch (DomainHttpException) {}

        $this->assertDatabaseEmpty('analysis_responses');
    }
}
