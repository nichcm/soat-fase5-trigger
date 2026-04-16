<?php

namespace Tests\Feature;

use App\Infrastructure\Repository\Models\AnalysisResponseModel;
use App\Infrastructure\Repository\Models\TriggerModel;
use Tests\TestCase;

class GetProtocolDataTest extends TestCase
{
    private string $validUuid = '550e8400-e29b-41d4-a716-446655440000';

    private array $analysisContent = [
        'protocol'        => '550e8400-e29b-41d4-a716-446655440000',
        'components'      => [['name' => 'Auth Service', 'type' => 'microservice']],
        'risks'           => [['description' => 'Single point of failure', 'severity' => 'high']],
        'recommendations' => [['description' => 'Add redundancy']],
    ];

    public function test_retorna_400_para_uuid_invalido(): void
    {
        $response = $this->getJson('/api/data/nao-e-um-uuid');

        $response->assertStatus(400)
            ->assertJson(['err' => true])
            ->assertJsonPath('message', 'O protocol_uuid deve ser um UUID válido.');
    }

    public function test_retorna_404_para_protocolo_nao_cadastrado(): void
    {
        $response = $this->getJson("/api/data/{$this->validUuid}");

        $response->assertStatus(404)
            ->assertJson(['err' => true])
            ->assertJsonPath('message', 'Protocolo não encontrado.');
    }

    public function test_retorna_404_quando_analise_ainda_nao_disponivel(): void
    {
        $trigger = TriggerModel::create([
            'protocol_uuid' => $this->validUuid,
            'payload'       => ['protocol_uuid' => $this->validUuid, 'file' => []],
            'created_at'    => now(),
        ]);

        AnalysisResponseModel::create([
            'protocol_id' => $trigger->id,
            'status'      => 'EM_PROCESSAMENTO',
            'content'     => null,
            'received_at' => now(),
        ]);

        $response = $this->getJson("/api/data/{$this->validUuid}");

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Análise ainda não disponível para este protocolo.');
    }

    public function test_retorna_content_quando_analise_com_sucesso(): void
    {
        $trigger = TriggerModel::create([
            'protocol_uuid' => $this->validUuid,
            'payload'       => ['protocol_uuid' => $this->validUuid, 'file' => []],
            'created_at'    => now(),
        ]);

        AnalysisResponseModel::create([
            'protocol_id' => $trigger->id,
            'status'      => 'SUCESSO',
            'content'     => $this->analysisContent,
            'received_at' => now(),
        ]);

        $response = $this->getJson("/api/data/{$this->validUuid}");

        $response->assertStatus(200)
            ->assertJson(['err' => false])
            ->assertJsonPath('data.protocol', $this->validUuid)
            ->assertJsonStructure([
                'data' => ['protocol', 'components', 'risks', 'recommendations'],
            ]);
    }

    public function test_retorna_estrutura_correta_na_resposta(): void
    {
        $trigger = TriggerModel::create([
            'protocol_uuid' => $this->validUuid,
            'payload'       => ['protocol_uuid' => $this->validUuid, 'file' => []],
            'created_at'    => now(),
        ]);

        AnalysisResponseModel::create([
            'protocol_id' => $trigger->id,
            'status'      => 'SUCESSO',
            'content'     => $this->analysisContent,
            'received_at' => now(),
        ]);

        $response = $this->getJson("/api/data/{$this->validUuid}");

        $response->assertStatus(200)
            ->assertJsonStructure(['err', 'message', 'data']);
    }
}
