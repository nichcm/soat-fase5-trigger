<?php

namespace App\Application\UseCase\ProcessProtocol;

use App\Domain\Entity\AnalysisResponse;
use App\Domain\Entity\Trigger;
use App\Domain\Interface\AnalysisGatewayInterface;
use App\Domain\Interface\TriggerRepositoryInterface;
use DateTimeImmutable;
use Throwable;

class ProcessProtocolUseCase
{
    public function __construct(
        private readonly TriggerRepositoryInterface $repository,
        private readonly AnalysisGatewayInterface   $analysisGateway,
    ) {}

    public function execute(ProcessProtocolInput $input): void
    {
        $trigger = $this->repository->saveTrigger(new Trigger(
            id:           null,
            protocolUuid: $input->protocol,
            payload:      [
                'protocol_uuid' => $input->protocol,
                'file'          => [
                    'url'           => $input->fileUrl,
                    'name'          => $input->fileName,
                    'mimetype'      => $input->fileMimetype,
                    'size'          => $input->fileSize,
                    'original_name' => $input->originalName,
                    'hashed_name'   => $input->hashedName,
                ],
            ],
            createdAt:    new DateTimeImmutable(),
        ));

        $this->repository->saveAnalysisResponse(new AnalysisResponse(
            id:         null,
            protocolId: $trigger->id,
            status:     AnalysisResponse::STATUS_RECEBIDO,
            content:    null,
            receivedAt: new DateTimeImmutable(),
        ));

        try {
            $this->analysisGateway->sendForAnalysis(
                $input->protocol,
                $input->fileUrl,
                $input->fileMimetype,
            );

            $this->repository->saveAnalysisResponse(new AnalysisResponse(
                id:         null,
                protocolId: $trigger->id,
                status:     AnalysisResponse::STATUS_EM_PROCESSAMENTO,
                content:    null,
                receivedAt: new DateTimeImmutable(),
            ));
        } catch (Throwable $e) {
            logger()->error('Falha ao enviar protocolo para análise.', [
                'protocol' => $input->protocol,
                'error'    => $e->getMessage(),
            ]);

            $this->repository->saveAnalysisResponse(new AnalysisResponse(
                id:         null,
                protocolId: $trigger->id,
                status:     AnalysisResponse::STATUS_ERRO_PROCESSAMENTO,
                content:    null,
                receivedAt: new DateTimeImmutable(),
            ));
        }
    }
}
