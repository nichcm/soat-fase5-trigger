<?php

namespace App\Infrastructure\Gateway;

use App\Domain\Interface\AnalysisGatewayInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnalysisGateway implements AnalysisGatewayInterface
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('ANALYSIS_SERVICE_URL');
    }

    public function sendForAnalysis(string $protocolUuid, string $fileUrl, string $fileMimetype): void
    {
        $response = Http::post("{$this->baseUrl}/analyze", [
            'protocol' => $protocolUuid,
            'file'     => [
                'url'      => $fileUrl,
                'mimetype' => $fileMimetype,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Falha ao enviar para o serviço de análise. Status: {$response->status()}"
            );
        }
    }
}
