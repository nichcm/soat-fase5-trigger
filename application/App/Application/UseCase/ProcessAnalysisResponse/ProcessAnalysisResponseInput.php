<?php

namespace App\Application\UseCase\ProcessAnalysisResponse;

class ProcessAnalysisResponseInput
{
    public function __construct(
        public readonly string $protocol,
        public readonly array  $components,
        public readonly array  $risks,
        public readonly array  $recommendations,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            protocol:        $data['protocol'],
            components:      $data['components']      ?? [],
            risks:           $data['risks']           ?? [],
            recommendations: $data['recommendations'] ?? [],
        );
    }

    public function toContent(): array
    {
        return [
            'protocol'        => $this->protocol,
            'components'      => $this->components,
            'risks'           => $this->risks,
            'recommendations' => $this->recommendations,
        ];
    }
}
