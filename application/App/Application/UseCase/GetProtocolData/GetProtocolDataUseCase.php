<?php

namespace App\Application\UseCase\GetProtocolData;

use App\Domain\Entity\AnalysisResponse;
use App\Domain\Exception\DomainHttpException;
use App\Domain\Interface\TriggerRepositoryInterface;
use Illuminate\Http\Response;

class GetProtocolDataUseCase
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

        $latest = $this->repository->findLatestAnalysisResponseByProtocolId($trigger->id);

        if (!$latest || $latest->status !== AnalysisResponse::STATUS_SUCESSO || !$latest->content) {
            throw new DomainHttpException(
                "Análise ainda não disponível para este protocolo.",
                Response::HTTP_NOT_FOUND,
            );
        }

        return $latest->content;
    }
}
