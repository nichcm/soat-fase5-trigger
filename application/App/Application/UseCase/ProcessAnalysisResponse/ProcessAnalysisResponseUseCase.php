<?php

namespace App\Application\UseCase\ProcessAnalysisResponse;

use App\Domain\Entity\AnalysisResponse;
use App\Domain\Exception\DomainHttpException;
use App\Domain\Interface\TriggerRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Http\Response;

class ProcessAnalysisResponseUseCase
{
    public function __construct(
        private readonly TriggerRepositoryInterface $repository,
    ) {}

    public function execute(ProcessAnalysisResponseInput $input): void
    {
        $trigger = $this->repository->findTriggerByProtocolUuid($input->protocol);

        if (!$trigger) {
            throw new DomainHttpException(
                "Trigger não encontrado para o protocolo: {$input->protocol}",
                Response::HTTP_NOT_FOUND,
            );
        }

        $this->repository->saveAnalysisResponse(new AnalysisResponse(
            id:         null,
            protocolId: $trigger->id,
            status:     AnalysisResponse::STATUS_SUCESSO,
            content:    $input->toContent(),
            receivedAt: new DateTimeImmutable(),
        ));
    }
}
