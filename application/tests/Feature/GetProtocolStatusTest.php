<?php

namespace Tests\Feature;

use App\Infrastructure\Repository\Models\AnalysisResponseModel;
use App\Infrastructure\Repository\Models\TriggerModel;
use Tests\TestCase;

class GetProtocolStatusTest extends TestCase
{
    private string $validUuid = '550e8400-e29b-41d4-a716-446655440000';

    public function test_retorna_400_para_uuid_invalido(): void
    {
        $response = $this->getJson('/api/status/nao-e-um-uuid');

        $response->assertStatus(400)
            ->assertJson(['err' => true])
            ->assertJsonPath('message', 'O protocol_uuid deve ser um UUID válido.');
    }

    public function test_retorna_404_para_protocolo_nao_cadastrado(): void
    {
        $response = $this->getJson("/api/status/{$this->validUuid}");

        $response->assertStatus(404)
            ->assertJson(['err' => true])
            ->assertJsonPath('message', 'Protocolo não encontrado.');
    }

    public function test_retorna_status_recebido_quando_sem_analysis_response(): void
    {
        $trigger = TriggerModel::create([
            'protocol_uuid' => $this->validUuid,
            'payload'       => ['protocol_uuid' => $this->validUuid, 'file' => []],
            'created_at'    => now(),
        ]);

        $response = $this->getJson("/api/status/{$this->validUuid}");

        $response->assertStatus(200)
            ->assertJson(['err' => false])
            ->assertJsonPath('data.protocol_uuid', $this->validUuid)
            ->assertJsonPath('data.status', 'RECEBIDO')
            ->assertJsonPath('data.received_at', null);
    }

    public function test_retorna_status_da_ultima_analysis_response(): void
    {
        $trigger = TriggerModel::create([
            'protocol_uuid' => $this->validUuid,
            'payload'       => ['protocol_uuid' => $this->validUuid, 'file' => []],
            'created_at'    => now(),
        ]);

        AnalysisResponseModel::create([
            'protocol_id' => $trigger->id,
            'status'      => 'RECEBIDO',
            'content'     => null,
            'received_at' => now()->subSeconds(10),
        ]);

        AnalysisResponseModel::create([
            'protocol_id' => $trigger->id,
            'status'      => 'EM_PROCESSAMENTO',
            'content'     => null,
            'received_at' => now(),
        ]);

        $response = $this->getJson("/api/status/{$this->validUuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'EM_PROCESSAMENTO');
    }

    public function test_retorna_estrutura_correta_na_resposta(): void
    {
        TriggerModel::create([
            'protocol_uuid' => $this->validUuid,
            'payload'       => ['protocol_uuid' => $this->validUuid, 'file' => []],
            'created_at'    => now(),
        ]);

        $response = $this->getJson("/api/status/{$this->validUuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'err',
                'message',
                'data' => ['protocol_uuid', 'status', 'received_at'],
            ]);
    }
}
