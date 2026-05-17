<?php

namespace App\Application\UseCase\GetProtocolStatus;

use App\Domain\Exception\DomainHttpException;
use App\Domain\Interface\TriggerRepositoryInterface;
use Illuminate\Http\Response;

class GetProtocolStatusUseCase
{
    public function __construct(
        private readonly TriggerRepositoryInterface $repository,
    ) {}

    public function execute(string $protocolUuid): array
    {
        $trigger = $this->repository->findTriggerByProtocolUuid($protocolUuid);

        if (!$trigger) {
            throw new DomainHttpException(
                "Protocolo não encontrado.",
                Response::HTTP_NOT_FOUND,
            );
        }

        logger()->info("Antes findLatestAnalysisResponseByProtocolId", ['protocol' => $protocolUuid]);
        $latest = $this->repository->findLatestAnalysisResponseByProtocolId($trigger->id);

        logger()->info("Após findLatestAnalysisResponseByProtocolId", ['dados' => $latest]);
        return [
            'protocol_uuid' => $protocolUuid,
            'status'        => $latest?->status ?? 'RECEBIDO',
            'received_at'   => $latest?->receivedAt->format('Y-m-d H:i:s') ?? null,
        ];
    }
}
