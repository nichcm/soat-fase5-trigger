<?php

namespace Tests\Unit;

use App\Application\UseCase\ProcessAnalysisResponse\ProcessAnalysisResponseInput;
use PHPUnit\Framework\TestCase;

class ProcessAnalysisResponseInputTest extends TestCase
{
    private array $payload = [
        'protocol'        => '550e8400-e29b-41d4-a716-446655440000',
        'components'      => [['name' => 'Auth Service', 'type' => 'microservice']],
        'risks'           => [['description' => 'Single point of failure', 'severity' => 'high']],
        'recommendations' => [['description' => 'Add redundancy']],
    ];

    public function test_from_array_mapeia_campos_corretamente(): void
    {
        $input = ProcessAnalysisResponseInput::fromArray($this->payload);

        $this->assertSame($this->payload['protocol'],        $input->protocol);
        $this->assertSame($this->payload['components'],      $input->components);
        $this->assertSame($this->payload['risks'],           $input->risks);
        $this->assertSame($this->payload['recommendations'], $input->recommendations);
    }

    public function test_from_array_usa_arrays_vazios_como_fallback(): void
    {
        $input = ProcessAnalysisResponseInput::fromArray([
            'protocol' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $this->assertSame([], $input->components);
        $this->assertSame([], $input->risks);
        $this->assertSame([], $input->recommendations);
    }

    public function test_to_content_retorna_estrutura_correta(): void
    {
        $input   = ProcessAnalysisResponseInput::fromArray($this->payload);
        $content = $input->toContent();

        $this->assertArrayHasKey('protocol',        $content);
        $this->assertArrayHasKey('components',      $content);
        $this->assertArrayHasKey('risks',           $content);
        $this->assertArrayHasKey('recommendations', $content);
        $this->assertSame($this->payload['protocol'], $content['protocol']);
    }
}
