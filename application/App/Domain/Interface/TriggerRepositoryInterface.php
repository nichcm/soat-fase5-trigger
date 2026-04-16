<?php

namespace App\Domain\Interface;

use App\Domain\Entity\AnalysisResponse;
use App\Domain\Entity\Trigger;

interface TriggerRepositoryInterface
{
    public function saveTrigger(Trigger $trigger): Trigger;

    public function findTriggerByProtocolUuid(string $protocolUuid): ?Trigger;

    public function saveAnalysisResponse(AnalysisResponse $response): AnalysisResponse;

    public function findLatestAnalysisResponseByProtocolId(int $protocolId): ?AnalysisResponse;

    public function findAllAnalysisResponsesByProtocolId(int $protocolId): array;
}
