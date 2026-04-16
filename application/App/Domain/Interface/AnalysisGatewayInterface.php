<?php

namespace App\Domain\Interface;

interface AnalysisGatewayInterface
{
    public function sendForAnalysis(string $protocolUuid, string $fileUrl, string $fileMimetype): void;
}
