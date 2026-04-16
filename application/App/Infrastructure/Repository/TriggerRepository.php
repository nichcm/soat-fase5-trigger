<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entity\AnalysisResponse;
use App\Domain\Entity\Trigger;
use App\Domain\Interface\TriggerRepositoryInterface;
use App\Infrastructure\Repository\Models\AnalysisResponseModel;
use App\Infrastructure\Repository\Models\TriggerModel;
use DateTimeImmutable;

class TriggerRepository implements TriggerRepositoryInterface
{
    public function saveTrigger(Trigger $trigger): Trigger
    {
        $model = TriggerModel::create([
            'protocol_uuid' => $trigger->protocolUuid,
            'payload'       => $trigger->payload,
            'created_at'    => now(),
        ]);

        return new Trigger(
            id:           $model->id,
            protocolUuid: $model->protocol_uuid,
            payload:      $model->payload,
            createdAt:    new DateTimeImmutable($model->created_at),
        );
    }

    public function findTriggerByProtocolUuid(string $protocolUuid): ?Trigger
    {
        $model = TriggerModel::where('protocol_uuid', $protocolUuid)->first();

        if (!$model) {
            return null;
        }

        return new Trigger(
            id:           $model->id,
            protocolUuid: $model->protocol_uuid,
            payload:      $model->payload,
            createdAt:    new DateTimeImmutable($model->created_at),
        );
    }

    public function saveAnalysisResponse(AnalysisResponse $response): AnalysisResponse
    {
        $model = AnalysisResponseModel::create([
            'protocol_id' => $response->protocolId,
            'status'      => $response->status,
            'content'     => $response->content,
            'received_at' => $response->receivedAt->format('Y-m-d H:i:s'),
        ]);

        return new AnalysisResponse(
            id:         $model->id,
            protocolId: $model->protocol_id,
            status:     $model->status,
            content:    $model->content,
            receivedAt: new DateTimeImmutable($model->received_at),
        );
    }

    public function findLatestAnalysisResponseByProtocolId(int $protocolId): ?AnalysisResponse
    {
        $model = AnalysisResponseModel::where('protocol_id', $protocolId)
            ->orderByDesc('received_at')
            ->first();

        if (!$model) {
            return null;
        }

        return new AnalysisResponse(
            id:         $model->id,
            protocolId: $model->protocol_id,
            status:     $model->status,
            content:    $model->content,
            receivedAt: new DateTimeImmutable($model->received_at),
        );
    }

    public function findAllAnalysisResponsesByProtocolId(int $protocolId): array
    {
        return AnalysisResponseModel::where('protocol_id', $protocolId)
            ->orderBy('received_at')
            ->get()
            ->map(fn(AnalysisResponseModel $model) => new AnalysisResponse(
                id:         $model->id,
                protocolId: $model->protocol_id,
                status:     $model->status,
                content:    $model->content,
                receivedAt: new DateTimeImmutable($model->received_at),
            ))
            ->toArray();
    }
}
