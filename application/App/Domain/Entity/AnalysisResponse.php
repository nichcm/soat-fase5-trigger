<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class AnalysisResponse
{
    public const STATUS_RECEBIDO           = 'RECEBIDO';
    public const STATUS_EM_PROCESSAMENTO   = 'EM_PROCESSAMENTO';
    public const STATUS_ERRO               = 'ERRO';
    public const STATUS_SUCESSO            = 'SUCESSO';
    public const STATUS_ERRO_PROCESSAMENTO = 'ERRO_PROCESSAMENTO';

    public const STATUSES = [
        self::STATUS_RECEBIDO,
        self::STATUS_EM_PROCESSAMENTO,
        self::STATUS_ERRO,
        self::STATUS_SUCESSO,
        self::STATUS_ERRO_PROCESSAMENTO,
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly int $protocolId,
        public readonly string $status,
        public readonly ?array $content,
        public readonly DateTimeImmutable $receivedAt,
    ) {}
}
