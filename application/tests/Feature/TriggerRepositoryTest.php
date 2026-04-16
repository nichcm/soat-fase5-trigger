<?php

namespace Tests\Feature;

use App\Domain\Entity\AnalysisResponse;
use App\Domain\Entity\Trigger;
use App\Infrastructure\Repository\Models\TriggerModel;
use App\Infrastructure\Repository\TriggerRepository;
use DateTimeImmutable;
use Tests\TestCase;

class TriggerRepositoryTest extends TestCase
{
    private TriggerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TriggerRepository();
    }

    // -------------------------------------------------------------------------
    // saveTrigger
    // -------------------------------------------------------------------------

    public function test_save_trigger_persiste_no_banco(): void
    {
        $trigger = new Trigger(
            id:           null,
            protocolUuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            payload:      ['protocol_uuid' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee', 'file' => []],
            createdAt:    new DateTimeImmutable(),
        );

        $this->repository->saveTrigger($trigger);

        $this->assertDatabaseHas('triggers', [
            'protocol_uuid' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
        ]);
    }

    public function test_save_trigger_retorna_entidade_com_id_preenchido(): void
    {
        $trigger = new Trigger(
            id:           null,
            protocolUuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            payload:      ['protocol_uuid' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee', 'file' => []],
            createdAt:    new DateTimeImmutable(),
        );

        $saved = $this->repository->saveTrigger($trigger);

        $this->assertNotNull($saved->id);
        $this->assertIsInt($saved->id);
    }

    public function test_save_trigger_persiste_campos_corretamente(): void
    {
        $payload = ['protocol_uuid' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee', 'file' => ['name' => 'diagram.pdf']];

        $trigger = new Trigger(
            id:           null,
            protocolUuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            payload:      $payload,
            createdAt:    new DateTimeImmutable(),
        );

        $saved = $this->repository->saveTrigger($trigger);

        $this->assertSame('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee', $saved->protocolUuid);
        $this->assertSame($payload, $saved->payload);
        $this->assertInstanceOf(DateTimeImmutable::class, $saved->createdAt);
    }

    // -------------------------------------------------------------------------
    // saveAnalysisResponse
    // -------------------------------------------------------------------------

    public function test_save_analysis_response_persiste_no_banco(): void
    {
        $trigger = $this->criarTrigger();

        $response = new AnalysisResponse(
            id:         null,
            protocolId: $trigger->id,
            status:     AnalysisResponse::STATUS_RECEBIDO,
            content:    null,
            receivedAt: new DateTimeImmutable(),
        );

        $this->repository->saveAnalysisResponse($response);

        $this->assertDatabaseHas('analysis_responses', [
            'protocol_id' => $trigger->id,
            'status'      => AnalysisResponse::STATUS_RECEBIDO,
        ]);
    }

    public function test_save_analysis_response_retorna_entidade_com_id_preenchido(): void
    {
        $trigger = $this->criarTrigger();

        $response = new AnalysisResponse(
            id:         null,
            protocolId: $trigger->id,
            status:     AnalysisResponse::STATUS_RECEBIDO,
            content:    null,
            receivedAt: new DateTimeImmutable(),
        );

        $saved = $this->repository->saveAnalysisResponse($response);

        $this->assertNotNull($saved->id);
        $this->assertIsInt($saved->id);
    }

    public function test_save_analysis_response_persiste_campos_corretamente(): void
    {
        $trigger = $this->criarTrigger();
        $content = ['protocol' => 'abc', 'components' => [], 'risks' => [], 'recommendations' => []];

        $response = new AnalysisResponse(
            id:         null,
            protocolId: $trigger->id,
            status:     AnalysisResponse::STATUS_SUCESSO,
            content:    $content,
            receivedAt: new DateTimeImmutable(),
        );

        $saved = $this->repository->saveAnalysisResponse($response);

        $this->assertSame($trigger->id, $saved->protocolId);
        $this->assertSame(AnalysisResponse::STATUS_SUCESSO, $saved->status);
        $this->assertSame($content, $saved->content);
        $this->assertInstanceOf(DateTimeImmutable::class, $saved->receivedAt);
    }

    public function test_save_analysis_response_com_content_nulo(): void
    {
        $trigger = $this->criarTrigger();

        $response = new AnalysisResponse(
            id:         null,
            protocolId: $trigger->id,
            status:     AnalysisResponse::STATUS_EM_PROCESSAMENTO,
            content:    null,
            receivedAt: new DateTimeImmutable(),
        );

        $saved = $this->repository->saveAnalysisResponse($response);

        $this->assertNull($saved->content);
    }

    // -------------------------------------------------------------------------
    // findAllAnalysisResponsesByProtocolId
    // -------------------------------------------------------------------------

    public function test_find_all_retorna_array_vazio_quando_sem_respostas(): void
    {
        $trigger = $this->criarTrigger();

        $result = $this->repository->findAllAnalysisResponsesByProtocolId($trigger->id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_find_all_retorna_todas_as_respostas_do_protocolo(): void
    {
        $trigger = $this->criarTrigger();

        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $trigger->id, AnalysisResponse::STATUS_RECEBIDO,         null, new DateTimeImmutable('2026-04-16 10:00:00')));
        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $trigger->id, AnalysisResponse::STATUS_EM_PROCESSAMENTO, null, new DateTimeImmutable('2026-04-16 10:01:00')));
        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $trigger->id, AnalysisResponse::STATUS_SUCESSO,          [],   new DateTimeImmutable('2026-04-16 10:02:00')));

        $result = $this->repository->findAllAnalysisResponsesByProtocolId($trigger->id);

        $this->assertCount(3, $result);
    }

    public function test_find_all_retorna_respostas_ordenadas_por_received_at(): void
    {
        $trigger = $this->criarTrigger();

        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $trigger->id, AnalysisResponse::STATUS_SUCESSO,          [], new DateTimeImmutable('2026-04-16 10:02:00')));
        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $trigger->id, AnalysisResponse::STATUS_RECEBIDO,         null, new DateTimeImmutable('2026-04-16 10:00:00')));
        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $trigger->id, AnalysisResponse::STATUS_EM_PROCESSAMENTO, null, new DateTimeImmutable('2026-04-16 10:01:00')));

        $result = $this->repository->findAllAnalysisResponsesByProtocolId($trigger->id);

        $this->assertSame(AnalysisResponse::STATUS_RECEBIDO,         $result[0]->status);
        $this->assertSame(AnalysisResponse::STATUS_EM_PROCESSAMENTO, $result[1]->status);
        $this->assertSame(AnalysisResponse::STATUS_SUCESSO,          $result[2]->status);
    }

    public function test_find_all_retorna_apenas_respostas_do_protocolo_correto(): void
    {
        $triggerA = $this->criarTrigger('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee');
        $triggerB = $this->criarTrigger('ffffffff-bbbb-cccc-dddd-eeeeeeeeeeee');

        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $triggerA->id, AnalysisResponse::STATUS_RECEBIDO, null, new DateTimeImmutable()));
        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $triggerA->id, AnalysisResponse::STATUS_SUCESSO,  [],   new DateTimeImmutable()));
        $this->repository->saveAnalysisResponse(new AnalysisResponse(null, $triggerB->id, AnalysisResponse::STATUS_RECEBIDO, null, new DateTimeImmutable()));

        $resultA = $this->repository->findAllAnalysisResponsesByProtocolId($triggerA->id);
        $resultB = $this->repository->findAllAnalysisResponsesByProtocolId($triggerB->id);

        $this->assertCount(2, $resultA);
        $this->assertCount(1, $resultB);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function criarTrigger(string $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'): Trigger
    {
        return $this->repository->saveTrigger(new Trigger(
            id:           null,
            protocolUuid: $uuid,
            payload:      ['protocol_uuid' => $uuid, 'file' => []],
            createdAt:    new DateTimeImmutable(),
        ));
    }
}
